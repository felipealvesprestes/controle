<?php

namespace App\Domain\Billing\Rounding;

/**
 * The rounding policies a provider or client can choose, as stored (RN-06).
 */
enum RoundingPolicyType: string
{
    case PerPeriod = 'per_period';
    case PerEntry = 'per_entry';
    case Exact = 'exact';

    public function policy(): RoundingPolicy
    {
        return match ($this) {
            self::PerPeriod => new RoundUpPerPeriod,
            self::PerEntry => new RoundUpPerEntry,
            self::Exact => new ExactMinutes,
        };
    }
}
