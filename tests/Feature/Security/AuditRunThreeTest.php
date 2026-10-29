<?php

use App\Livewire\Clients\ClientsIndex;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

it('hides the legal or full name in the clients list without fiscal permission — client privacy', function () {
    Client::factory()->withFiscalData()->create(['name' => 'WIS', 'legal_name' => 'Maria da Silva']);
    $this->actingAs(userWithPermissions(['client.view']));

    Livewire::test(ClientsIndex::class)
        ->assertSee('WIS')
        ->assertDontSee('Maria da Silva');
});

it('keeps who made each change: an author cannot be deleted — RN-20', function () {
    $user = User::factory()->create();
    $log = new AuditLog;
    $log->forceFill(['actor_id' => $user->id, 'event' => 'client.rate_scheduled', 'subject_type' => 'client', 'subject_id' => 1])->save();

    expect(fn () => $user->delete())->toThrow(QueryException::class)
        ->and($log->fresh()->actor_id)->toBe($user->id);
});

it('does not offer self-service account deletion; people leave by deactivation — RN-19', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee(__('Delete account'), false);

    expect(fn () => Livewire::test('pages::settings.delete-user-modal'))->toThrow(Exception::class);
});

it('drops a pending two-factor challenge once the password changes — A-03', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));

    $user->forceFill(['password' => 'a-brand-new-password'])->save();

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1']);

    $this->assertGuest();
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
});

it('still completes the two-factor challenge while the password is unchanged', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1']);

    $this->assertAuthenticatedAs($user);
});
