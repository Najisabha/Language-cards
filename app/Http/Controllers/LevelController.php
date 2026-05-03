<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Level;
use App\Support\PrintLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
        $level->load([
            'language',
            'decks' => fn ($q) => $q->withCount(['categories', 'cards'])->latest(),
        ]);

        $stats = [
            'decks' => $level->decks->count(),
            'categories' => $level->decks->sum('categories_count'),
            'cards' => $level->decks->sum('cards_count'),
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

    public function edit(Level $level): View
    {
        $level->load('language');

        return view('levels.edit', compact('level'));
    }

    public function update(Request $request, Level $level): RedirectResponse
    {
        $level->update($this->validateLevel($request));

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

    private function validateLevel(Request $request): array
    {
        return $request->validate([
            'language_id' => ['required', 'exists:languages,id'],
            'name' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:120'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
