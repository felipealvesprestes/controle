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

    /**
     * Reads an amount typed the Brazilian way: "60", "60,5", "1.234,56" or "R$ 60,00".
     */
    public static function parse(string $input): self
    {
        if (preg_match('/^(?:R\$\s*)?(\d{1,3}(?:\.\d{3}){0,2}|\d{1,9})(?:,(\d{1,2}))?$/', trim($input), $matches) !== 1) {
            throw new InvalidArgumentException('Invalid amount.');
        }

        $reais = (int) str_replace('.', '', $matches[1]);
        $cents = (int) str_pad($matches[2] ?? '0', 2, '0');

        return new self($reais * 100 + $cents);
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

    /**
     * R$ 1.335,00
     */
    public function formatted(): string
    {
        return 'R$ '.$this->decimal();
    }

    /**
     * 1.335,00, the way the amount is typed back into a form.
     */
    public function decimal(): string
    {
        return number_format(intdiv($this->cents, 100), 0, ',', '.').','.sprintf('%02d', $this->cents % 100);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }
}
