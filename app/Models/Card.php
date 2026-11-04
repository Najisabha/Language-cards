<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'deck_id',
        'category_id',
        'word',
        'front_bg_type',
        'front_bg_value',
        'en_meaning',
        'ar_meaning',
        'explanation',
        'icon',
        'icon_image_path',
        'media_image_id',
        'show_en',
        'show_ar',
        'show_explanation',
        'show_icon',
        'position',
    ];

    protected $casts = [
        'show_en' => 'boolean',
        'show_ar' => 'boolean',
        'show_explanation' => 'boolean',
        'show_icon' => 'boolean',
        'position' => 'integer',
    ];

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function mediaImage(): BelongsTo
    {
        return $this->belongsTo(MediaImage::class);
    }

    /**
     * Public URL for the back-face icon image under /uploads/… (no storage symlink needed).
     */
    public function iconImageUrl(): ?string
    {
        $media = $this->mediaImage;
        if ($media) {
            return $media->url();
        }

        // Legacy fallback while old rows may still only have icon_image_path.
        if (! $this->icon_image_path) {
            return null;
        }

        return (new MediaImage(['path' => $this->icon_image_path]))->url();
    }

    /**
     * Emoji/text icon only — ignores truncated image URLs stored in the icon column by mistake.
     */
    public function iconEmoji(): ?string
    {
        $icon = trim((string) $this->icon);
        if ($icon === '') {
            return null;
        }

        if (str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/')) {
            return null;
        }

        return $icon;
    }
}
