<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;
use App\Models\Deck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $data = $this->validateCard($request);
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
        $data = $this->validateCard($request);
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
        $data = $this->validateCard($request, $card);
        $data['category_id'] = $card->category_id;
        $data['deck_id'] = $card->deck_id;

        $card->update($data);

        return redirect()->route('decks.show', $card->deck_id)->with('status', 'تم تحديث البطاقة.');
    }

    public function destroy(Card $card): RedirectResponse
    {
        $deckId = $card->deck_id;

        if ($card->icon_image_path) {
            Storage::disk('public')->delete($card->icon_image_path);
        }

        $card->delete();

        return redirect()->route('decks.show', $deckId)->with('status', 'تم حذف البطاقة.');
    }

    private function validateCard(Request $request, ?Card $card = null): array
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'max:120'],
            'front_bg_type' => ['required', 'in:color,image'],
            'front_bg_value' => ['required', 'string', 'max:1000'],
            'en_meaning' => ['nullable', 'string', 'max:255'],
            'ar_meaning' => ['nullable', 'string', 'max:255'],
            'explanation' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:16'],
            'icon_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:2048'],
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

        return $data;
    }

    private function resolveIconImagePath(Request $request, ?Card $card): ?string
    {
        $existingPath = $card?->icon_image_path;

        if ($request->hasFile('icon_image')) {
            if ($existingPath) {
                Storage::disk('public')->delete($existingPath);
            }

            return $request->file('icon_image')->store('card-icons', 'public');
        }

        if ($request->boolean('remove_icon_image')) {
            if ($existingPath) {
                Storage::disk('public')->delete($existingPath);
            }
            return null;
        }

        return $existingPath;
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

        return '#ffffff';
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
