<?php

namespace App\Livewire\Settings;

use App\Application\Provider\UpdateCompanyDetails;
use App\Domain\Provider\CompanyDetails;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Provider\ProviderRuleViolation;
use App\Livewire\Concerns\FillsAddressFromZipCode;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * T-10 › Company details: the provider as it appears on the report and on the invoice (F-02).
 */
class CompanyDetailsSettings extends Component
{
    use FillsAddressFromZipCode;

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

    public function updatedZipCode(): void
    {
        $this->fillAddressFromZipCode('settings.company.edit', 'company-number');
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
        return view('livewire.settings.company-details-settings')->title(__('Company details'));
    }
}
