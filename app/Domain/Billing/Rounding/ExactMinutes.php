<?php

namespace App\Domain\Billing\Rounding;

use App\Domain\Shared\HourlyRate;

/**
 * Charges the exact minutes worked, without rounding (RN-06).
 */
final class ExactMinutes implements RoundingPolicy
{
    public function apply(array $entries, HourlyRate $closingRate): array
    {
        return $entries;
    }
}
