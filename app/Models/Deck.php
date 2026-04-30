<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Deck extends Model
{
    use HasFactory;

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
}
