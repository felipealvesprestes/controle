<?php

use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('invitation/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.show');
    Route::post('invitation', [AcceptInvitationController::class, 'store'])->name('invitation.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('home', HomeController::class)->name('home.redirect');
    Route::get('dashboard', DashboardController::class)->middleware('can:dashboard.page')->name('dashboard');
});

require __DIR__.'/admin.php';

require __DIR__.'/clients.php';

require __DIR__.'/categories.php';

require __DIR__.'/settings.php';
