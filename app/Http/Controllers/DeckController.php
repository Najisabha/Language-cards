<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\Language;
use App\Models\Level;
use App\Support\PrintLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeckController extends Controller
{
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

    public function show(Request $request, Deck $deck): View
    {
        $deck->load([
            'level.language',
            'categories' => fn ($q) => $q->with(['cards' => fn ($c) => $c->orderBy('position')])->withCount('cards'),
        ]);

        $allCards = $deck->categories->flatMap->cards->values();
        $q = trim((string) $request->query('q', ''));
        $cards = $allCards;

        if ($q !== '') {
            $tokens = preg_split('/\s+/u', mb_strtolower($q, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $cards = $allCards
                ->filter(function ($card) use ($tokens) {
                    $haystack = mb_strtolower(
                        trim(implode(' ', [
                            (string) $card->word,
                            (string) $card->en_meaning,
                            (string) $card->ar_meaning,
                            (string) $card->explanation,
                        ])),
                        'UTF-8'
                    );

                    foreach ($tokens as $token) {
                        if ($token === '') {
                            continue;
                        }
                        if (! str_contains($haystack, $token)) {
                            return false;
                        }
                    }

                    return true;
                })
                ->values();
        }

        return view('decks.show', [
            'deck' => $deck,
            'cards' => $cards,
            'q' => $q,
            'totalCardsCount' => $allCards->count(),
        ]);
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

        $validated = PrintLayout::validateQuery($request);
        $printSettings = PrintLayout::settingsFromQuery($validated);

        return view('decks.print', [
            'deck' => $deck,
            'printSettings' => $printSettings,
            'printScope' => 'deck',
        ]);
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
