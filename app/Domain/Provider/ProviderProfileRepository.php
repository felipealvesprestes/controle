<?php

namespace App\Domain\Provider;

/**
 * The single provider profile (F-02). Reading never fails: a missing profile reads as the initial values.
 */
interface ProviderProfileRepository
{
    public function billingDefaults(): BillingDefaults;

    public function companyDetails(): CompanyDetails;

    /**
     * @return int The profile id, for the audit trail.
     */
    public function saveBillingDefaults(BillingDefaults $defaults): int;

    /**
     * @return int The profile id, for the audit trail.
     */
    public function saveCompanyDetails(CompanyDetails $details): int;
}
