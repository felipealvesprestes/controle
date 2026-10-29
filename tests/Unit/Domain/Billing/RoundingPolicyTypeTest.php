<?php

use App\Domain\Billing\Rounding\ExactMinutes;
use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Billing\Rounding\RoundUpPerEntry;
use App\Domain\Billing\Rounding\RoundUpPerPeriod;

it('maps each stored policy to its rounding rule — RN-06', function (RoundingPolicyType $type, string $class) {
    expect($type->policy())->toBeInstanceOf($class);
})->with([
    [RoundingPolicyType::PerPeriod, RoundUpPerPeriod::class],
    [RoundingPolicyType::PerEntry, RoundUpPerEntry::class],
    [RoundingPolicyType::Exact, ExactMinutes::class],
]);
