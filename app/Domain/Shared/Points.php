<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * Billing unit: one point is worth exactly fifteen minutes (RN-01).
 */
final readonly class Points
{
    public const int MINUTES_PER_POINT = 15;

    private function __construct(public int $value) {}

    public static function of(int $value): self
    {
        if ($value < 0) {
            throw new InvalidArgumentException("Points cannot be negative, got {$value}.");
        }

        return new self($value);
    }

    /**
     * The smallest whole number of points that covers the given minutes.
     */
    public static function coveringMinutes(Minutes $minutes): self
    {
        return new self(intdiv($minutes->value + self::MINUTES_PER_POINT - 1, self::MINUTES_PER_POINT));
    }

    /**
     * The exact number of points, or null when the minutes are not a whole number of points.
     */
    public static function exactlyFrom(Minutes $minutes): ?self
    {
        if ($minutes->value % self::MINUTES_PER_POINT !== 0) {
            return null;
        }

        return new self(intdiv($minutes->value, self::MINUTES_PER_POINT));
    }

    public function toMinutes(): Minutes
    {
        return Minutes::of($this->value * self::MINUTES_PER_POINT);
    }
}
