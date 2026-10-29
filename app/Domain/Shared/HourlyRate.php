<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * Price charged per hour of work (RN-04).
 */
final readonly class HourlyRate
{
    /** R$ 100.000,00 per hour: anything above is a typing mistake and would not fit the rate columns. */
    public const int MAX_CENTS = 10_000_000;

    private function __construct(public Money $perHour) {}

    public static function of(Money $perHour): self
    {
        if ($perHour->cents <= 0) {
            throw new InvalidArgumentException("Hourly rate must be positive, got {$perHour->cents} cents.");
        }

        if ($perHour->cents > self::MAX_CENTS) {
            throw new InvalidArgumentException('Hourly rate cannot exceed '.self::MAX_CENTS." cents, got {$perHour->cents}.");
        }

        return new self($perHour);
    }

    /**
     * Value of one point: a quarter of the hourly rate (RN-05).
     */
    public function pointValue(): Money
    {
        return Money::fromCentMinutes($this->perHour->cents * Points::MINUTES_PER_POINT);
    }

    /**
     * Amount for the given minutes in "cent-minutes", to be summed before a single rounding (RN-07).
     */
    public function centMinutesFor(Minutes $minutes): int
    {
        return $this->perHour->cents * $minutes->value;
    }
}
