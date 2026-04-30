<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'color', 'position'];

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class)->orderBy('position')->orderBy('name');
    }
}
