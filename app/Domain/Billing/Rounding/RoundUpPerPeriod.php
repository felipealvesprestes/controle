<?php

namespace App\Domain\Billing\Rounding;

use App\Domain\Billing\BillableEntry;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Minutes;

/**
 * Sums the period and rounds it up once to the next whole point, priced at the closing rate (RN-06, default).
 */
final class RoundUpPerPeriod implements RoundingPolicy
{
    public function apply(array $entries, HourlyRate $closingRate): array
    {
        $worked = array_reduce(
            $entries,
            static fn (Minutes $total, BillableEntry $entry): Minutes => $total->plus($entry->minutes),
            Minutes::zero(),
        );

        $adjustment = $worked->roundedUpToPoint()->minus($worked);

        if ($adjustment->value === 0) {
            return $entries;
        }

        return [...$entries, new BillableEntry($adjustment, $closingRate)];
    }
}
