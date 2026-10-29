<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * A Brazilian company registration number, numeric or alphanumeric (Receita Federal, from July 2026).
 */
final readonly class Cnpj
{
    private const array FIRST_DIGIT_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    private const array SECOND_DIGIT_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    private function __construct(public string $value) {}

    /**
     * Accepts the number with or without punctuation; letters are upper-cased.
     */
    public static function fromInput(string $input): self
    {
        $value = strtoupper((string) preg_replace('/[\s.\/-]/', '', $input));

        if (preg_match('/^[0-9A-Z]{12}[0-9]{2}$/', $value) !== 1 || preg_match('/^(.)\1{13}$/', $value) === 1) {
            throw new InvalidArgumentException('Invalid CNPJ format.');
        }

        $base = substr($value, 0, 12);
        $first = self::checkDigit($base, self::FIRST_DIGIT_WEIGHTS);
        $second = self::checkDigit($base.$first, self::SECOND_DIGIT_WEIGHTS);

        if (substr($value, 12) !== $first.$second) {
            throw new InvalidArgumentException('Invalid CNPJ check digits.');
        }

        return new self($value);
    }

    /**
     * 12.345.678/0001-95
     */
    public function formatted(): string
    {
        return sprintf('%s.%s.%s/%s-%s', substr($this->value, 0, 2), substr($this->value, 2, 3), substr($this->value, 5, 3), substr($this->value, 8, 4), substr($this->value, 12));
    }

    /**
     * Each character is worth its ASCII code minus 48, so digits keep their value and letters start at 17.
     *
     * @param  list<int>  $weights
     */
    private static function checkDigit(string $characters, array $weights): string
    {
        $sum = 0;

        foreach ($weights as $position => $weight) {
            $sum += (ord($characters[$position]) - 48) * $weight;
        }

        $remainder = $sum % 11;

        return (string) ($remainder < 2 ? 0 : 11 - $remainder);
    }
}
