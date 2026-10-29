<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Who receives the bill (F-03). Written only through the repository.
 *
 * @property int $id
 * @property string $name
 * @property string $color
 * @property string|null $rounding_policy
 * @property string $legal_name
 * @property string|null $document
 * @property string $email
 * @property bool $withholds_iss
 * @property string $address_street
 * @property string $address_number
 * @property string $address_complement
 * @property string $address_district
 * @property string $address_city
 * @property string $address_state
 * @property string $address_zip_code
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'withholds_iss' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ClientRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ClientRate::class);
    }
}
