<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('administrators can visit the dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows the billing rule computed from the spreadsheet october', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('R$ 1.335,00')
        ->assertSee('22h04')
        ->assertSee('22h15')
        ->assertSee('R$ 15,00');
});

test('dashboard setup counts only what is really done', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertViewHas('stepsDone', 1);

    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertViewHas('stepsDone', 2);
});

test('sidebar footer shows the configured version', function () {
    config(['app.version' => '9.8.7']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('v9.8.7');
});
