<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * Price charged per hour of work (RN-04).
 */
final readonly class HourlyRate
{
    private function __construct(public Money $perHour) {}

    public static function of(Money $perHour): self
    {
        if ($perHour->cents <= 0) {
            throw new InvalidArgumentException("Hourly rate must be positive, got {$perHour->cents} cents.");
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
