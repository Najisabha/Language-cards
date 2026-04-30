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
        return $this->hasMany(Deck::class)->orderBy('created_at', 'desc');
    }
}
