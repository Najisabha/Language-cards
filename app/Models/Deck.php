<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Validation\ValidationException;

class Deck extends Model
{
    use HasFactory;

    public const CATEGORY_WORDS = 'الكلمات';

    public const CATEGORY_SENTENCES = 'الجمل';

    protected $fillable = ['level_id', 'name', 'description', 'color'];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('position');
    }

    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, Category::class);
    }

    public function scopeTopics(Builder $query): Builder
    {
        return $query->whereNotIn('name', [self::CATEGORY_WORDS, self::CATEGORY_SENTENCES]);
    }

    public function isLegacyWordSentenceDeck(): bool
    {
        return in_array($this->name, [self::CATEGORY_WORDS, self::CATEGORY_SENTENCES], true);
    }

    public function syncWordSentenceCategories(bool $includeWords, bool $includeSentences): void
    {
        if ($includeWords) {
            $this->categories()->firstOrCreate(
                ['name' => self::CATEGORY_WORDS],
                ['position' => 1, 'description' => null]
            );
        } else {
            $this->removeCategoryIfEmpty(self::CATEGORY_WORDS);
        }

        if ($includeSentences) {
            $this->categories()->firstOrCreate(
                ['name' => self::CATEGORY_SENTENCES],
                ['position' => 2, 'description' => null]
            );
        } else {
            $this->removeCategoryIfEmpty(self::CATEGORY_SENTENCES);
        }
    }

    private function removeCategoryIfEmpty(string $name): void
    {
        $category = $this->categories()->where('name', $name)->first();

        if (! $category) {
            return;
        }

        if ($category->cards()->exists()) {
            $field = $name === self::CATEGORY_WORDS ? 'include_words' : 'include_sentences';

            throw ValidationException::withMessages([
                $field => "لا يمكن إزالة «{$name}» لأنها تحتوي على بطاقات. احذف البطاقات أولًا.",
            ]);
        }

        $category->delete();
    }

    public function createCategories(array $definitions): void
    {
        foreach ($definitions as $category) {
            $this->categories()->firstOrCreate(
                ['name' => $category['name']],
                ['position' => $category['position'], 'description' => null]
            );
        }
    }

    public function ensureDefaultCategories(): void
    {
        $this->createCategories($this->defaultCategoryDefinitions());
    }

    /** @return list<array{name: string, position: int}> */
    public function defaultCategoryDefinitions(): array
    {
        return [
            ['name' => self::CATEGORY_WORDS, 'position' => 1],
            ['name' => self::CATEGORY_SENTENCES, 'position' => 2],
        ];
    }
}
