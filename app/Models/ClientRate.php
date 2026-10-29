<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A client's hourly rate from a given day (RN-04).
 *
 * @property int $id
 * @property int $client_id
 * @property int $rate_cents
 * @property CarbonImmutable $valid_from
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClientRate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_cents' => 'integer',
            'valid_from' => 'immutable_date',
        ];
    }
}
