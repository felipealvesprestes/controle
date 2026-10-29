<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The single provider profile (F-02). Written only through the repository.
 *
 * @property int $id
 * @property int $default_rate_cents
 * @property string $default_rounding_policy
 * @property string $legal_name
 * @property string $trade_name
 * @property string|null $cnpj
 * @property string $municipal_registration
 * @property string $tax_regime
 * @property string $address_street
 * @property string $address_number
 * @property string $address_complement
 * @property string $address_district
 * @property string $address_city
 * @property string $address_state
 * @property string $address_zip_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProviderProfile extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_rate_cents' => 'integer',
        ];
    }
}
