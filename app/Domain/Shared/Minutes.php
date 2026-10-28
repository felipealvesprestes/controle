<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * A non-negative whole number of minutes (RN-02, RN-07).
 */
final readonly class Minutes
{
    private function __construct(public int $value) {}

    public static function of(int $value): self
    {
        if ($value < 0) {
            throw new InvalidArgumentException("Minutes cannot be negative, got {$value}.");
        }

        return new self($value);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function minus(self $other): self
    {
        return self::of($this->value - $other->value);
    }

    /**
     * Rounds up to the next whole point (RN-01).
     */
    public function roundedUpToPoint(): self
    {
        return new self(Points::coveringMinutes($this)->toMinutes()->value);
    }
}
