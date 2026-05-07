<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;
use App\Models\Deck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CardController extends Controller
{
    public function createForDeck(Deck $deck): View
    {
        $category = $this->defaultCategory($deck);

        return view('cards.create', compact('category', 'deck'));
    }

    public function storeForDeck(Request $request, Deck $deck): RedirectResponse
    {
        $category = $this->defaultCategory($deck);
        $data = $this->validateCard($request, null, $deck);
        $data['deck_id'] = $deck->id;
        $data['category_id'] = $category->id;
        $data['position'] = (int) ($deck->cards()->max('cards.position') ?? 0) + 1;

        $category->cards()->create($data);

        return redirect()->route('decks.show', $deck)->with('status', 'تمت إضافة البطاقة.');
    }

    public function create(Category $category): View
    {
        $deck = $category->deck;
        return view('cards.create', compact('category', 'deck'));
    }

    public function store(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validateCard($request, null, $category->deck);
        $data['deck_id'] = $category->deck_id;
        $data['category_id'] = $category->id;
        $data['position'] = (int) ($category->cards()->max('position') ?? 0) + 1;

        $category->cards()->create($data);

        return redirect()->route('decks.show', $category->deck_id)->with('status', 'تمت إضافة البطاقة.');
    }

    public function edit(Card $card): View
    {
        $card->load('category.deck');
        $category = $card->category;
        $deck = $category->deck;

        return view('cards.edit', compact('card', 'category', 'deck'));
    }

    public function update(Request $request, Card $card): RedirectResponse
    {
        $data = $this->validateCard($request, $card, $card->deck);
        $data['category_id'] = $card->category_id;
        $data['deck_id'] = $card->deck_id;

        $card->update($data);

        return redirect()->route('decks.show', $card->deck_id)->with('status', 'تم تحديث البطاقة.');
    }

    public function destroy(Card $card): RedirectResponse
    {
        $deckId = $card->deck_id;

        $this->deleteStoredIconImage($card->icon_image_path);

        $card->delete();

        return redirect()->route('decks.show', $deckId)->with('status', 'تم حذف البطاقة.');
    }

    public function checkWordDuplicate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'max:120'],
            'deck_id' => ['required', 'integer', 'exists:decks,id'],
            'card_id' => ['nullable', 'integer', 'exists:cards,id'],
        ]);

        $deck = Deck::query()->with('level')->find($validated['deck_id']);
        $card = isset($validated['card_id']) ? Card::find($validated['card_id']) : null;
        $duplicate = $this->hasDuplicateWordInLanguage($validated['word'], $deck, $card);

        return response()->json(['duplicate' => $duplicate]);
    }

    private function validateCard(Request $request, ?Card $card = null, ?Deck $deck = null): array
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'max:120'],
            'front_bg_type' => ['required', 'in:color,image'],
            'front_bg_value' => ['required', 'string', 'max:1000'],
            'en_meaning' => ['nullable', 'string', 'max:255'],
            'ar_meaning' => ['nullable', 'string', 'max:255'],
            'explanation' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:16'],
            'icon_image' => [
                'nullable',
                'file',
                'max:2048',
                'mimes:jpg,jpeg,png,webp,gif,svg',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/svg+xml',
            ],
            'remove_icon_image' => ['sometimes', 'boolean'],
            'show_en' => ['sometimes', 'boolean'],
            'show_ar' => ['sometimes', 'boolean'],
            'show_explanation' => ['sometimes', 'boolean'],
            'show_icon' => ['sometimes', 'boolean'],
        ]);

        $data = [
            'word' => $validated['word'],
            'front_bg_type' => $validated['front_bg_type'],
            'front_bg_value' => $this->sanitizeFrontBgValue($validated['front_bg_type'], $validated['front_bg_value']),
            'en_meaning' => $validated['en_meaning'] ?? null,
            'ar_meaning' => $validated['ar_meaning'] ?? null,
            'explanation' => $validated['explanation'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'show_en' => $request->boolean('show_en'),
            'show_ar' => $request->boolean('show_ar'),
            'show_explanation' => $request->boolean('show_explanation'),
            'show_icon' => $request->boolean('show_icon'),
        ];

        $data['icon_image_path'] = $this->resolveIconImagePath($request, $card);
        $this->ensureUniqueWordPerLanguage($data['word'], $deck, $card);

        return $data;
    }

    private function ensureUniqueWordPerLanguage(string $word, ?Deck $deck, ?Card $card = null): void
    {
        if ($this->hasDuplicateWordInLanguage($word, $deck, $card)) {
            throw ValidationException::withMessages([
                'word' => 'هذه الكلمة موجودة بالفعل في نفس اللغة. اختر كلمة مختلفة.',
            ]);
        }
    }

    private function hasDuplicateWordInLanguage(string $word, ?Deck $deck, ?Card $card = null): bool
    {
        $languageId = $deck?->level?->language_id ?? $deck?->level()->value('language_id');
        if (! $languageId) {
            return false;
        }

        $normalizedWord = mb_strtolower(trim($word), 'UTF-8');
        if ($normalizedWord === '') {
            return false;
        }

        $query = Card::query()
            ->select('cards.id')
            ->join('categories', 'categories.id', '=', 'cards.category_id')
            ->join('decks', 'decks.id', '=', 'categories.deck_id')
            ->join('levels', 'levels.id', '=', 'decks.level_id')
            ->where('levels.language_id', $languageId)
            ->whereRaw('LOWER(TRIM(cards.word)) = ?', [$normalizedWord]);

        if ($card) {
            $query->where('cards.id', '!=', $card->id);
        }

        return $query->exists();
    }

    private function resolveIconImagePath(Request $request, ?Card $card): ?string
    {
        $existingPath = $card?->icon_image_path;

        if ($request->hasFile('icon_image')) {
            if ($existingPath) {
                $this->deleteStoredIconImage($existingPath);
            }

            return $request->file('icon_image')->store('card-icons', 'card_uploads');
        }

        if ($request->boolean('remove_icon_image')) {
            if ($existingPath) {
                $this->deleteStoredIconImage($existingPath);
            }
            return null;
        }

        return $existingPath;
    }

    private function deleteStoredIconImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('card_uploads')->exists($path)) {
            Storage::disk('card_uploads')->delete($path);

            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function sanitizeFrontBgValue(string $type, string $value): string
    {
        $value = trim($value);

        if ($type === 'image') {
            return $value;
        }

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return $value;
        }

        if ($this->isSafeGradient($value)) {
            return $value;
        }

        if ($this->isSafeColorKeyword($value)) {
            return strtolower($value);
        }

        return '#ffffff';
    }

    private function isSafeColorKeyword(string $value): bool
    {
        $value = trim($value);

        if (! preg_match('/^[a-zA-Z]{3,25}$/', $value)) {
            return false;
        }

        $forbidden = ['url', 'expression', 'javascript', '<', '>', '"', "'"];
        foreach ($forbidden as $needle) {
            if (stripos($value, $needle) !== false) {
                return false;
            }
        }

        return true;
    }

    private function isSafeGradient(string $value): bool
    {
        if (! preg_match('/^(linear-gradient|radial-gradient|conic-gradient)\s*\(.+\)$/i', $value)) {
            return false;
        }

        $forbidden = ['url(', '<', '>', '"', "'", 'expression(', 'javascript:'];
        foreach ($forbidden as $needle) {
            if (stripos($value, $needle) !== false) {
                return false;
            }
        }

        return true;
    }

    private function defaultCategory(Deck $deck): Category
    {
        return $deck->categories()->firstOrCreate(
            ['position' => 1],
            [
                'name' => 'افتراضي',
                'description' => null,
            ]
        );
    }
}
