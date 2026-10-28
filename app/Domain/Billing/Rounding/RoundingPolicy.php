<?php

namespace App\Domain\Billing\Rounding;

use App\Domain\Billing\BillableEntry;
use App\Domain\Shared\HourlyRate;

/**
 * Turns worked entries into the entries that are actually charged (RN-06).
 */
interface RoundingPolicy
{
    /**
     * @param  list<BillableEntry>  $entries
     * @param  HourlyRate  $closingRate  Rate in force on the last day of the period, used to price period-level rounding.
     * @return list<BillableEntry>
     */
    public function apply(array $entries, HourlyRate $closingRate): array;
}
