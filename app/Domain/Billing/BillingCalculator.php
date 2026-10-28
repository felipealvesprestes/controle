<?php

namespace App\Domain\Billing;

use App\Domain\Billing\Rounding\RoundingPolicy;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Minutes;
use App\Domain\Shared\Money;

/**
 * The single place where a period's charge is computed (RN-06, RN-07).
 */
final class BillingCalculator
{
    /**
     * @param  list<BillableEntry>  $entries
     */
    public function calculate(array $entries, RoundingPolicy $policy, HourlyRate $closingRate): BillingTotals
    {
        $billed = $policy->apply($entries, $closingRate);

        $centMinutes = array_sum(array_map(
            static fn (BillableEntry $entry): int => $entry->rate->centMinutesFor($entry->minutes),
            $billed,
        ));

        return new BillingTotals(
            workedMinutes: $this->sumMinutes($entries),
            billedMinutes: $this->sumMinutes($billed),
            amount: Money::fromCentMinutes($centMinutes),
        );
    }

    /**
     * @param  list<BillableEntry>  $entries
     */
    private function sumMinutes(array $entries): Minutes
    {
        return array_reduce(
            $entries,
            static fn (Minutes $total, BillableEntry $entry): Minutes => $total->plus($entry->minutes),
            Minutes::zero(),
        );
    }
}
