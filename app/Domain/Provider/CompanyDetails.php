<?php

namespace App\Domain\Provider;

use App\Domain\Shared\Cnpj;

/**
 * The provider company as it appears on the report and on the invoice (F-02).
 */
final readonly class CompanyDetails
{
    public function __construct(
        public string $legalName = '',
        public string $tradeName = '',
        public ?Cnpj $cnpj = null,
        public string $municipalRegistration = '',
        public TaxRegime $taxRegime = TaxRegime::SimplesMeEpp,
        public string $street = '',
        public string $number = '',
        public string $complement = '',
        public string $district = '',
        public string $city = '',
        public string $state = '',
        public string $zipCode = '',
    ) {}

    /**
     * Enough to identify the provider on a report and on the invoice: legal name, CNPJ and municipal registration.
     */
    public function isComplete(): bool
    {
        return $this->legalName !== '' && $this->cnpj !== null && $this->municipalRegistration !== '';
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'legal_name' => $this->legalName,
            'trade_name' => $this->tradeName,
            'cnpj' => $this->cnpj?->value,
            'municipal_registration' => $this->municipalRegistration,
            'tax_regime' => $this->taxRegime->value,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'district' => $this->district,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zipCode,
        ];
    }
}
