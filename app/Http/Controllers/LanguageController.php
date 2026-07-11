<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Support\LanguageExcelExchange;
use App\Support\PrintLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LanguageController extends Controller
{
    public function index(): View
    {
        $languages = Language::withCount([
            'levels',
            'levels as decks_count' => fn ($query) => $query->join('decks', 'decks.level_id', '=', 'levels.id')
                ->select(DB::raw('count(decks.id)')),
            'levels as cards_count' => fn ($query) => $query->join('decks', 'decks.level_id', '=', 'levels.id')
                ->join('categories', 'categories.deck_id', '=', 'decks.id')
                ->join('cards', 'cards.category_id', '=', 'categories.id')
                ->select(DB::raw('count(cards.id)')),
        ])->orderBy('position')->orderBy('name')->get();

        $stats = [
            'languages' => $languages->count(),
            'levels' => $languages->sum('levels_count'),
            'cards' => $languages->sum('cards_count'),
        ];

        return view('languages.index', compact('languages', 'stats'));
    }

    public function create(): View
    {
        return view('languages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $language = Language::create($this->validateLanguage($request));

        return redirect()->route('languages.show', $language)->with('status', 'تم إنشاء اللغة بنجاح.');
    }

    public function show(Language $language): View
    {
        $language->load([
            'levels' => fn ($q) => $q->withCount([
                'decks',
                'decks as cards_count' => fn ($query) => $query->join('categories', 'categories.deck_id', '=', 'decks.id')
                    ->join('cards', 'cards.category_id', '=', 'categories.id')
                    ->select(DB::raw('count(cards.id)')),
            ])->orderBy('position')->orderBy('name'),
        ]);

        $stats = [
            'levels' => $language->levels->count(),
            'decks' => $language->levels->sum('decks_count'),
            'cards' => $language->levels->sum('cards_count'),
        ];

        return view('languages.show', compact('language', 'stats'));
    }

    public function printOptions(Language $language): View
    {
        $language->load([
            'levels' => fn ($q) => $q->orderBy('position')->orderBy('name')->with([
                'decks' => fn ($dq) => $dq->withCount('cards')->latest(),
            ]),
        ]);

        return view('languages.print-options', compact('language'));
    }

    public function print(Request $request, Language $language): View|RedirectResponse
    {
        $validated = PrintLayout::validateQuery($request);
        $printSettings = PrintLayout::settingsFromQuery($validated);

        $language->load([
            'levels' => fn ($q) => $q->orderBy('position')->orderBy('name')->with([
                'decks' => fn ($dq) => $dq->latest()->with([
                    'categories' => fn ($cq) => $cq->orderBy('position')->with([
                        'cards' => fn ($c) => $c->orderBy('position'),
                    ]),
                ]),
            ]),
        ]);

        $allDecks = $language->levels->flatMap->decks;
        $allowedIds = $allDecks->pluck('id')->all();
        $explicitSubset = $request->query('selection') === '1';

        $rawDeckIds = $request->input('deck_ids', []);
        if (! is_array($rawDeckIds)) {
            $rawDeckIds = [];
        }
        $rawDeckIds = array_values(array_unique(array_filter(array_map('intval', $rawDeckIds))));

        if ($explicitSubset) {
            if (count($rawDeckIds) === 0) {
                return redirect()
                    ->route('languages.print.options', $language)
                    ->with('status', 'اختر مجموعة واحدة على الأقل للطباعة.');
            }

            $selectedDeckIds = array_values(array_intersect($rawDeckIds, $allowedIds));
            if (count($selectedDeckIds) === 0) {
                return redirect()
                    ->route('languages.print.options', $language)
                    ->with('status', 'المجموعات المحددة غير صالحة لهذه اللغة.');
            }
        } else {
            $selectedDeckIds = $allowedIds;
        }

        $selectedSet = array_flip($selectedDeckIds);
        $cards = $allDecks
            ->filter(fn ($deck) => isset($selectedSet[$deck->id]))
            ->flatMap(fn ($deck) => $deck->categories->flatMap->cards)
            ->values();

        return view('decks.print', [
            'deck' => null,
            'level' => null,
            'language' => $language,
            'printSettings' => $printSettings,
            'printScope' => 'language',
            'preloadedPrintCards' => $cards,
            'selectedDeckIds' => $selectedDeckIds,
            'levelPrintSubset' => $explicitSubset,
        ]);
    }

    public function export(Language $language, LanguageExcelExchange $excel): StreamedResponse
    {
        return $excel->download($language);
    }

    public function importForm(Language $language): View
    {
        return view('languages.import', compact('language'));
    }

    public function importTemplate(Language $language, LanguageExcelExchange $excel): StreamedResponse
    {
        return $excel->downloadTemplate($language);
    }

    public function import(Request $request, Language $language, LanguageExcelExchange $excel): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'اختر ملف Excel للاستيراد.',
            'file.mimes' => 'يجب أن يكون الملف بصيغة Excel (.xlsx أو .xls).',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
        ]);

        $result = $excel->import($language, $request->file('file'));

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
                ->route('languages.show', $language)
                ->with('status', $message.' ملاحظات: '.$preview.$extra);
        }

        return redirect()->route('languages.show', $language)->with('status', $message);
    }

    public function edit(Language $language): View
    {
        return view('languages.edit', compact('language'));
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $language->update($this->validateLanguage($request, $language->id));

        return redirect()->route('languages.show', $language)->with('status', 'تم تحديث اللغة.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();

        return redirect()->route('languages.index')->with('status', 'تم حذف اللغة.');
    }

    private function validateLanguage(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:languages,name';
        if ($ignoreId !== null) {
            $uniqueRule .= ','.$ignoreId;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:80', $uniqueRule],
            'code' => ['nullable', 'string', 'max:10'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
