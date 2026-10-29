<?php

use App\Domain\Client\ClientRate;
use App\Domain\Client\RateSchedule;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Money;

function rateFrom(int $id, string $day, int $cents): ClientRate
{
    return new ClientRate($id, new DateTimeImmutable($day), HourlyRate::of(Money::fromCents($cents)));
}

it('resolves the rate valid on a day: the latest one that already started — RN-04', function (string $day, ?int $expectedId) {
    $schedule = new RateSchedule([rateFrom(1, '2026-08-01', 6000), rateFrom(2, '2026-11-01', 7000)]);

    expect($schedule->on(new DateTimeImmutable($day))?->id)->toBe($expectedId);
})->with([
    'before the first rate uses the default' => ['2026-07-31', null],
    'on the first day' => ['2026-08-01', 1],
    'between the two' => ['2026-10-31', 1],
    'on the change day' => ['2026-11-01', 2],
    'after the change' => ['2027-01-15', 2],
]);

it('lists the newest rate first', function () {
    $schedule = new RateSchedule([rateFrom(1, '2026-08-01', 6000), rateFrom(2, '2026-11-01', 7000)]);

    expect(array_map(fn (ClientRate $rate) => $rate->id, $schedule->rates))->toBe([2, 1]);
});

it('finds a rate by id', function () {
    $schedule = new RateSchedule([rateFrom(1, '2026-08-01', 6000)]);

    expect($schedule->find(1)?->rate->perHour->cents)->toBe(6000)
        ->and($schedule->find(2))->toBeNull()
        ->and($schedule->find(1)?->toArray())->toBe(['valid_from' => '2026-08-01', 'rate_cents' => 6000]);
});
