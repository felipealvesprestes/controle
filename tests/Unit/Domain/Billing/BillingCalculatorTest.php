<?php

use App\Domain\Billing\BillableEntry;
use App\Domain\Billing\BillingCalculator;
use App\Domain\Billing\Rounding\ExactMinutes;
use App\Domain\Billing\Rounding\RoundUpPerEntry;
use App\Domain\Billing\Rounding\RoundUpPerPeriod;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Minutes;
use App\Domain\Shared\Money;

function billingRate(int $cents): HourlyRate
{
    return HourlyRate::of(Money::fromCents($cents));
}

/**
 * @param  list<int>  $minutes
 * @return list<BillableEntry>
 */
function billableEntries(array $minutes, int $rateCents = 6000): array
{
    return array_map(fn (int $value): BillableEntry => new BillableEntry(Minutes::of($value), billingRate($rateCents)), $minutes);
}

describe('round up per period (default) — RN-06', function () {
    it('bills the spreadsheet months with whole points', function (array $minutes, int $points, int $amountCents) {
        $totals = (new BillingCalculator)->calculate(billableEntries($minutes), new RoundUpPerPeriod, billingRate(6000));

        expect($totals->points()?->value)->toBe($points)
            ->and($totals->amount->cents)->toBe($amountCents);
    })->with([
        'august 2026' => [[600, 52], 44, 66000],
        'september 2026' => [[1000, 564], 105, 157500],
        'october 2026' => [[1300, 24], 89, 133500],
    ]);

    it('rounds the period once, not each entry', function () {
        $totals = (new BillingCalculator)->calculate(billableEntries([7, 7, 7]), new RoundUpPerPeriod, billingRate(6000));

        expect($totals->workedMinutes->value)->toBe(21)
            ->and($totals->billedMinutes->value)->toBe(30)
            ->and($totals->roundingMinutes()->value)->toBe(9)
            ->and($totals->amount->cents)->toBe(3000);
    });

    it('adds nothing when the period is already whole points', function () {
        $totals = (new BillingCalculator)->calculate(billableEntries([30, 15]), new RoundUpPerPeriod, billingRate(6000));

        expect($totals->roundingMinutes()->value)->toBe(0)
            ->and($totals->amount->cents)->toBe(4500);
    });

    it('prices each entry at its own rate and the rounding at the closing rate', function () {
        $worked = [
            new BillableEntry(Minutes::of(50), billingRate(6000)),
            new BillableEntry(Minutes::of(50), billingRate(9000)),
        ];

        $totals = (new BillingCalculator)->calculate($worked, new RoundUpPerPeriod, billingRate(9000));

        // 50 min × R$ 60 + 50 min × R$ 90 + 5 min of rounding × R$ 90
        expect($totals->billedMinutes->value)->toBe(105)
            ->and($totals->amount->cents)->toBe(5000 + 7500 + 750);
    });
});

describe('round up per entry — RN-06', function () {
    it('rounds every entry up before summing', function () {
        $totals = (new BillingCalculator)->calculate(billableEntries([7, 7, 7]), new RoundUpPerEntry, billingRate(6000));

        expect($totals->billedMinutes->value)->toBe(45)
            ->and($totals->points()?->value)->toBe(3)
            ->and($totals->amount->cents)->toBe(4500);
    });
});

describe('exact minutes — RN-06', function () {
    it('charges the minutes worked without rounding', function () {
        $totals = (new BillingCalculator)->calculate(billableEntries([652]), new ExactMinutes, billingRate(6000));

        expect($totals->billedMinutes->value)->toBe(652)
            ->and($totals->roundingMinutes()->value)->toBe(0)
            ->and($totals->points())->toBeNull()
            ->and($totals->amount->cents)->toBe(65200);
    });

    it('rounds the money only once at the end — RN-07', function () {
        // Three entries of 1 minute at R$ 60,10/h: 3 × 100,1666… cents = 300,5 → 301 cents.
        $totals = (new BillingCalculator)->calculate(billableEntries([1, 1, 1], 6010), new ExactMinutes, billingRate(6010));

        expect($totals->amount->cents)->toBe(301);
    });
});

it('returns zero for a period without entries', function () {
    $totals = (new BillingCalculator)->calculate([], new RoundUpPerPeriod, billingRate(6000));

    expect($totals->workedMinutes->value)->toBe(0)
        ->and($totals->billedMinutes->value)->toBe(0)
        ->and($totals->points()?->value)->toBe(0)
        ->and($totals->amount->cents)->toBe(0);
});
