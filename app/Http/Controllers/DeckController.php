<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\Language;
use App\Models\Level;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeckController extends Controller
{
    /**
     * Canonical physical paper dimensions in mm as [width, height] for the
     * orientation we render on screen and print. A4 stays portrait (matches
     * the original 3x3 layout); larger sizes use the orientation that fits
     * the doubled card count, so the preview's white "page" exactly matches
     * the real paper the user will print on.
     */
    private const PAPER_DIMENSIONS_MM = [
        'A4' => [210.0, 297.0],
        'A3' => [420.0, 297.0],
        'A2' => [420.0, 594.0],
        'A1' => [841.0, 594.0],
        'A0' => [841.0, 1189.0],
    ];

    /**
     * Card size and inter-card spacing in mm. The card size is fixed across
     * paper sizes so a larger paper simply holds more cards rather than
     * larger ones. Values match the original A4 layout: 3x3 grid with 10mm
     * padding and 4mm gaps fits nine cards of (190 - 8) / 3 by (277 - 8) / 3.
     */
    private const CARD_WIDTH_MM = 60.6666667;
    private const CARD_HEIGHT_MM = 89.6666667;
    private const PAGE_PADDING_MM = 10.0;
    private const CARD_GAP_MM = 4.0;
    private const CARD_BORDER_WIDTH_MM = 0.3;

    /**
     * Hard ceilings for the custom print form. They prevent users from
     * entering values that would either crash the layout (negative gaps),
     * overflow the printable area, or generate massive grids that the
     * browser would struggle to paginate.
     */
    private const CUSTOM_LIMITS = [
        'rows' => ['min' => 1, 'max' => 20],
        'cols' => ['min' => 1, 'max' => 20],
        'page_padding_mm' => ['min' => 0.0, 'max' => 50.0],
        'card_gap_mm' => ['min' => 0.0, 'max' => 30.0],
        'border_width_mm' => ['min' => 0.0, 'max' => 5.0],
    ];

    private const CUSTOM_BORDER_STYLES = ['solid', 'dashed', 'dotted', 'double', 'none'];

    public function index(Request $request): View|RedirectResponse
    {
        $languageId = $request->integer('language_id');
        $levelId = $request->integer('level_id');

        if (! $languageId) {
            $languages = Language::query()
                ->withCount([
                    'levels',
                    'levels as decks_count' => fn ($query) => $query->join('decks', 'decks.level_id', '=', 'levels.id')
                        ->select(DB::raw('count(decks.id)')),
                ])
                ->orderBy('position')
                ->orderBy('name')
                ->get();

            return view('decks.index', [
                'mode' => 'pick_language',
                'languages' => $languages,
                'language' => null,
                'level' => null,
                'levels' => collect(),
                'decks' => collect(),
                'stats' => [
                    'languages' => $languages->count(),
                    'levels' => $languages->sum('levels_count'),
                    'decks' => $languages->sum('decks_count'),
                ],
            ]);
        }

        $language = Language::find($languageId);
        if (! $language) {
            return redirect()->route('decks.index')->with('status', 'اللغة غير موجودة.');
        }

        if (! $levelId) {
            $language->load([
                'levels' => fn ($q) => $q->withCount([
                    'decks',
                    'decks as cards_count' => fn ($query) => $query->join('categories', 'categories.deck_id', '=', 'decks.id')
                        ->join('cards', 'cards.category_id', '=', 'categories.id')
                        ->select(DB::raw('count(cards.id)')),
                ])
                    ->orderBy('position')
                    ->orderBy('name'),
            ]);

            return view('decks.index', [
                'mode' => 'pick_level',
                'languages' => collect(),
                'language' => $language,
                'level' => null,
                'levels' => $language->levels,
                'decks' => collect(),
                'stats' => [
                    'levels' => $language->levels->count(),
                    'decks' => $language->levels->sum('decks_count'),
                    'cards' => $language->levels->sum('cards_count'),
                ],
            ]);
        }

        $level = Level::query()
            ->where('id', $levelId)
            ->where('language_id', $languageId)
            ->first();

        if (! $level) {
            return redirect()->route('decks.index', ['language_id' => $languageId])
                ->with('status', 'المستوى غير موجود أو لا ينتمي لهذه اللغة.');
        }

        $decks = Deck::query()
            ->where('level_id', $level->id)
            ->with(['level.language'])
            ->withCount(['categories', 'cards'])
            ->latest()
            ->get();

        $stats = [
            'decks' => $decks->count(),
            'cards' => $decks->sum('cards_count'),
            'categories' => $decks->sum('categories_count'),
        ];

        return view('decks.index', [
            'mode' => 'list_decks',
            'languages' => collect(),
            'language' => $language,
            'level' => $level,
            'levels' => collect(),
            'decks' => $decks,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $selectedLevel = Level::query()
            ->with('language')
            ->find($request->integer('level_id'));

        if (! $selectedLevel) {
            return redirect()->route('levels.index')->with('status', 'اختر مستوى أولًا قبل إنشاء مجموعة جديدة.');
        }

        return view('decks.create', compact('selectedLevel'));
    }

    public function store(Request $request): RedirectResponse
    {
        $deck = Deck::create($this->validateDeck($request));

        return redirect()->route('decks.show', $deck)->with('status', 'تم إنشاء المجموعة بنجاح.');
    }

    public function show(Deck $deck): View
    {
        $deck->load([
            'level.language',
            'categories' => fn ($q) => $q->with(['cards' => fn ($c) => $c->orderBy('position')])->withCount('cards'),
        ]);

        $cards = $deck->categories->flatMap->cards->values();

        return view('decks.show', compact('deck', 'cards'));
    }

    public function edit(Deck $deck): View
    {
        $deck->load('level.language');

        return view('decks.edit', compact('deck'));
    }

    public function update(Request $request, Deck $deck): RedirectResponse
    {
        $deck->update($this->validateDeck($request));

        return redirect()->route('decks.show', $deck)->with('status', 'تم تحديث المجموعة.');
    }

    public function destroy(Deck $deck): RedirectResponse
    {
        $level = $deck->level;
        $deck->delete();

        if ($level) {
            return redirect()->route('levels.show', $level)->with('status', 'تم حذف المجموعة.');
        }

        return redirect()->route('languages.index')->with('status', 'تم حذف المجموعة.');
    }

    public function printOptions(Deck $deck): View
    {
        $deck->load('level.language');

        return view('decks.print-options', compact('deck'));
    }

    public function print(Request $request, Deck $deck): View
    {
        $deck->load('level', 'categories.cards');

        $rowsLimits = self::CUSTOM_LIMITS['rows'];
        $colsLimits = self::CUSTOM_LIMITS['cols'];
        $paddingLimits = self::CUSTOM_LIMITS['page_padding_mm'];
        $gapLimits = self::CUSTOM_LIMITS['card_gap_mm'];
        $borderLimits = self::CUSTOM_LIMITS['border_width_mm'];

        $validated = $request->validate([
            'paper_size' => ['nullable', Rule::in(array_keys(self::PAPER_DIMENSIONS_MM))],
            'mode' => ['nullable', Rule::in(['default', 'custom'])],
            'rows' => ['nullable', 'integer', 'min:'.$rowsLimits['min'], 'max:'.$rowsLimits['max']],
            'cols' => ['nullable', 'integer', 'min:'.$colsLimits['min'], 'max:'.$colsLimits['max']],
            'page_padding_mm' => ['nullable', 'numeric', 'min:'.$paddingLimits['min'], 'max:'.$paddingLimits['max']],
            'card_gap_mm' => ['nullable', 'numeric', 'min:'.$gapLimits['min'], 'max:'.$gapLimits['max']],
            'border_width_mm' => ['nullable', 'numeric', 'min:'.$borderLimits['min'], 'max:'.$borderLimits['max']],
            'border_style' => ['nullable', Rule::in(self::CUSTOM_BORDER_STYLES)],
        ]);

        $mode = $validated['mode'] ?? 'default';
        $paperSize = $validated['paper_size'] ?? 'A4';

        $printSettings = $mode === 'custom'
            ? $this->buildCustomPrintSettings($paperSize, $validated)
            : $this->buildPrintSettings($paperSize);

        return view('decks.print', compact('deck', 'printSettings'));
    }

    /**
     * Build a print layout from user-provided rows/cols and styling values.
     * The card size is derived from the paper size and grid so the cards
     * always fill the printable area instead of being cropped or floating.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildCustomPrintSettings(string $paperSize, array $input): array
    {
        [$pageWidth, $pageHeight] = self::PAPER_DIMENSIONS_MM[$paperSize];

        $rows = (int) ($input['rows'] ?? 3);
        $cols = (int) ($input['cols'] ?? 3);
        $padding = (float) ($input['page_padding_mm'] ?? self::PAGE_PADDING_MM);
        $gap = (float) ($input['card_gap_mm'] ?? self::CARD_GAP_MM);
        $borderWidth = (float) ($input['border_width_mm'] ?? self::CARD_BORDER_WIDTH_MM);
        $borderStyle = $input['border_style'] ?? 'solid';

        // If style is visible but width is zero, keep a readable default.
        if ($borderStyle !== 'none' && $borderWidth <= 0) {
            $borderWidth = self::CARD_BORDER_WIDTH_MM;
        }

        $usableWidth = max(0.0, $pageWidth - 2 * $padding - max(0, $cols - 1) * $gap);
        $usableHeight = max(0.0, $pageHeight - 2 * $padding - max(0, $rows - 1) * $gap);

        $cardWidth = $cols > 0 ? $usableWidth / $cols : 0.0;
        $cardHeight = $rows > 0 ? $usableHeight / $rows : 0.0;

        $cssPageSize = $this->formatMm($pageWidth) . 'mm ' . $this->formatMm($pageHeight) . 'mm';

        return [
            'paper_size' => $paperSize,
            'css_page_size' => $cssPageSize,
            'page_width_mm' => $pageWidth,
            'page_height_mm' => $pageHeight,
            'card_width_mm' => $cardWidth,
            'card_height_mm' => $cardHeight,
            'padding_mm' => $padding,
            'gap_mm' => $gap,
            'cols' => $cols,
            'rows' => $rows,
            'per_page' => $cols * $rows,
            'border_width_mm' => $borderWidth,
            'border_style' => $borderStyle,
            'mode' => 'custom',
        ];
    }

    /**
     * Compute layout metrics using the "doubling from A4" model:
     * the column count doubles for each step above A4 (3, 6, 12, 24, 48)
     * while the row count remains 3, yielding 9, 18, 36, 72, 144 cards.
     * Page dimensions are derived from the grid so the card size never
     * changes. Orientation is fixed to landscape for a consistent UX.
     *
     * @return array<string, mixed>
     */
    private function buildPrintSettings(string $paperSize): array
    {
        [$cols, $rows] = $this->gridFor($paperSize);
        [$pageWidth, $pageHeight] = self::PAPER_DIMENSIONS_MM[$paperSize];

        // Always emit an explicit "WIDTH HEIGHT" page size so the browser
        // never falls back to its default A4 box when the named size is
        // ignored or the chosen orientation differs from the dialog default.
        $cssPageSize = $this->formatMm($pageWidth) . 'mm ' . $this->formatMm($pageHeight) . 'mm';

        return [
            'paper_size' => $paperSize,
            'css_page_size' => $cssPageSize,
            'page_width_mm' => $pageWidth,
            'page_height_mm' => $pageHeight,
            'card_width_mm' => self::CARD_WIDTH_MM,
            'card_height_mm' => self::CARD_HEIGHT_MM,
            'padding_mm' => self::PAGE_PADDING_MM,
            'gap_mm' => self::CARD_GAP_MM,
            'cols' => $cols,
            'rows' => $rows,
            'per_page' => $cols * $rows,
            'border_width_mm' => self::CARD_BORDER_WIDTH_MM,
            'border_style' => 'solid',
            'mode' => 'default',
        ];
    }

    /**
     * Grid (cols, rows) for each paper size. Each layout is hand-picked so
     * that the fixed A4-sized cards genuinely fit inside the canonical paper
     * dimensions (PAPER_DIMENSIONS_MM). The card count still doubles per
     * step (9, 18, 36, 72, 144) to preserve the "doubling from A4" model.
     *
     * @return array{0:int,1:int}
     */
    private function gridFor(string $paperSize): array
    {
        return match ($paperSize) {
            'A4' => [3, 3],
            'A3' => [6, 3],
            'A2' => [6, 6],
            'A1' => [12, 6],
            'A0' => [12, 12],
        };
    }

    /**
     * Render a millimetre value without trailing zeros so the inline CSS
     * stays clean (e.g. 420 instead of 420.000) and exact paper sizes
     * remain byte-identical to what browsers expect.
     */
    private function formatMm(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    public function reorderCardsForm(Deck $deck): View
    {
        $deck->load('level.language');
        $cards = $deck->cards()->orderBy('cards.position')->get();

        return view('decks.reorder-cards', compact('deck', 'cards'));
    }

    public function reorderCards(Request $request, Deck $deck): RedirectResponse
    {
        $validated = $request->validate([
            'cards' => ['required', 'array', 'min:1'],
            'cards.*.id' => ['required', 'integer', 'exists:cards,id'],
            'cards.*.position' => ['required', 'integer', 'min:1'],
        ]);

        $deckCardIds = $deck->cards()->pluck('cards.id')->all();

        $requestedIds = collect($validated['cards'])->pluck('id')->all();
        foreach ($requestedIds as $cardId) {
            if (! in_array($cardId, $deckCardIds, true)) {
                return redirect()
                    ->route('decks.cards.reorder.form', $deck)
                    ->with('status', 'بعض البطاقات لا تنتمي لهذه المجموعة.');
            }
        }

        $sortedCards = collect($validated['cards'])
            ->sortBy('position')
            ->values();

        foreach ($sortedCards as $index => $cardData) {
            $deck->cards()->where('cards.id', $cardData['id'])->update([
                'position' => $index + 1,
            ]);
        }

        return redirect()
            ->route('decks.show', $deck)
            ->with('status', 'تم ترتيب البطاقات بنجاح.');
    }

    private function validateDeck(Request $request): array
    {
        return $request->validate([
            'level_id' => ['required', 'exists:levels,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
