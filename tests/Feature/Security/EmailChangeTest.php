<?php

use App\Models\User;
use Livewire\Livewire;

it('does not change the email without the current password — A-02', function () {
    $user = User::factory()->create(['email' => 'owner@example.test']);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('email', 'attacker@example.test')
        ->call('updateProfileInformation')
        ->assertHasErrors(['current_password' => 'required']);

    expect($user->fresh()?->email)->toBe('owner@example.test');
});

it('does not change the email with a wrong current password — A-02', function () {
    $user = User::factory()->create(['email' => 'owner@example.test']);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('email', 'attacker@example.test')
        ->set('current_password', 'wrong-password')
        ->call('updateProfileInformation')
        ->assertHasErrors(['current_password' => 'current_password']);

    expect($user->fresh()?->email)->toBe('owner@example.test');
});

it('changes the email with the current password and stores it in lower case — A-02', function () {
    $user = User::factory()->create(['email' => 'owner@example.test']);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('email', ' New@Example.TEST ')
        ->set('current_password', 'password')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertSet('current_password', '');

    expect($user->fresh()?->email)->toBe('new@example.test');
});

it('changes only the name without asking for the password — A-02', function () {
    $user = User::factory()->create(['name' => 'Old', 'email' => 'owner@example.test']);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('name', 'New Name')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()?->name)->toBe('New Name');
});
