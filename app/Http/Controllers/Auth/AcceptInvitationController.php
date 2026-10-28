<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Lets an invited user set their password with the single-use link (F-01).
 */
final class AcceptInvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        return view('pages::auth.accept-invitation', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);

        $credentials = [
            'token' => $request->string('token')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->string('password')->toString(),
            'password_confirmation' => $request->string('password_confirmation')->toString(),
        ];

        $status = Password::broker('invites')->reset($credentials, function (User $user, string $password): void {
            if (! $user->active) {
                return;
            }

            $user->forceFill([
                'password' => $password,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET || ! User::query()->where('email', $credentials['email'])->where('active', true)->exists()) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __('This invitation link is invalid or has expired.')]);
        }

        return redirect()->route('login')->with('status', __('Password set. You can sign in now.'));
    }
}
