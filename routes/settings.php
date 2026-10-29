<?php

use App\Http\Middleware\RequireRecentPasswordConfirmation;
use App\Livewire\Settings\BillingDefaultsSettings;
use App\Livewire\Settings\CompanyDetailsSettings;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            RequireRecentPasswordConfirmation::class,
        ])
        ->name('security.edit');

    // Provider settings (F-02).
    Route::livewire('settings/billing', BillingDefaultsSettings::class)->middleware('can:settings.billing.page')->name('settings.billing.edit');
    Route::livewire('settings/company', CompanyDetailsSettings::class)->middleware('can:settings.company.page')->name('settings.company.edit');
});
