<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Deck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function create(Deck $deck): View
    {
        return view('categories.create', compact('deck'));
    }

    public function store(Request $request, Deck $deck): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['position'] = (int) ($deck->categories()->max('position') ?? 0) + 1;
        $deck->categories()->create($data);

        return redirect()->route('decks.show', $deck)->with('status', 'تم إنشاء التصنيف.');
    }

    public function edit(Category $category): View
    {
        $deck = $category->deck;
        return view('categories.edit', compact('category', 'deck'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category->update($data);

        return redirect()->route('decks.show', $category->deck_id)->with('status', 'تم تحديث التصنيف.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $deckId = $category->deck_id;
        $category->delete();
        return redirect()->route('decks.show', $deckId)->with('status', 'تم حذف التصنيف.');
    }
}
