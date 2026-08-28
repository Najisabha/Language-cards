<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Deck;
use App\Models\Language;
use App\Models\Level;
use App\Support\LevelExcelExchange;
use App\Support\PrintLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LevelController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $languageId = $request->integer('language_id');

        if (! $languageId) {
            $languages = Language::query()
                ->withCount('levels')
                ->orderBy('position')
                ->orderBy('name')
                ->get();

            return view('levels.index', [
                'mode' => 'pick_language',
                'languages' => $languages,
                'selectedLanguage' => null,
                'levels' => collect(),
                'stats' => [
                    'languages' => $languages->count(),
                    'levels' => $languages->sum('levels_count'),
                ],
            ]);
        }

        $selectedLanguage = Language::find($languageId);
        if (! $selectedLanguage) {
            return redirect()->route('levels.index')->with('status', 'اللغة غير موجودة.');
        }

        $levels = Level::query()
            ->where('language_id', $languageId)
            ->with('language')
            ->withCount([
                'decks',
                'decks as cards_count' => fn ($query) => $query->join('categories', 'categories.deck_id', '=', 'decks.id')
                    ->join('cards', 'cards.category_id', '=', 'categories.id')
                    ->select(DB::raw('count(cards.id)')),
            ])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $stats = [
            'levels' => $levels->count(),
            'decks' => $levels->sum('decks_count'),
            'cards' => $levels->sum('cards_count'),
        ];

        return view('levels.index', [
            'mode' => 'filtered_levels',
            'languages' => collect(),
            'selectedLanguage' => $selectedLanguage,
            'levels' => $levels,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $selectedLanguage = Language::query()
            ->orderBy('position')
            ->orderBy('name')
            ->find($request->integer('language_id'));

        if (! $selectedLanguage) {
            return redirect()->route('languages.index')->with('status', 'اختر لغة أولًا قبل إنشاء مستوى جديد.');
        }

        return view('levels.create', compact('selectedLanguage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $level = Level::create($this->validateLevel($request));

        return redirect()->route('levels.show', $level)->with('status', 'تم إنشاء المستوى بنجاح.');
    }

    public function show(Level $level): View
    {
        $level->migrateLegacyWordSentenceDecks();

        $level->load([
            'language',
            'decks' => fn ($q) => $q->topics()->withCount(['categories', 'cards'])->orderBy('id'),
        ]);

        $wordSentenceCounts = Card::query()
            ->join('categories', 'cards.category_id', '=', 'categories.id')
            ->join('decks', 'categories.deck_id', '=', 'decks.id')
            ->where('decks.level_id', $level->id)
            ->whereIn('categories.name', [Deck::CATEGORY_WORDS, Deck::CATEGORY_SENTENCES])
            ->selectRaw('categories.name, count(*) as total')
            ->groupBy('categories.name')
            ->pluck('total', 'name');

        $stats = [
            'decks' => $level->decks->count(),
            'cards' => $level->decks->sum('cards_count'),
            'words' => (int) $wordSentenceCounts->get(Deck::CATEGORY_WORDS, 0),
            'sentences' => (int) $wordSentenceCounts->get(Deck::CATEGORY_SENTENCES, 0),
        ];

        return view('levels.show', compact('level', 'stats'));
    }

    public function printOptions(Level $level): View
    {
        $level->load([
            'language',
            'decks' => fn ($q) => $q->withCount('cards')->latest(),
        ]);

        return view('levels.print-options', compact('level'));
    }

    public function print(Request $request, Level $level): View|RedirectResponse
    {
        $validated = PrintLayout::validateQuery($request);
        $printSettings = PrintLayout::settingsFromQuery($validated);

        $level->load([
            'language',
            'decks' => fn ($q) => $q->latest()->with([
                'categories' => fn ($cq) => $cq->orderBy('position')->with([
                    'cards' => fn ($c) => $c->orderBy('position'),
                ]),
            ]),
        ]);

        $allowedIds = $level->decks->pluck('id')->all();
        $explicitSubset = $request->query('selection') === '1';

        $rawDeckIds = $request->input('deck_ids', []);
        if (! is_array($rawDeckIds)) {
            $rawDeckIds = [];
        }
        $rawDeckIds = array_values(array_unique(array_filter(array_map('intval', $rawDeckIds))));

        if ($explicitSubset) {
            if (count($rawDeckIds) === 0) {
                return redirect()
                    ->route('levels.print.options', $level)
                    ->with('status', 'اختر مجموعة واحدة على الأقل للطباعة.');
            }

            $selectedDeckIds = array_values(array_intersect($rawDeckIds, $allowedIds));
            if (count($selectedDeckIds) === 0) {
                return redirect()
                    ->route('levels.print.options', $level)
                    ->with('status', 'المجموعات المحددة غير صالحة لهذا المستوى.');
            }
        } else {
            $selectedDeckIds = $allowedIds;
        }

        $selectedSet = array_flip($selectedDeckIds);
        $cards = $level->decks
            ->filter(fn ($deck) => isset($selectedSet[$deck->id]))
            ->flatMap(fn ($deck) => $deck->categories->flatMap->cards)
            ->values();

        return view('decks.print', [
            'deck' => null,
            'level' => $level,
            'printSettings' => $printSettings,
            'printScope' => 'level',
            'preloadedPrintCards' => $cards,
            'selectedDeckIds' => $selectedDeckIds,
            'levelPrintSubset' => $explicitSubset,
        ]);
    }

    public function export(Level $level, LevelExcelExchange $excel): StreamedResponse
    {
        $level->migrateLegacyWordSentenceDecks();

        return $excel->download($level);
    }

    public function importForm(Level $level): View
    {
        $level->load('language');

        return view('levels.import', compact('level'));
    }

    public function importTemplate(Level $level, LevelExcelExchange $excel): StreamedResponse
    {
        return $excel->downloadTemplate($level);
    }

    public function import(Request $request, Level $level, LevelExcelExchange $excel): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'اختر ملف Excel للاستيراد.',
            'file.mimes' => 'يجب أن يكون الملف بصيغة Excel (.xlsx أو .xls).',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
        ]);

        $level->migrateLegacyWordSentenceDecks();

        $result = $excel->import($level, $request->file('file'));

        $message = sprintf(
            'تم الاستيراد: %d جديدة، %d محدّثة، %d متخطاة.',
            $result['created'],
            $result['updated'],
            $result['skipped']
        );

        if ($result['errors'] !== []) {
            $preview = implode(' ', array_slice($result['errors'], 0, 5));
            $extra = count($result['errors']) > 5
                ? ' … (+'.(count($result['errors']) - 5).')'
                : '';

            return redirect()
                ->route('levels.show', $level)
                ->with('status', $message.' ملاحظات: '.$preview.$extra);
        }

        return redirect()->route('levels.show', $level)->with('status', $message);
    }

    public function edit(Level $level): View
    {
        $level->load('language');

        return view('levels.edit', compact('level'));
    }

    public function update(Request $request, Level $level): RedirectResponse
    {
        $level->update($this->validateLevel($request, $level->id));

        return redirect()->route('levels.show', $level)->with('status', 'تم تحديث المستوى.');
    }

    public function destroy(Level $level): RedirectResponse
    {
        $language = $level->language;
        $level->delete();

        if ($language) {
            return redirect()->route('languages.show', $language)->with('status', 'تم حذف المستوى.');
        }

        return redirect()->route('languages.index')->with('status', 'تم حذف المستوى.');
    }

    private function validateLevel(Request $request, ?int $ignoreLevelId = null): array
    {
        $languageId = $request->input('language_id');

        $nameRule = Rule::unique('levels', 'name')
            ->where(fn ($query) => $query->where('language_id', $languageId));

        if ($ignoreLevelId !== null) {
            $nameRule->ignore($ignoreLevelId);
        }

        return $request->validate([
            'language_id' => ['required', 'exists:languages,id'],
            'name' => ['required', 'string', 'max:50', $nameRule],
            'title' => ['nullable', 'string', 'max:120'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'name.unique' => 'رمز المستوى مستخدم مسبقاً في هذه اللغة.',
        ]);
    }
}
