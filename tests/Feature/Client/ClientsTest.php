<?php

use App\Application\Client\ChangeClientStatus;
use App\Application\Client\CreateClient;
use App\Application\Client\DeleteClient;
use App\Application\Client\RemoveRate;
use App\Application\Client\ScheduleRate;
use App\Application\Client\UpdateClient;
use App\Domain\Access\AccessDenied;
use App\Domain\Client\ClientRuleViolation;
use App\Livewire\Clients\ClientShow;
use App\Livewire\Clients\ClientsIndex;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientRate;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * @return array{name: string, color: string, roundingPolicy: string, legalName: string, taxId: string, email: string, withholdsIss: bool, street: string, number: string, complement: string, district: string, city: string, state: string, zipCode: string}
 */
function clientInput(array $overrides = []): array
{
    return [
        'name' => 'WIS',
        'color' => 'green',
        'roundingPolicy' => '',
        'legalName' => 'WIS Tecnologia LTDA',
        'taxId' => '11.222.333/0001-81',
        'email' => 'Financeiro@Example.com',
        'withholdsIss' => true,
        'street' => 'Rua Exemplo',
        'number' => '100',
        'complement' => '',
        'district' => 'Centro',
        'city' => 'Canoas',
        'state' => 'rs',
        'zipCode' => '92010-000',
        ...$overrides,
    ];
}

function rateFor(Client $client, string $validFrom, int $cents): ClientRate
{
    $rate = new ClientRate;
    $rate->forceFill(['client_id' => $client->id, 'valid_from' => $validFrom, 'rate_cents' => $cents])->save();

    return $rate;
}

beforeEach(function () {
    $this->travelTo(new DateTimeImmutable('2026-10-29 12:00:00'));
    Http::fake(['viacep.com.br/*' => Http::response(['logradouro' => 'Avenida Victor Barreto', 'bairro' => 'Centro', 'localidade' => 'Canoas', 'uf' => 'RS'])]);
});

describe('screens', function () {
    it('shows the menu, the list and the detail to an administrator — F-03', function () {
        $this->actingAs(User::factory()->admin()->create());
        $client = Client::factory()->create(['name' => 'WIS']);

        $this->get(route('dashboard'))->assertSee(route('clients.index'));
        $this->get(route('clients.index'))->assertOk()->assertSee('WIS');
        $this->get(route('clients.show', $client))->assertOk()->assertSee('WIS')->assertSee(__('Rates'));
    });

    it('hides the menu and answers 403 without permission — RN-18', function () {
        $client = Client::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('clients.index'))->assertForbidden();
        $this->get(route('clients.show', $client))->assertForbidden();
        $this->get(route('profile.edit'))->assertDontSee(route('clients.index'));
    });

    it('answers 404 for a client that does not exist', function () {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('clients.show', 999))->assertNotFound();
    });

    it('marks the dashboard step once there is an active client', function () {
        $this->actingAs(User::factory()->admin()->create());
        Client::factory()->inactive()->create();

        $this->get(route('dashboard'))->assertViewHas('stepsDone', 1);

        Client::factory()->create();

        $this->get(route('dashboard'))->assertViewHas('stepsDone', 2);
    });
});

describe('list', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('shows the current rate, or the default when the client has none — RN-04', function () {
        $wis = Client::factory()->withFiscalData()->create(['name' => 'WIS']);
        rateFor($wis, '2026-08-01', 6000);
        rateFor($wis, '2026-10-01', 7500);
        rateFor($wis, '2026-12-01', 9000);
        Client::factory()->create(['name' => 'Beta']);

        Livewire::test(ClientsIndex::class)
            ->assertSeeInOrder(['Beta', __('Default'), 'R$ 60,00/h', 'WIS', '11.222.333/0001-81', 'R$ 75,00/h'])
            ->assertDontSee('R$ 90,00/h');
    });

    it('flags the clients whose fiscal data is incomplete', function () {
        Client::factory()->create(['name' => 'Beta']);

        Livewire::test(ClientsIndex::class)->assertSeeHtml('data-test="fiscal-incomplete"');
    });

    it('separates active and inactive clients — RN-13', function () {
        Client::factory()->create(['name' => 'Gamma']);
        Client::factory()->inactive()->create(['name' => 'Delta']);

        Livewire::test(ClientsIndex::class)
            ->assertSee('Gamma')->assertDontSee('Delta')
            ->set('status', 'inactive')
            ->assertSee('Delta')->assertDontSee('Gamma');
    });

    it('explains the empty lists', function () {
        Livewire::test(ClientsIndex::class)
            ->assertSee(__('No clients yet.'))
            ->set('status', 'inactive')
            ->assertSee(__('No inactive clients.'));
    });

    it('masks the CPF or CNPJ and the rate without permission — F-17', function () {
        $wis = Client::factory()->withFiscalData()->create(['name' => 'WIS']);
        rateFor($wis, '2026-08-01', 7500);
        $this->actingAs(userWithPermissions(['client.view']));

        Livewire::test(ClientsIndex::class)
            ->assertSee('WIS')
            ->assertDontSee('11.222.333/0001-81')
            ->assertDontSee('R$ 75,00')
            ->assertSee('•••')
            ->assertDontSee(__('New client'));
    });
});

describe('creating', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('creates a client with its first rate and opens it — F-03', function () {
        Livewire::test(ClientsIndex::class)
            ->assertSet('validFrom', '2026-10-29')
            ->set('name', '  WIS  ')
            ->set('color', 'amber')
            ->set('rate', '75,00')
            ->call('create')
            ->assertRedirect(route('clients.show', Client::query()->sole()));

        $client = Client::query()->sole();
        expect($client->name)->toBe('WIS')
            ->and($client->color)->toBe('amber')
            ->and($client->rates()->sole()->rate_cents)->toBe(7500)
            ->and(AuditLog::query()->where('subject_type', 'client')->pluck('event')->all())->toBe(['client.created', 'client.rate_scheduled']);
    });

    it('creates a client without a rate, which then uses the default — RN-04', function () {
        Livewire::test(ClientsIndex::class)->set('name', 'Beta')->call('create')->assertHasNoErrors();

        expect(Client::query()->sole()->rates()->count())->toBe(0);
    });

    it('refuses an invalid name, colour, rate or date', function (array $fields, string $error) {
        Client::factory()->create(['name' => 'WIS']);

        Livewire::test(ClientsIndex::class)
            ->set(['name' => 'Novo', ...$fields])
            ->call('create')
            ->assertHasErrors($error);

        expect(Client::query()->count())->toBe(1);
    })->with([
        'empty name' => [['name' => '  '], 'name'],
        'long name' => [['name' => str_repeat('a', 61)], 'name'],
        'name in use' => [['name' => 'WIS'], 'name'],
        'unknown colour' => [['color' => 'gold'], 'color'],
        'invalid rate' => [['rate' => 'abc'], 'rate'],
        'rate above the limit' => [['rate' => '100.000,01'], 'rate'],
        'invalid date' => [['rate' => '60', 'validFrom' => '2026-02-30'], 'validFrom'],
        'date out of range' => [['rate' => '60', 'validFrom' => '1999-12-31'], 'validFrom'],
    ]);
});

describe('editing', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('saves the details and the fiscal data with an audit entry — F-03', function () {
        $client = Client::factory()->create(['name' => 'Old']);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->set(clientInput(['roundingPolicy' => 'exact']))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('taxId', '11.222.333/0001-81')
            ->assertSet('email', 'financeiro@example.com')
            ->assertSet('state', 'RS')
            ->assertSee(__('Fiscal data complete'));

        $client->refresh();
        expect($client->name)->toBe('WIS')
            ->and($client->document)->toBe('11222333000181')
            ->and($client->rounding_policy)->toBe('exact')
            ->and($client->withholds_iss)->toBeTrue()
            ->and($client->address_zip_code)->toBe('92010000');

        $log = AuditLog::query()->where('event', 'client.updated')->sole();
        expect($log->before['name'])->toBe('Old')->and($log->after['name'])->toBe('WIS');
    });

    it('accepts a CPF for a person and warns that the CEP goes to ViaCEP — ADR-0013', function () {
        $client = Client::factory()->create();

        Livewire::test(ClientShow::class, ['client' => $client])
            ->set(clientInput(['taxId' => '52998224725', 'legalName' => 'Maria Silva']))
            ->call('save')
            ->assertSet('taxId', '529.982.247-25')
            ->assertSee(__('The CEP is sent to ViaCEP to fill the address.'));
    });

    it('goes back to the provider default when the policy is emptied — RN-06', function () {
        $client = Client::factory()->create(['rounding_policy' => 'exact']);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->assertSet('roundingPolicy', 'exact')
            ->set(clientInput(['name' => $client->name]))
            ->call('save');

        expect($client->refresh()->rounding_policy)->toBeNull();
    });

    it('lists what is missing for the invoice', function () {
        $client = Client::factory()->create();

        Livewire::test(ClientShow::class, ['client' => $client])
            ->assertSee(__('Fiscal data incomplete'))
            ->assertSee(__('client_fiscal.tax_id'));
    });

    it('refuses invalid details without saving', function (array $input, string $error) {
        Client::factory()->create(['name' => 'Other']);
        $client = Client::factory()->create(['name' => 'Mine']);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->set(clientInput($input))
            ->call('save')
            ->assertHasErrors($error);

        expect($client->refresh()->name)->toBe('Mine');
    })->with([
        'name of another client' => [['name' => 'Other'], 'name'],
        'unknown policy' => [['roundingPolicy' => 'daily'], 'roundingPolicy'],
        'long legal name' => [['legalName' => str_repeat('a', 256)], 'legalName'],
        'invalid CNPJ' => [['taxId' => '11.222.333/0001-82'], 'taxId'],
        'invalid CPF' => [['taxId' => '529.982.247-26'], 'taxId'],
        'invalid email' => [['email' => 'financeiro'], 'email'],
        'unknown state' => [['state' => 'XX', 'zipCode' => ''], 'state'],
        'short CEP' => [['zipCode' => '9201'], 'zipCode'],
        'unknown colour' => [['color' => 'gold'], 'color'],
    ]);

    it('keeps its own name when saving unchanged', function () {
        $client = Client::factory()->create(['name' => 'WIS']);

        Livewire::test(ClientShow::class, ['client' => $client])->set(clientInput())->call('save')->assertHasNoErrors();
    });

    it('fills the address from the CEP and leaves number and complement alone — ADR-0013', function () {
        $client = Client::factory()->create();

        Livewire::test(ClientShow::class, ['client' => $client])
            ->set('number', '42')
            ->set('zipCode', '92010000')
            ->assertSet('zipCode', '92010-000')
            ->assertSet('street', 'Avenida Victor Barreto')
            ->assertSet('city', 'Canoas')
            ->assertSet('number', '42');
    });

    it('refuses to save a client deleted in the meantime', function () {
        $client = Client::factory()->create();

        expect(fn () => app(UpdateClient::class)->handle(User::factory()->admin()->create()->id, $client->id + 1, clientInput()))
            ->toThrow(ClientRuleViolation::class, 'This client no longer exists.');
    });

    it('shows the history tab', function () {
        $client = Client::factory()->create();

        Livewire::test(ClientShow::class, ['client' => $client])
            ->set(clientInput())->call('save')
            ->set('tab', 'history')
            ->assertSee(__('audit.client.updated'));
    });
});

describe('rates', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('schedules a rate change and marks the current one — RN-04', function () {
        $client = Client::factory()->create();
        rateFor($client, '2026-08-01', 6000);

        $component = Livewire::test(ClientShow::class, ['client' => $client])
            ->assertSet('rateValidFrom', '2026-10-29')
            ->set('rateValue', '75')
            ->set('rateValidFrom', '2026-12-01')
            ->call('scheduleRate')
            ->assertHasNoErrors()
            ->assertSet('rateValue', '');

        // Newest first; the August rate is still the current one on 29/10.
        expect($component->html())->toMatch('#desde 01/12/2026.*R\$ 75,00/h.*desde 01/08/2026.*R\$ 60,00/h.*Vigente#s');

        expect($client->rates()->count())->toBe(2)
            ->and(AuditLog::query()->where('event', 'client.rate_scheduled')->sole()->after)->toEqual(['valid_from' => '2026-12-01', 'rate_cents' => 7500]);
    });

    it('refuses two rates starting on the same day — RN-04', function () {
        $client = Client::factory()->create();
        rateFor($client, '2026-11-01', 6000);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->set('rateValue', '75')
            ->set('rateValidFrom', '2026-11-01')
            ->call('scheduleRate')
            ->assertHasErrors('rateValidFrom');

        expect($client->rates()->count())->toBe(1);
    });

    it('refuses an invalid rate or day', function (string $value, string $day, string $error) {
        $client = Client::factory()->create();

        Livewire::test(ClientShow::class, ['client' => $client])
            ->set('rateValue', $value)
            ->set('rateValidFrom', $day)
            ->call('scheduleRate')
            ->assertHasErrors($error);
    })->with([
        ['0', '2026-11-01', 'rateValue'],
        ['60', '01/11/2026', 'rateValidFrom'],
    ]);

    it('shows the default while the client has no rate, or until its first one starts — RN-04', function () {
        $client = Client::factory()->create();

        Livewire::test(ClientShow::class, ['client' => $client])->assertSee(__('Default: :rate/h', ['rate' => 'R$ 60,00']));

        rateFor($client, '2026-12-01', 9000);

        Livewire::test(ClientShow::class, ['client' => $client])->assertSee(__('Until the first one starts: default of :rate/h', ['rate' => 'R$ 60,00']));
    });

    it('removes a rate typed by mistake', function () {
        $client = Client::factory()->create();
        $rate = rateFor($client, '2026-11-01', 6000);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->call('removeRate', $rate->id)
            ->assertHasNoErrors()
            ->call('removeRate', $rate->id)
            ->assertHasErrors('rates');

        expect($client->rates()->count())->toBe(0)
            ->and(AuditLog::query()->where('event', 'client.rate_removed')->sole()->before)->toEqual(['valid_from' => '2026-11-01', 'rate_cents' => 6000]);
    });

    it('does not remove a rate of another client through the id', function () {
        $client = Client::factory()->create();
        $other = rateFor(Client::factory()->create(), '2026-11-01', 6000);

        Livewire::test(ClientShow::class, ['client' => $client])->call('removeRate', $other->id)->assertHasErrors('rates');

        expect(ClientRate::query()->count())->toBe(1);
    });

    it('refuses a rate for a client deleted in the meantime', function () {
        expect(fn () => app(ScheduleRate::class)->handle(User::factory()->admin()->create()->id, 999, '60', '2026-11-01'))
            ->toThrow(ClientRuleViolation::class, 'This client no longer exists.');
    });
});

describe('status and deletion', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('deactivates and reactivates a client, keeping it — RN-13', function () {
        $client = Client::factory()->create();

        Livewire::test(ClientShow::class, ['client' => $client])
            ->call('setActive', false)
            ->assertSee(__('Reactivate'));

        expect($client->refresh()->active)->toBeFalse();

        Livewire::test(ClientShow::class, ['client' => $client])->call('setActive', true)->call('setActive', true);

        expect($client->refresh()->active)->toBeTrue()
            ->and(AuditLog::query()->where('subject_type', 'client')->pluck('event')->all())->toBe(['client.deactivated', 'client.reactivated']);
    });

    it('deletes a client with its rates — RN-13', function () {
        $client = Client::factory()->create();
        rateFor($client, '2026-11-01', 6000);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->call('delete')
            ->assertRedirect(route('clients.index'));

        expect(Client::query()->count())->toBe(0)
            ->and(ClientRate::query()->count())->toBe(0)
            ->and(AuditLog::query()->where('event', 'client.deleted')->sole()->before)->toBe(['name' => $client->name]);
    });

    it('answers 404 when the client was deleted in another tab', function () {
        $client = Client::factory()->create();
        $component = Livewire::test(ClientShow::class, ['client' => $client]);
        $client->delete();

        $component->call('delete')->assertNotFound();
    });

    it('refuses status changes and deletion of a missing client', function (Closure $action) {
        expect(fn () => $action(User::factory()->admin()->create()->id))->toThrow(ClientRuleViolation::class, 'This client no longer exists.');
    })->with([
        'status' => [fn (int $actorId) => app(ChangeClientStatus::class)->handle($actorId, 999, false)],
        'delete' => [fn (int $actorId) => app(DeleteClient::class)->handle($actorId, 999)],
    ]);
});

describe('permissions', function () {
    it('denies each use case without its permission — SEC-02', function (Closure $action) {
        $client = Client::factory()->create();
        $user = userWithPermissions(['client.view']);

        expect(fn () => $action($user->id, $client->id))->toThrow(AccessDenied::class);
    })->with([
        'create' => [fn (int $actorId) => app(CreateClient::class)->handle($actorId, 'Novo', 'petrol', '', '2026-10-29')],
        'update' => [fn (int $actorId, int $clientId) => app(UpdateClient::class)->handle($actorId, $clientId, clientInput())],
        'schedule rate' => [fn (int $actorId, int $clientId) => app(ScheduleRate::class)->handle($actorId, $clientId, '60', '2026-11-01')],
        'remove rate' => [fn (int $actorId, int $clientId) => app(RemoveRate::class)->handle($actorId, $clientId, 1)],
        'status' => [fn (int $actorId, int $clientId) => app(ChangeClientStatus::class)->handle($actorId, $clientId, false)],
        'delete' => [fn (int $actorId, int $clientId) => app(DeleteClient::class)->handle($actorId, $clientId)],
    ]);

    it('does not let a creator set the first rate without the rate permission', function () {
        $user = userWithPermissions(['client.create']);

        expect(fn () => app(CreateClient::class)->handle($user->id, 'Novo', 'petrol', '60', '2026-10-29'))->toThrow(AccessDenied::class);
        expect(Client::query()->count())->toBe(0);
    });

    it('keeps the fiscal data out of the page state without permission — F-17', function () {
        $client = Client::factory()->withFiscalData()->create();
        rateFor($client, '2026-08-01', 7500);
        $this->actingAs(userWithPermissions(['client.view']));

        Livewire::test(ClientShow::class, ['client' => $client])
            ->assertSet('taxId', '')
            ->assertSet('email', '')
            ->assertSet('street', '')
            ->assertDontSee('11.222.333/0001-81')
            ->assertDontSee('R$ 75,00')
            ->assertDontSeeHtml('data-test="save-client-button"')
            ->assertDontSeeHtml('data-test="delete-client-button"');
    });

    it('does not look the CEP up for who may not edit the client', function () {
        $client = Client::factory()->create();
        $this->actingAs(userWithPermissions(['client.fiscal.view']));

        Livewire::test(ClientShow::class, ['client' => $client])->set('zipCode', '92010000')->assertSet('street', '');

        Http::assertNothingSent();
    });
});
