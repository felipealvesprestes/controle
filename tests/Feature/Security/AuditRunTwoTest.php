<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('checks one hash and builds none for an unknown email, like a known one — login timing', function () {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => 'warm-up@example.test', 'password' => 'wrong-password']);

    Hash::spy();
    $this->post(route('login.store'), ['email' => 'nobody@example.test', 'password' => 'wrong-password']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);

    Hash::shouldNotHaveReceived('make');
    Hash::shouldHaveReceived('check')->twice();
});

it('limits password resets per real client behind a trusted proxy — shared proxy IP', function () {
    config(['trustedproxy.proxies' => '10.0.0.2']);
    $fromProxy = fn (string $client) => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => $client]);

    foreach (range(1, 5) as $attempt) {
        $fromProxy('198.51.100.1')->post(route('password.email'), ['email' => 'a@example.test']);
    }

    $fromProxy('198.51.100.1')->post(route('password.email'), ['email' => 'a@example.test'])->assertStatus(429);
    $fromProxy('203.0.113.9')->post(route('password.email'), ['email' => 'b@example.test'])->assertRedirect();
});

it('ignores forwarded addresses from untrusted peers', function () {
    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.7', 'HTTP_X_FORWARDED_FOR' => "198.51.100.{$attempt}"])
            ->post(route('password.email'), ['email' => 'a@example.test']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.7', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9'])
        ->post(route('password.email'), ['email' => 'b@example.test'])
        ->assertStatus(429);
});

it('does not let the last active administrator delete their account — RN-17', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect($admin->fresh())->not->toBeNull();
});

it('lets an administrator delete their account while another one remains', function () {
    User::factory()->admin()->create();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors();

    expect($admin->fresh())->toBeNull();
});
