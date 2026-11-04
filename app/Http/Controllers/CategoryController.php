<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Deck;
use App\Models\MediaFolder;
use App\Support\CardMediaFolderMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category): View
    {
        $category->load([
            'deck.level.language',
            'cards' => fn ($q) => $q->orderBy('position')->with('mediaImage'),
        ]);

        $deck = $category->deck;
        $q = trim((string) $request->query('q', ''));
        $cards = $category->cards;

        if ($q !== '') {
            $tokens = preg_split('/\s+/u', mb_strtolower($q, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $cards = $cards
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

        return view('categories.show', [
            'category' => $category,
            'deck' => $deck,
            'cards' => $cards,
            'q' => $q,
            'totalCardsCount' => $category->cards->count(),
        ]);
    }

    public function create(Deck $deck): RedirectResponse
    {
        return redirect()
            ->route('decks.show', $deck)
            ->with('status', 'التصنيفان «الكلمات» و«الجمل» يُنشآن تلقائيًا لكل نوع.');
    }

    public function store(Request $request, Deck $deck): RedirectResponse
    {
        return redirect()
            ->route('decks.show', $deck)
            ->with('status', 'لا يمكن إضافة تصنيفات مخصصة. استخدم «الكلمات» أو «الجمل».');
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

        if (in_array($category->name, [Deck::CATEGORY_WORDS, Deck::CATEGORY_SENTENCES], true)
            && $data['name'] !== $category->name) {
            return redirect()
                ->route('categories.show', $category)
                ->with('status', 'لا يمكن تغيير اسم التصنيف الافتراضي.');
        }

        $category->update($data);

        return redirect()->route('categories.show', $category)->with('status', 'تم تحديث التصنيف.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if (in_array($category->name, [Deck::CATEGORY_WORDS, Deck::CATEGORY_SENTENCES], true)) {
            return redirect()
                ->route('decks.show', $category->deck_id)
                ->with('status', 'لا يمكن حذف تصنيف «الكلمات» أو «الجمل».');
        }

        $deckId = $category->deck_id;
        $category->delete();

        return redirect()->route('decks.show', $deckId)->with('status', 'تم حذف التصنيف.');
    }

    public function matchMediaFolder(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
            'folder_name' => ['nullable', 'string', 'max:120'],
        ]);

        $folder = null;
        if (! empty($validated['folder_id'])) {
            $folder = MediaFolder::query()->find((int) $validated['folder_id']);
        }

        $folderName = trim((string) ($validated['folder_name'] ?? ''));
        if (! $folder && $folderName !== '') {
            $folder = MediaFolder::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($folderName, 'UTF-8')])
                ->first();
        }

        if (! $folder) {
            return redirect()
                ->route('categories.show', $category)
                ->with('status', 'لم يتم العثور على مجلد بهذا الاسم في مكتبة الصور.');
        }

        if ($folder->images()->count() === 0) {
            return redirect()
                ->route('categories.show', $category)
                ->with('status', 'المجلد «'.$folder->name.'» فارغ ولا يحتوي صورًا.');
        }

        $result = CardMediaFolderMatcher::apply($category, $folder);

        $parts = [
            'تم الربط من المجلد «'.$folder->name.'»: '.$result['matched'].' بطاقة.',
        ];

        if ($result['unchanged'] > 0) {
            $parts[] = $result['unchanged'].' كانت مربوطة مسبقًا.';
        }

        if ($result['unmatched_cards'] !== []) {
            $sample = array_slice($result['unmatched_cards'], 0, 8);
            $extra = count($result['unmatched_cards']) - count($sample);
            $parts[] = 'بدون صورة: '.implode('، ', $sample).($extra > 0 ? '… (+'.$extra.')' : '').'.';
        }

        return redirect()
            ->route('categories.show', $category)
            ->with('status', implode(' ', $parts));
    }
}
