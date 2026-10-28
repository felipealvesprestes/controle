<?php

use App\Domain\Access\Effect;
use App\Domain\Access\Grants;
use App\Domain\Access\Permission;
use App\Domain\Access\PermissionCatalog;
use App\Domain\Access\PermissionProvider;
use App\Domain\Access\PermissionResolver;
use App\Domain\Access\PermissionType;
use App\Domain\Access\Reason;

/**
 * @param  list<Permission>  $permissions
 */
function catalogOf(array $permissions, string $module = 'Billing'): PermissionCatalog
{
    return new PermissionCatalog([new class($module, $permissions) implements PermissionProvider
    {
        /** @param list<Permission> $permissions */
        public function __construct(private string $name, private array $permissions) {}

        public function module(): string
        {
            return $this->name;
        }

        public function permissions(): array
        {
            return $this->permissions;
        }
    }]);
}

function billingCatalog(): PermissionCatalog
{
    return catalogOf([
        new Permission('billing.menu', PermissionType::Menu, 'Menu', 'T-05'),
        new Permission('billing.period.view', PermissionType::Page, 'View', 'T-06', ['billing.menu']),
        new Permission('billing.period.close', PermissionType::Action, 'Close', 'T-06', ['billing.period.view']),
        new Permission('billing.amount.view', PermissionType::Content, 'Amounts', 'T-06'),
    ]);
}

it('rejects malformed keys and self dependencies', function (string $key, array $dependsOn) {
    new Permission($key, PermissionType::Action, 'Label', 'T-01', $dependsOn);
})->with([
    'no module' => ['close', []],
    'uppercase' => ['Billing.close', []],
    'self' => ['billing.close', ['billing.close']],
])->throws(InvalidArgumentException::class);

it('refuses duplicate keys and unknown dependencies', function (array $permissions) {
    catalogOf($permissions);
})->with([
    'duplicate' => [[new Permission('a.b', PermissionType::Menu, 'A', 'T-1'), new Permission('a.b', PermissionType::Page, 'B', 'T-1')]],
    'unknown dependency' => [[new Permission('a.b', PermissionType::Page, 'A', 'T-1', ['a.missing'])]],
])->throws(InvalidArgumentException::class);

it('lists keys by module and resolves dependencies transitively', function () {
    $catalog = billingCatalog();

    expect($catalog->has('billing.menu'))->toBeTrue()
        ->and($catalog->has('billing.nope'))->toBeFalse()
        ->and($catalog->keys())->toHaveCount(4)
        ->and(array_keys($catalog->byModule()))->toBe(['Billing'])
        ->and($catalog->withDependencies(['billing.period.close', 'billing.menu']))
        ->toEqualCanonicalizing(['billing.period.close', 'billing.period.view', 'billing.menu']);
});

it('fails on an unknown permission', function () {
    billingCatalog()->get('billing.nope');
})->throws(InvalidArgumentException::class);

describe('resolver — RN-15, RN-16', function () {
    $decide = fn (Grants $grants): array => (new PermissionResolver(billingCatalog()))->explain($grants);

    it('denies everything by default', function () use ($decide) {
        expect(collect($decide(new Grants(false, true)))->every(fn ($d) => ! $d->allowed && $d->reason === Reason::NotGranted))->toBeTrue();
    });

    it('denies everything to an inactive user, even an administrator — RN-19', function () use ($decide) {
        expect($decide(new Grants(true, false))['billing.menu']->reason)->toBe(Reason::Inactive);
    });

    it('allows everything to an active administrator — RN-17', function () use ($decide) {
        expect(collect($decide(new Grants(true, true)))->every(fn ($d) => $d->allowed && $d->reason === Reason::Administrator))->toBeTrue();
    });

    it('applies user denial before user allowance before roles', function () use ($decide) {
        $decisions = $decide(new Grants(false, true,
            ['billing.menu' => ['Staff'], 'billing.period.view' => ['Staff'], 'billing.amount.view' => ['Staff']],
            ['billing.amount.view' => Effect::Deny, 'billing.period.close' => Effect::Allow],
        ));

        expect($decisions['billing.menu']->reason)->toBe(Reason::Role)
            ->and($decisions['billing.menu']->roles)->toBe(['Staff'])
            ->and($decisions['billing.amount.view']->reason)->toBe(Reason::DeniedForUser)
            ->and($decisions['billing.period.close']->reason)->toBe(Reason::AllowedForUser)
            ->and($decisions['billing.period.close']->allowed)->toBeTrue();
    });

    it('drops a permission whose dependency is missing, transitively', function () use ($decide) {
        $decisions = $decide(new Grants(false, true,
            ['billing.period.view' => ['Staff'], 'billing.period.close' => ['Staff']],
            ['billing.menu' => Effect::Deny],
        ));

        expect($decisions['billing.period.view']->reason)->toBe(Reason::MissingDependency)
            ->and($decisions['billing.period.close']->reason)->toBe(Reason::MissingDependency);
    });

    it('lists only allowed keys', function () {
        $keys = (new PermissionResolver(billingCatalog()))->allowedKeys(new Grants(false, true, ['billing.amount.view' => ['Staff']]));

        expect($keys)->toBe(['billing.amount.view']);
    });
});
