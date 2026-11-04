<?php

namespace App\Support;

use App\Models\Card;
use App\Models\Category;
use App\Models\MediaFolder;
use App\Models\MediaImage;
use Illuminate\Support\Collection;

class CardMediaFolderMatcher
{
    /**
     * Match category cards to images in a media folder by normalized names.
     *
     * @return array{matched: int, unchanged: int, unmatched_cards: list<string>, unused_images: list<string>}
     */
    public static function apply(Category $category, MediaFolder $folder): array
    {
        /** @var Collection<int, MediaImage> $images */
        $images = $folder->images()->orderBy('name')->get();

        /** @var array<string, MediaImage> $imagesByKey */
        $imagesByKey = [];
        foreach ($images as $image) {
            $key = self::normalizeKey((string) $image->name);
            if ($key === '' || isset($imagesByKey[$key])) {
                continue;
            }
            $imagesByKey[$key] = $image;
        }

        $cards = $category->cards()->orderBy('position')->get();
        $matched = 0;
        $unchanged = 0;
        $unmatchedCards = [];
        $usedImageIds = [];

        foreach ($cards as $card) {
            $image = self::findImageForCard($card, $imagesByKey);

            if (! $image) {
                $unmatchedCards[] = (string) $card->word;
                continue;
            }

            $usedImageIds[$image->id] = true;

            $alreadyLinked = (int) $card->media_image_id === (int) $image->id
                && (string) $card->icon_image_path === (string) $image->path
                && (bool) $card->show_icon;

            if ($alreadyLinked) {
                $unchanged++;
                continue;
            }

            // Linking alone is not enough: the back face only renders media when show_icon is on.
            $card->update([
                'media_image_id' => $image->id,
                'icon_image_path' => $image->path,
                'show_icon' => true,
            ]);
            $matched++;
        }

        $unusedImages = $images
            ->reject(fn (MediaImage $image) => isset($usedImageIds[$image->id]))
            ->map(fn (MediaImage $image) => $image->displayName())
            ->values()
            ->all();

        return [
            'matched' => $matched,
            'unchanged' => $unchanged,
            'unmatched_cards' => $unmatchedCards,
            'unused_images' => $unusedImages,
        ];
    }

    /**
     * @param  array<string, MediaImage>  $imagesByKey
     */
    private static function findImageForCard(Card $card, array $imagesByKey): ?MediaImage
    {
        $candidates = [
            (string) $card->word,
            (string) $card->en_meaning,
            (string) $card->ar_meaning,
        ];

        foreach ($candidates as $candidate) {
            $key = self::normalizeKey($candidate);
            if ($key !== '' && isset($imagesByKey[$key])) {
                return $imagesByKey[$key];
            }
        }

        return null;
    }

    public static function normalizeKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = pathinfo($value, PATHINFO_FILENAME) ?: $value;
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/[\s_\-.\x{00A0}]+/u', '', $value) ?? '';
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';

        return $value;
    }
}
