<?php

use App\Livewire\Time\TimeEntriesIndex;
use App\Livewire\Time\Today;
use Illuminate\Support\Facades\Route;

// Today and time entries (F-05, F-06).
Route::middleware(['auth', 'verified', 'can:time.entry.view'])->group(function () {
    Route::livewire('today', Today::class)->name('time.today');
    Route::livewire('time-entries', TimeEntriesIndex::class)->name('time-entries.index');
});
