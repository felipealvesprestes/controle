<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
});

/**
 * Extracts the Livewire snapshot of the named component from a rendered page.
 *
 * @param  TestResponse<Response>  $page
 */
function livewireSnapshot(TestResponse $page, string $component): string
{
    preg_match_all('/wire:snapshot="([^"]+)"/', (string) $page->getContent(), $matches);

    foreach ($matches[1] as $encoded) {
        $snapshot = html_entity_decode($encoded, ENT_QUOTES);

        if ((json_decode($snapshot, true)['memo']['name'] ?? null) === $component) {
            return $snapshot;
        }
    }

    throw new RuntimeException("Snapshot for [{$component}] not found.");
}

/**
 * @param  array<string, mixed>  $session
 * @return TestResponse<Response>
 */
function callLivewireAction(User $user, string $snapshot, string $method, array $session): TestResponse
{
    return test()->actingAs($user)
        ->withSession($session)
        ->withHeaders(['X-Livewire' => 'true'])
        ->postJson(Livewire::getUpdateUri(), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [],
                'calls' => [['method' => $method, 'params' => [], 'metadata' => []]],
            ]],
        ]);
}

it('does not disable two-factor authentication once the password confirmation expired — A-01', function () {
    $user = User::factory()->withTwoFactor()->create();

    $page = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk();

    callLivewireAction($user, livewireSnapshot($page, 'pages::settings.security'), 'disable', [
        'auth.password_confirmed_at' => time() - config('auth.password_timeout') - 1,
    ]);

    expect($user->fresh()?->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

it('still disables two-factor authentication within the confirmation window — A-01', function () {
    $user = User::factory()->withTwoFactor()->create();

    $page = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk();

    callLivewireAction($user, livewireSnapshot($page, 'pages::settings.security'), 'disable', [
        'auth.password_confirmed_at' => time(),
    ])->assertOk();

    expect($user->fresh()?->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('does not regenerate recovery codes once the password confirmation expired — A-01', function () {
    $user = User::factory()->withTwoFactor()->create();
    $codesBefore = $user->two_factor_recovery_codes;

    $page = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk();

    callLivewireAction($user, livewireSnapshot($page, 'pages::settings.two-factor.recovery-codes'), 'regenerateRecoveryCodes', [
        'auth.password_confirmed_at' => time() - config('auth.password_timeout') - 1,
    ]);

    expect($user->fresh()?->two_factor_recovery_codes)->toBe($codesBefore);
});
