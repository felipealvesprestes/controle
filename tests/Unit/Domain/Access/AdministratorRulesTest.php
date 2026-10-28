<?php

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\Account;
use App\Domain\Access\AdministratorRules;

function rulesWith(array $accounts, int $activeAdmins = 2): AdministratorRules
{
    $repository = Mockery::mock(AccessRepository::class);
    $repository->allows('account')->andReturnUsing(fn (int $id) => $accounts[$id] ?? null);
    $repository->allows('activeAdministratorCount')->andReturn($activeAdmins);

    return new AdministratorRules($repository);
}

it('accepts only active administrators as actors — RN-17', function (?Account $account) {
    rulesWith(array_filter([1 => $account]))->actor(1);
})->with([
    'missing' => [null],
    'regular user' => [new Account(1, 'Ana', 'ana@example.test', false, true)],
    'inactive admin' => [new Account(1, 'Ana', 'ana@example.test', true, false)],
])->throws(AccessDenied::class);

it('refuses an unknown target', function () {
    rulesWith([])->target(9);
})->throws(AccessRuleViolation::class, 'This user does not exist.');

it('never lets the actor remove their own administrator access — RN-17', function () {
    $actor = new Account(1, 'Ana', 'ana@example.test', true, true);

    rulesWith([1 => $actor])->guardAdministratorLoss($actor, $actor);
})->throws(AccessRuleViolation::class, 'You cannot remove your own administrator access.');

it('keeps the last active administrator, even after a concurrent change — RN-17', function () {
    $actor = new Account(1, 'Ana', 'ana@example.test', true, true);
    $other = new Account(2, 'Bia', 'bia@example.test', true, true);

    rulesWith([1 => $actor, 2 => $other], activeAdmins: 1)->guardAdministratorLoss($actor, $other);
})->throws(AccessRuleViolation::class, 'The system must keep at least one active administrator.');

it('lets one of several administrators be removed', function () {
    $actor = new Account(1, 'Ana', 'ana@example.test', true, true);
    $other = new Account(2, 'Bia', 'bia@example.test', true, true);
    $rules = rulesWith([1 => $actor, 2 => $other]);

    $rules->guardAdministratorLoss($actor, $other);

    expect($rules->actor(1))->toBe($actor)->and($rules->target(2))->toBe($other);
});
