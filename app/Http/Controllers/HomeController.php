<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends a signed-in user to the first screen they may open (RN-15).
 */
final class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()?->can('dashboard.page')
            ? redirect()->route('dashboard')
            : redirect()->route('profile.edit');
    }
}
