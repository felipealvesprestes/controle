<?php

use App\Http\Middleware\RequireRecentPasswordConfirmation;
use App\Livewire\Admin\RoleShow;
use App\Livewire\Admin\RolesIndex;
use App\Livewire\Admin\UserShow;
use App\Livewire\Admin\UsersIndex;
use Illuminate\Support\Facades\Route;

// Access administration (F-17): administrators only, with a recent password confirmation (SEC-34).
Route::middleware(['auth', 'verified', 'can:access.manage', RequireRecentPasswordConfirmation::class])
    ->prefix('admin')
    ->group(function () {
        Route::livewire('users', UsersIndex::class)->name('admin.users.index');
        Route::livewire('users/{user}', UserShow::class)->name('admin.users.show');
        Route::livewire('roles', RolesIndex::class)->name('admin.roles.index');
        Route::livewire('roles/{role}', RoleShow::class)->name('admin.roles.show');
    });
