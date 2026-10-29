<?php

namespace App\Domain\Provider;

use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Money;

/**
 * Rate and rounding used by clients that do not set their own (RN-04, RN-06).
 */
final readonly class BillingDefaults
{
    /** R$ 60,00 per hour, the spreadsheet's rate. */
    public const int INITIAL_RATE_CENTS = 6000;

    public function __construct(
        public HourlyRate $rate,
        public RoundingPolicyType $roundingPolicy,
    ) {}

    public static function initial(): self
    {
        return new self(HourlyRate::of(Money::fromCents(self::INITIAL_RATE_CENTS)), RoundingPolicyType::PerPeriod);
    }

    /**
     * @return array{rate_cents: int, rounding_policy: string}
     */
    public function toArray(): array
    {
        return ['rate_cents' => $this->rate->perHour->cents, 'rounding_policy' => $this->roundingPolicy->value];
    }
}
