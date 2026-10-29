<?php

namespace App\Application\Provider;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Provider\BillingDefaults;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Provider\ProviderRuleViolation;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Changes the default hourly rate and rounding policy (F-02, RN-04, RN-06).
 * Closed periods keep their own snapshot, so they are not affected (RN-11).
 */
final class UpdateBillingDefaults
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProviderProfileRepository $profiles,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, string $rate, string $roundingPolicy): BillingDefaults
    {
        if (! $this->authorizer->allows($actorId, 'settings.billing.edit')) {
            throw new AccessDenied('You may not change the billing defaults.');
        }

        try {
            $hourlyRate = HourlyRate::of(Money::parse($rate));
        } catch (InvalidArgumentException) {
            throw new ProviderRuleViolation('rate', 'Enter an hourly rate between 0,01 and 100.000,00, such as 60,00.');
        }

        $policy = RoundingPolicyType::tryFrom($roundingPolicy)
            ?? throw new ProviderRuleViolation('roundingPolicy', 'Choose a rounding policy.');

        $defaults = new BillingDefaults($hourlyRate, $policy);

        DB::transaction(function () use ($actorId, $defaults): void {
            $before = $this->profiles->billingDefaults();
            $profileId = $this->profiles->saveBillingDefaults($defaults);
            $this->audit->record($actorId, 'provider.billing_defaults.updated', 'provider_profile', $profileId, $before->toArray(), $defaults->toArray());
        });

        return $defaults;
    }
}
