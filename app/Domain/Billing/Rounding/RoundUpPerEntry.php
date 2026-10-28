<?php

namespace App\Domain\Billing\Rounding;

use App\Domain\Billing\BillableEntry;
use App\Domain\Shared\HourlyRate;

/**
 * Rounds every entry up to the next whole point before summing (RN-06).
 */
final class RoundUpPerEntry implements RoundingPolicy
{
    public function apply(array $entries, HourlyRate $closingRate): array
    {
        return array_map(
            static fn (BillableEntry $entry): BillableEntry => new BillableEntry($entry->minutes->roundedUpToPoint(), $entry->rate),
            $entries,
        );
    }
}
