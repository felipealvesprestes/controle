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
        $user = $request->user();

        return match (true) {
            (bool) $user?->can('time.entry.view') => redirect()->route('time.today'),
            (bool) $user?->can('dashboard.page') => redirect()->route('dashboard'),
            default => redirect()->route('profile.edit'),
        };
    }
}
