<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * A Brazilian individual taxpayer number, for clients that are people (F-03).
 */
final readonly class Cpf
{
    private function __construct(public string $value) {}

    /**
     * Accepts the number with or without punctuation.
     */
    public static function fromInput(string $input): self
    {
        $value = (string) preg_replace('/[\s.-]/', '', $input);

        if (preg_match('/^[0-9]{11}$/', $value) !== 1 || preg_match('/^(.)\1{10}$/', $value) === 1) {
            throw new InvalidArgumentException('Invalid CPF format.');
        }

        $first = self::checkDigit(substr($value, 0, 9));
        $second = self::checkDigit(substr($value, 0, 9).$first);

        if (substr($value, 9) !== $first.$second) {
            throw new InvalidArgumentException('Invalid CPF check digits.');
        }

        return new self($value);
    }

    /**
     * 529.982.247-25
     */
    public function formatted(): string
    {
        return sprintf('%s.%s.%s-%s', substr($this->value, 0, 3), substr($this->value, 3, 3), substr($this->value, 6, 3), substr($this->value, 9));
    }

    /**
     * Weights start at the length plus one and fall to 2.
     */
    private static function checkDigit(string $digits): string
    {
        $sum = 0;
        $weight = strlen($digits) + 1;

        foreach (str_split($digits) as $digit) {
            $sum += (int) $digit * $weight--;
        }

        $remainder = $sum % 11;

        return (string) ($remainder < 2 ? 0 : 11 - $remainder);
    }
}
