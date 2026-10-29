<?php

namespace App\Livewire\Concerns;

use App\Application\Address\LookUpZipCode;
use App\Domain\Address\AddressLookupUnavailable;
use App\Domain\Address\ZipCodeLookupRefused;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Fills street, district, city and state as soon as the CEP has 8 digits, like most Brazilian forms (ADR-0013).
 * Number and complement stay as typed. The component declares the address properties.
 *
 * @property string $zipCode
 * @property string $street
 * @property string $district
 * @property string $city
 * @property string $state
 */
trait FillsAddressFromZipCode
{
    /**
     * @param  string  $permission  The edit permission of the form being filled.
     * @param  string  $numberFieldId  Field that receives the focus once the address is filled.
     */
    protected function fillAddressFromZipCode(string $permission, string $numberFieldId): void
    {
        $digits = (string) preg_replace('/\D/', '', $this->zipCode);

        if (strlen($digits) !== 8 || Gate::denies($permission)) {
            return;
        }

        $this->resetErrorBag('zipCode');

        try {
            $suggestion = app(LookUpZipCode::class)->handle((int) Auth::id(), $permission, $digits);
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

        $this->js("document.getElementById('{$numberFieldId}')?.focus()");
    }
}
