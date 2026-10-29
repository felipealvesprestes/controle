<?php

use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Minutes;
use App\Domain\Shared\Money;

it('rejects a rate that is not positive — RN-04', function (int $cents) {
    HourlyRate::of(Money::fromCents($cents));
})->with([0, -100])->throws(InvalidArgumentException::class);

it('values a point at a quarter of the hourly rate — RN-05', function (int $rateCents, int $pointCents) {
    expect(HourlyRate::of(Money::fromCents($rateCents))->pointValue()->cents)->toBe($pointCents);
})->with([
    'R$ 60/h is R$ 15 per point' => [6000, 1500],
    'odd rate rounds half up' => [6002, 1501],
]);

it('prices minutes in cent-minutes for a single final rounding — RN-07', function () {
    $rate = HourlyRate::of(Money::fromCents(6000));

    expect($rate->centMinutesFor(Minutes::of(7)))->toBe(42000);
});

it('rejects a rate above R$ 100.000,00 per hour, which would not fit the rate columns', function () {
    expect(HourlyRate::of(Money::fromCents(HourlyRate::MAX_CENTS))->perHour->cents)->toBe(HourlyRate::MAX_CENTS);

    HourlyRate::of(Money::fromCents(HourlyRate::MAX_CENTS + 1));
})->throws(InvalidArgumentException::class, 'Hourly rate cannot exceed 10000000 cents, got 10000001.');
