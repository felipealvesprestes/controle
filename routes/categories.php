<?php

use App\Livewire\Categories\CategoriesIndex;
use Illuminate\Support\Facades\Route;

// Categories (F-04).
Route::middleware(['auth', 'verified', 'can:category.view'])->group(function () {
    Route::livewire('categories', CategoriesIndex::class)->name('categories.index');
});
