<?php

namespace App\Domain\Client;

use App\Domain\Address\Address;
use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Shared\TaxId;

/**
 * Who receives the bill, with the data the invoice needs (F-03).
 */
final readonly class Client
{
    /** Longest short name: it has to fit in selectors and on the phone. */
    public const int NAME_LIMIT = 60;

    /**
     * @param  RoundingPolicyType|null  $roundingPolicy  Null uses the provider default (RN-06).
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public ClientColor $color,
        public ?RoundingPolicyType $roundingPolicy = null,
        public string $legalName = '',
        public ?TaxId $taxId = null,
        public string $email = '',
        public bool $withholdsIss = false,
        public ?Address $address = null,
        public bool $active = true,
    ) {}

    public function address(): Address
    {
        return $this->address ?? Address::empty();
    }

    /**
     * What is still missing to issue an invoice: legal name, CPF or CNPJ and the full address.
     *
     * @return list<'legal_name'|'tax_id'|'address'>
     */
    public function missingFiscalData(): array
    {
        return array_values(array_filter([
            $this->legalName === '' ? 'legal_name' : null,
            $this->taxId === null ? 'tax_id' : null,
            $this->address()->isComplete() ? null : 'address',
        ]));
    }

    /**
     * @return array<string, string|bool|null>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'color' => $this->color->value,
            'rounding_policy' => $this->roundingPolicy?->value,
            'legal_name' => $this->legalName,
            'document' => $this->taxId?->value(),
            'email' => $this->email,
            'withholds_iss' => $this->withholdsIss,
            ...$this->address()->toArray(),
            'active' => $this->active,
        ];
    }
}
