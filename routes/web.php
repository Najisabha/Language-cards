<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CardAiIconImageController;
use App\Http\Controllers\CardAiSuggestController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LevelController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('languages.index'));

Route::get('login', [AdminAuthController::class, 'create'])->name('login');
Route::post('login', [AdminAuthController::class, 'store'])->name('login.store');
Route::post('logout', [AdminAuthController::class, 'destroy'])->name('logout')->middleware('admin');

Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
Route::get('languages/{language}', [LanguageController::class, 'show'])
    ->whereNumber('language')
    ->name('languages.show');

Route::get('levels', [LevelController::class, 'index'])->name('levels.index');
Route::get('levels/{level}', [LevelController::class, 'show'])
    ->whereNumber('level')
    ->name('levels.show');
Route::get('levels/{level}/print/options', [LevelController::class, 'printOptions'])
    ->whereNumber('level')
    ->name('levels.print.options');
Route::get('levels/{level}/print', [LevelController::class, 'print'])
    ->whereNumber('level')
    ->name('levels.print');

Route::get('decks', [DeckController::class, 'index'])->name('decks.index');
Route::get('decks/{deck}', [DeckController::class, 'show'])
    ->whereNumber('deck')
    ->name('decks.show');
Route::get('decks/{deck}/print/options', [DeckController::class, 'printOptions'])
    ->whereNumber('deck')
    ->name('decks.print.options');
Route::get('decks/{deck}/print', [DeckController::class, 'print'])
    ->whereNumber('deck')
    ->name('decks.print');

Route::get('categories/{category}', [CategoryController::class, 'show'])
    ->whereNumber('category')
    ->name('categories.show');

Route::middleware('admin')->group(function (): void {
    Route::post('cards/ai-suggest', CardAiSuggestController::class)
        ->middleware('throttle:20,1')
        ->name('cards.ai-suggest');

    Route::post('cards/ai-icon-image', CardAiIconImageController::class)
        ->middleware('throttle:8,1')
        ->name('cards.ai-icon-image');

    Route::post('cards/check-duplicate-word', [CardController::class, 'checkWordDuplicate'])
        ->middleware('throttle:30,1')
        ->name('cards.check-duplicate-word');

    Route::resource('languages', LanguageController::class)->except(['index', 'show']);
    Route::resource('levels', LevelController::class)->except(['index', 'show']);
    Route::resource('decks', DeckController::class)->except(['index', 'show']);

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
});
