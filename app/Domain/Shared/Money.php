<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * An amount in Brazilian reais, stored as whole cents (RN-07, ADR-0004).
 */
final readonly class Money
{
    private function __construct(public int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Converts a non-negative amount expressed in "cent-minutes" (minutes × cents per hour)
     * into money, rounding half up to the nearest cent exactly once.
     */
    public static function fromCentMinutes(int $centMinutes): self
    {
        if ($centMinutes < 0) {
            throw new InvalidArgumentException("Cent-minutes cannot be negative, got {$centMinutes}.");
        }

        return new self(intdiv($centMinutes + 30, 60));
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }
}
