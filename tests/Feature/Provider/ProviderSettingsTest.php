<?php

use App\Application\Access\SaveRole;
use App\Application\Provider\UpdateBillingDefaults;
use App\Application\Provider\UpdateCompanyDetails;
use App\Domain\Access\AccessDenied;
use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Provider\ProviderProfileRepository;
use App\Livewire\Settings\BillingDefaultsSettings;
use App\Livewire\Settings\CompanyDetailsSettings;
use App\Models\AuditLog;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * A user whose role grants exactly the given permissions (dependencies included).
 *
 * @param  list<string>  $permissions
 */
function userWithPermissions(array $permissions): User
{
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $user->roles()->attach(app(SaveRole::class)->handle($admin->id, null, 'Role '.$user->id, '', $permissions));

    return $user;
}

/**
 * @return array{legalName: string, tradeName: string, cnpj: string, municipalRegistration: string, street: string, number: string, complement: string, district: string, city: string, state: string, zipCode: string}
 */
function companyInput(array $overrides = []): array
{
    return [
        'legalName' => 'Placedev Tecnologia LTDA',
        'tradeName' => 'Placedev',
        'cnpj' => '11.222.333/0001-81',
        'municipalRegistration' => '12345',
        'street' => 'Rua Exemplo',
        'number' => '100',
        'complement' => 'Sala 2',
        'district' => 'Centro',
        'city' => 'Canoas',
        'state' => 'rs',
        'zipCode' => '92010-000',
        ...$overrides,
    ];
}

// Filling the CEP looks the address up; these tests answer with the same address they type.
beforeEach(fn () => Http::fake(['viacep.com.br/*' => Http::response(['logradouro' => 'Rua Exemplo', 'bairro' => 'Centro', 'localidade' => 'Canoas', 'uf' => 'RS'])]));

describe('screens', function () {
    it('renders both settings pages and the menu for an administrator', function () {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('settings.billing.edit'))->assertOk()->assertSee('60,00')->assertSee('R$ 75,00');
        $this->get(route('settings.company.edit'))->assertOk()->assertSee(__('Legal name'));
        $this->get(route('dashboard'))->assertSee(route('settings.billing.edit'))->assertSee(route('settings.company.edit'));
    });

    it('hides the menu and answers 403 without permission — RN-18', function (string $route) {
        $this->actingAs(User::factory()->create());

        $this->get(route($route))->assertForbidden();
        $this->get(route('profile.edit'))->assertDontSee(route($route));
    })->with(['settings.billing.edit', 'settings.company.edit']);
});

describe('billing defaults', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('updates the example as the policy and the rate change — F-02', function () {
        Livewire::test(BillingDefaultsSettings::class)
            ->assertSet('rate', '60,00')
            ->assertSet('roundingPolicy', 'per_period')
            ->assertSeeInOrder(['1h07', '5 pontos', 'R$ 75,00'])
            ->set('roundingPolicy', 'exact')
            ->assertSeeInOrder(['1h07', '1h07', 'R$ 67,00'])
            ->set('rate', '120')
            ->assertSee('R$ 134,00')
            ->set('rate', 'abc')
            ->assertSee(__('Enter a valid rate to see the example.'))
            ->set('rate', '60')
            ->set('roundingPolicy', 'nope')
            ->assertSee(__('Enter a valid rate to see the example.'));
    });

    it('saves the defaults and records who changed them', function () {
        Livewire::test(BillingDefaultsSettings::class)
            ->set('rate', '1.250,5')
            ->set('roundingPolicy', 'per_entry')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('rate', '1.250,50');

        $defaults = app(ProviderProfileRepository::class)->billingDefaults();
        $log = AuditLog::query()->sole();

        expect($defaults->rate->perHour->cents)->toBe(125050)
            ->and($defaults->roundingPolicy)->toBe(RoundingPolicyType::PerEntry)
            ->and($log->event)->toBe('provider.billing_defaults.updated')
            ->and($log->before)->toBe(['rate_cents' => 6000, 'rounding_policy' => 'per_period'])
            ->and($log->after)->toBe(['rate_cents' => 125050, 'rounding_policy' => 'per_entry']);
    });

    it('refuses a rate of zero or a policy outside the list', function (string $rate, string $policy, string $field) {
        Livewire::test(BillingDefaultsSettings::class)
            ->set('rate', $rate)
            ->set('roundingPolicy', $policy)
            ->call('save')
            ->assertHasErrors($field);

        expect(ProviderProfile::query()->count())->toBe(0);
    })->with([
        'zero rate' => ['0,00', 'per_period', 'rate'],
        'text rate' => ['sessenta', 'per_period', 'rate'],
        'unknown policy' => ['60', 'monthly', 'roundingPolicy'],
    ]);
});

describe('company details', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('saves normalized company details, reloads them formatted and records the change', function () {
        Livewire::test(CompanyDetailsSettings::class)
            ->set(companyInput(['cnpj' => '12abc34501de35', 'zipCode' => '92010000']))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('cnpj', '12.ABC.345/01DE-35')
            ->assertSet('state', 'RS');

        Livewire::test(CompanyDetailsSettings::class)
            ->assertSet('legalName', 'Placedev Tecnologia LTDA')
            ->assertSet('cnpj', '12.ABC.345/01DE-35')
            ->assertSet('zipCode', '92010-000')
            ->assertSee(__('tax_regime.simples_me_epp'));

        expect(ProviderProfile::query()->sole()->cnpj)->toBe('12ABC34501DE35')
            ->and(AuditLog::query()->sole()->event)->toBe('provider.company_details.updated');
    });

    it('keeps the billing defaults when only the company changes, and the other way round', function () {
        Livewire::test(CompanyDetailsSettings::class)->set(companyInput())->call('save');
        Livewire::test(BillingDefaultsSettings::class)->set('rate', '80')->call('save');

        $profiles = app(ProviderProfileRepository::class);

        expect(ProviderProfile::query()->count())->toBe(1)
            ->and($profiles->billingDefaults()->rate->perHour->cents)->toBe(8000)
            ->and($profiles->companyDetails()->legalName)->toBe('Placedev Tecnologia LTDA');
    });

    it('shows the error next to the field', function (array $overrides, string $field) {
        Livewire::test(CompanyDetailsSettings::class)
            ->set(companyInput($overrides))
            ->call('save')
            ->assertHasErrors($field);

        expect(ProviderProfile::query()->count())->toBe(0);
    })->with([
        'missing legal name' => [['legalName' => '   '], 'legalName'],
        'invalid CNPJ check digits' => [['cnpj' => '11.222.333/0001-82'], 'cnpj'],
        'long trade name' => [['tradeName' => str_repeat('a', 256)], 'tradeName'],
        'missing municipal registration' => [['municipalRegistration' => ' '], 'municipalRegistration'],
        'municipal registration with symbols' => [['municipalRegistration' => '12<script>'], 'municipalRegistration'],
        'unknown state, typed without CEP' => [['state' => 'XX', 'zipCode' => ''], 'state'],
        'short CEP' => [['zipCode' => '9201'], 'zipCode'],
    ]);

    it('marks the dashboard step as done once the company is filled in', function () {
        $this->get(route('dashboard'))->assertViewHas('stepsDone', 1);

        app(UpdateCompanyDetails::class)->handle((int) auth()->id(), companyInput());

        $this->get(route('dashboard'))->assertViewHas('stepsDone', 2);
    });
});

describe('permissions', function () {
    it('masks the rate and keeps it out of the page for a user without rate.view — F-17', function () {
        $this->actingAs(userWithPermissions(['settings.billing.page']));

        $this->get(route('settings.billing.edit'))
            ->assertOk()
            ->assertSee('•••')
            ->assertDontSee('60,00')
            ->assertDontSee('R$ 75,00')
            ->assertDontSee(__('Save defaults'));

        Livewire::test(BillingDefaultsSettings::class)->assertSet('rate', '');
    });

    it('shows read-only screens and refuses saving without the edit action — SEC-02', function () {
        $this->actingAs(userWithPermissions(['settings.billing.rate.view', 'settings.company.page']));

        $this->get(route('settings.billing.edit'))->assertOk()->assertSee('60,00')->assertDontSee(__('Save defaults'));
        $this->get(route('settings.company.edit'))->assertOk()->assertDontSee(__('Save company details'));

        expect(fn () => Livewire::test(BillingDefaultsSettings::class)->set('rate', '1')->call('save'))->toThrow(AccessDenied::class)
            ->and(fn () => Livewire::test(CompanyDetailsSettings::class)->set(companyInput())->call('save'))->toThrow(AccessDenied::class)
            ->and(ProviderProfile::query()->count())->toBe(0);
    });

    it('checks the permission in the use case, not only on the screen — SEC-02', function (string $useCase) {
        $actorId = userWithPermissions(['settings.billing.page', 'settings.company.page'])->id;

        match ($useCase) {
            'billing' => app(UpdateBillingDefaults::class)->handle($actorId, '60', 'exact'),
            'company' => app(UpdateCompanyDetails::class)->handle($actorId, companyInput()),
        };
    })->throws(AccessDenied::class)->with(['billing', 'company']);

    it('lets a non-administrator with the edit actions save', function () {
        $this->actingAs(userWithPermissions(['settings.billing.edit', 'settings.company.edit']));

        Livewire::test(BillingDefaultsSettings::class)->set('rate', '70')->call('save')->assertHasNoErrors();
        Livewire::test(CompanyDetailsSettings::class)->set(companyInput())->call('save')->assertHasNoErrors();

        expect(app(ProviderProfileRepository::class)->billingDefaults()->rate->perHour->cents)->toBe(7000);
    });
});
