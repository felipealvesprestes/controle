<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('ends a session after the password is changed elsewhere — A-03', function () {
    $user = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('home.redirect', absolute: false));

    $this->get(route('dashboard'))->assertOk();

    $user->forceFill(['password' => Hash::make('a-new-password')])->save();

    // A new HTTP request starts with fresh guards; the test kernel would otherwise reuse the cached user.
    app('auth')->forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('keeps the session when the password did not change — A-03', function () {
    $user = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('dashboard'))->assertOk();
});
