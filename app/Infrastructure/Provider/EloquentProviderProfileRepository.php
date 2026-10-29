<?php

namespace App\Infrastructure\Provider;

use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Provider\BillingDefaults;
use App\Domain\Provider\CompanyDetails;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Provider\TaxRegime;
use App\Domain\Shared\Cnpj;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Money;
use App\Models\ProviderProfile;

final class EloquentProviderProfileRepository implements ProviderProfileRepository
{
    public function billingDefaults(): BillingDefaults
    {
        $profile = $this->find();

        if ($profile === null) {
            return BillingDefaults::initial();
        }

        return new BillingDefaults(
            HourlyRate::of(Money::fromCents($profile->default_rate_cents)),
            RoundingPolicyType::from($profile->default_rounding_policy),
        );
    }

    public function companyDetails(): CompanyDetails
    {
        $profile = $this->find();

        if ($profile === null) {
            return new CompanyDetails;
        }

        return new CompanyDetails(
            legalName: $profile->legal_name,
            tradeName: $profile->trade_name,
            cnpj: $profile->cnpj === null ? null : Cnpj::fromInput($profile->cnpj),
            municipalRegistration: $profile->municipal_registration,
            taxRegime: TaxRegime::from($profile->tax_regime),
            street: $profile->address_street,
            number: $profile->address_number,
            complement: $profile->address_complement,
            district: $profile->address_district,
            city: $profile->address_city,
            state: $profile->address_state,
            zipCode: $profile->address_zip_code,
        );
    }

    public function saveBillingDefaults(BillingDefaults $defaults): int
    {
        $profile = $this->findOrNew();
        $profile->forceFill([
            'default_rate_cents' => $defaults->rate->perHour->cents,
            'default_rounding_policy' => $defaults->roundingPolicy->value,
        ])->save();

        return $profile->id;
    }

    public function saveCompanyDetails(CompanyDetails $details): int
    {
        $profile = $this->findOrNew();
        $profile->forceFill([
            'legal_name' => $details->legalName,
            'trade_name' => $details->tradeName,
            'cnpj' => $details->cnpj?->value,
            'municipal_registration' => $details->municipalRegistration,
            'tax_regime' => $details->taxRegime->value,
            'address_street' => $details->street,
            'address_number' => $details->number,
            'address_complement' => $details->complement,
            'address_district' => $details->district,
            'address_city' => $details->city,
            'address_state' => $details->state,
            'address_zip_code' => $details->zipCode,
        ])->save();

        return $profile->id;
    }

    private function find(): ?ProviderProfile
    {
        return ProviderProfile::query()->orderBy('id')->first();
    }

    /**
     * The existing profile locked for update, or a new one with the initial billing defaults.
     */
    private function findOrNew(): ProviderProfile
    {
        $profile = ProviderProfile::query()->orderBy('id')->lockForUpdate()->first();

        if ($profile !== null) {
            return $profile;
        }

        $initial = BillingDefaults::initial();

        return (new ProviderProfile)->forceFill([
            'default_rate_cents' => $initial->rate->perHour->cents,
            'default_rounding_policy' => $initial->roundingPolicy->value,
            'tax_regime' => TaxRegime::SimplesMeEpp->value,
        ]);
    }
}
