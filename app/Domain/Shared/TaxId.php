<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * The taxpayer number of whoever receives the invoice: a CPF for a person or a CNPJ for a company.
 */
final readonly class TaxId
{
    private function __construct(public Cpf|Cnpj $number) {}

    /**
     * Eleven digits read as a CPF; anything else must be a CNPJ.
     */
    public static function fromInput(string $input): self
    {
        $compact = (string) preg_replace('/[\s.\/-]/', '', $input);

        if (preg_match('/^[0-9]{11}$/', $compact) === 1) {
            return new self(Cpf::fromInput($compact));
        }

        try {
            return new self(Cnpj::fromInput($compact));
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Invalid CPF or CNPJ.');
        }
    }

    public function isIndividual(): bool
    {
        return $this->number instanceof Cpf;
    }

    public function value(): string
    {
        return $this->number->value;
    }

    public function formatted(): string
    {
        return $this->number->formatted();
    }
}
