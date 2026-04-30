<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
