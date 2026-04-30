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
}
