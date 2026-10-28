<?php

namespace App\Domain\Billing;

use App\Domain\Shared\Minutes;
use App\Domain\Shared\Money;
use App\Domain\Shared\Points;

/**
 * Result of billing a set of entries; becomes the period snapshot on closing (RN-11).
 */
final readonly class BillingTotals
{
    public function __construct(
        public Minutes $workedMinutes,
        public Minutes $billedMinutes,
        public Money $amount,
    ) {}

    /**
     * Minutes added by the rounding policy.
     */
    public function roundingMinutes(): Minutes
    {
        return $this->billedMinutes->minus($this->workedMinutes);
    }

    /**
     * Billed points, or null when the policy charges minutes that are not whole points (Exact).
     */
    public function points(): ?Points
    {
        return Points::exactlyFrom($this->billedMinutes);
    }
}
