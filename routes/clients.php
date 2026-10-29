<?php

use App\Livewire\Clients\ClientShow;
use App\Livewire\Clients\ClientsIndex;
use Illuminate\Support\Facades\Route;

// Clients and rates (F-03).
Route::middleware(['auth', 'verified', 'can:client.view'])->group(function () {
    Route::livewire('clients', ClientsIndex::class)->name('clients.index');
    Route::livewire('clients/{client}', ClientShow::class)->name('clients.show');
});
