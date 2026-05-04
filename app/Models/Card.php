<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Public URL for the back-face icon image under /uploads/… (no storage symlink needed).
     * Legacy files in storage/app/public are copied here on first view so old /storage URLs stop 404ing.
     */
    public function iconImageUrl(): ?string
    {
        if (! $this->icon_image_path) {
            return null;
        }

        $path = $this->icon_image_path;

        if (Storage::disk('card_uploads')->exists($path)) {
            return asset('uploads/'.$path);
        }

        if (Storage::disk('public')->exists($path)) {
            try {
                Storage::disk('card_uploads')->put(
                    $path,
                    Storage::disk('public')->get($path)
                );
                Storage::disk('public')->delete($path);
            } catch (\Throwable) {
                return Storage::disk('public')->url($path);
            }

            if (Storage::disk('card_uploads')->exists($path)) {
                return asset('uploads/'.$path);
            }

            return Storage::disk('public')->url($path);
        }

        return null;
    }
}
