<?php

namespace App\Livewire\Settings;

use App\Application\Address\LookUpZipCode;
use App\Application\Provider\UpdateCompanyDetails;
use App\Domain\Address\AddressLookupUnavailable;
use App\Domain\Address\BrazilianStates;
use App\Domain\Address\ZipCodeLookupRefused;
use App\Domain\Provider\CompanyDetails;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Provider\ProviderRuleViolation;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * T-10 › Company details: the provider as it appears on the report and on the invoice (F-02).
 */
class CompanyDetailsSettings extends Component
{
    public string $legalName = '';

    public string $tradeName = '';

    public string $cnpj = '';

    public string $municipalRegistration = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $district = '';

    public string $city = '';

    public string $state = '';

    public string $zipCode = '';

    public function mount(ProviderProfileRepository $profiles): void
    {
        $this->fillFrom($profiles->companyDetails());
    }

    /**
     * Fills the address as soon as the CEP has 8 digits, like most Brazilian forms. Number and complement stay as typed.
     */
    public function updatedZipCode(): void
    {
        $digits = (string) preg_replace('/\D/', '', $this->zipCode);

        if (strlen($digits) !== 8 || Gate::denies('settings.company.edit')) {
            return;
        }

        $this->resetErrorBag('zipCode');

        try {
            $suggestion = app(LookUpZipCode::class)->handle((int) Auth::id(), 'settings.company.edit', $digits);
        } catch (ZipCodeLookupRefused $refused) {
            $this->addError('zipCode', __($refused->getMessage()));

            return;
        } catch (AddressLookupUnavailable) {
            $this->addError('zipCode', __('The CEP lookup is unavailable right now. Fill in the address.'));

            return;
        }

        if ($suggestion === null) {
            $this->addError('zipCode', __('CEP not found. Check it or fill in the address.'));

            return;
        }

        $this->zipCode = substr($digits, 0, 5).'-'.substr($digits, 5);

        foreach (['street' => $suggestion->street, 'district' => $suggestion->district, 'city' => $suggestion->city, 'state' => $suggestion->state] as $field => $value) {
            if ($value !== '') {
                $this->{$field} = $value;
            }
        }

        $this->js("document.getElementById('company-number')?.focus()");
    }

    public function save(UpdateCompanyDetails $updateCompanyDetails): void
    {
        try {
            $details = $updateCompanyDetails->handle((int) Auth::id(), [
                'legalName' => $this->legalName,
                'tradeName' => $this->tradeName,
                'cnpj' => $this->cnpj,
                'municipalRegistration' => $this->municipalRegistration,
                'street' => $this->street,
                'number' => $this->number,
                'complement' => $this->complement,
                'district' => $this->district,
                'city' => $this->city,
                'state' => $this->state,
                'zipCode' => $this->zipCode,
            ]);
        } catch (ProviderRuleViolation $violation) {
            $this->resetErrorBag();
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        $this->fillFrom($details);
        $this->resetErrorBag();
        Flux::toast(variant: 'success', text: __('Company details saved.'));
    }

    private function fillFrom(CompanyDetails $details): void
    {
        $this->legalName = $details->legalName;
        $this->tradeName = $details->tradeName;
        $this->cnpj = $details->cnpj?->formatted() ?? '';
        $this->municipalRegistration = $details->municipalRegistration;
        $this->street = $details->street;
        $this->number = $details->number;
        $this->complement = $details->complement;
        $this->district = $details->district;
        $this->city = $details->city;
        $this->state = $details->state;
        $this->zipCode = $details->zipCode === '' ? '' : substr($details->zipCode, 0, 5).'-'.substr($details->zipCode, 5);
    }

    public function render(): View
    {
        return view('livewire.settings.company-details-settings', [
            'states' => BrazilianStates::CODES,
        ])->title(__('Company details'));
    }
}
