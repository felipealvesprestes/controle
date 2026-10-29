<?php

namespace App\Livewire\Settings;

use App\Application\Provider\UpdateBillingDefaults;
use App\Domain\Billing\BillableEntry;
use App\Domain\Billing\BillingCalculator;
use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Provider\ProviderRuleViolation;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Minutes;
use App\Domain\Shared\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * T-10 › Billing defaults: default hourly rate and rounding policy, with a live example (F-02).
 */
class BillingDefaultsSettings extends Component
{
    /** Minutes of the live example: 1h07, as in F-02. */
    public const int EXAMPLE_MINUTES = 67;

    /** Empty unless the user may see the rate: public properties reach the browser. */
    public string $rate = '';

    public string $roundingPolicy = '';

    public function mount(ProviderProfileRepository $profiles): void
    {
        $defaults = $profiles->billingDefaults();
        $this->roundingPolicy = $defaults->roundingPolicy->value;

        if (Gate::allows('settings.billing.rate.view')) {
            $this->rate = $defaults->rate->perHour->decimal();
        }
    }

    /**
     * The example for the rate and policy on screen, or null while the rate is not a valid amount.
     *
     * @return array{worked: string, points: int|null, billed: string, amount: string}|null
     */
    #[Computed]
    public function example(): ?array
    {
        $policy = RoundingPolicyType::tryFrom($this->roundingPolicy);

        try {
            $rate = HourlyRate::of(Money::parse($this->rate));
        } catch (InvalidArgumentException) {
            return null;
        }

        if ($policy === null) {
            return null;
        }

        $worked = Minutes::of(self::EXAMPLE_MINUTES);
        $totals = app(BillingCalculator::class)->calculate([new BillableEntry($worked, $rate)], $policy->policy(), $rate);

        return [
            'worked' => $worked->formatted(),
            'points' => $totals->points()?->value,
            'billed' => $totals->billedMinutes->formatted(),
            'amount' => $totals->amount->formatted(),
        ];
    }

    public function save(UpdateBillingDefaults $updateBillingDefaults): void
    {
        try {
            $defaults = $updateBillingDefaults->handle((int) Auth::id(), $this->rate, $this->roundingPolicy);
        } catch (ProviderRuleViolation $violation) {
            $this->resetErrorBag();
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        $this->rate = $defaults->rate->perHour->decimal();
        $this->resetErrorBag();
        Flux::toast(variant: 'success', text: __('Billing defaults saved.'));
    }

    public function render(): View
    {
        return view('livewire.settings.billing-defaults-settings', [
            'policies' => RoundingPolicyType::cases(),
        ])->title(__('Billing defaults'));
    }
}
