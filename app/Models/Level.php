<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasFactory;

    protected $fillable = ['language_id', 'name', 'title', 'position'];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function decks(): HasMany
    {
        return $this->hasMany(Deck::class)->orderBy('id');
    }

    public function topicDecks(): HasMany
    {
        return $this->hasMany(Deck::class)->topics()->orderBy('id');
    }

    public function migrateLegacyWordSentenceDecks(): void
    {
        $legacyDecks = $this->decks()
            ->whereIn('name', [Deck::CATEGORY_WORDS, Deck::CATEGORY_SENTENCES])
            ->get();

        if ($legacyDecks->isEmpty()) {
            return;
        }

        $targetTopic = $this->decks()->topics()->orderBy('id')->first();

        if (! $targetTopic) {
            return;
        }

        $targetTopic->ensureDefaultCategories();

        foreach ($legacyDecks as $legacyDeck) {
            $category = $targetTopic->categories()->where('name', $legacyDeck->name)->first();

            if ($category) {
                Card::query()
                    ->where('deck_id', $legacyDeck->id)
                    ->update([
                        'deck_id' => $targetTopic->id,
                        'category_id' => $category->id,
                    ]);
            }

            $legacyDeck->categories()->delete();
            $legacyDeck->delete();
        }
    }
}
