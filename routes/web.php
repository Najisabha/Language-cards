<?php

use App\Http\Controllers\CardAiIconImageController;
use App\Http\Controllers\CardAiSuggestController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LevelController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('languages.index'));

Route::post('cards/ai-suggest', CardAiSuggestController::class)
    ->middleware('throttle:20,1')
    ->name('cards.ai-suggest');

Route::post('cards/ai-icon-image', CardAiIconImageController::class)
    ->middleware('throttle:8,1')
    ->name('cards.ai-icon-image');

Route::resource('languages', LanguageController::class);
Route::get('levels/{level}/print/options', [LevelController::class, 'printOptions'])->name('levels.print.options');
Route::get('levels/{level}/print', [LevelController::class, 'print'])->name('levels.print');
Route::resource('levels', LevelController::class);
Route::resource('decks', DeckController::class);
Route::get('decks/{deck}/print/options', [DeckController::class, 'printOptions'])->name('decks.print.options');
Route::get('decks/{deck}/print', [DeckController::class, 'print'])->name('decks.print');
Route::get('decks/{deck}/cards/reorder', [DeckController::class, 'reorderCardsForm'])->name('decks.cards.reorder.form');
Route::post('decks/{deck}/cards/reorder', [DeckController::class, 'reorderCards'])->name('decks.cards.reorder');
Route::get('decks/{deck}/cards/create', [CardController::class, 'createForDeck'])->name('decks.cards.create');
Route::post('decks/{deck}/cards', [CardController::class, 'storeForDeck'])->name('decks.cards.store');

Route::resource('decks.categories', CategoryController::class)
    ->shallow()
    ->except(['index', 'show']);

Route::resource('categories.cards', CardController::class)
    ->shallow()
    ->except(['index', 'show']);
