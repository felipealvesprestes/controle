<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The entries of one client in one month (RN-08). Written only through the repository.
 *
 * @property int $id
 * @property int $client_id
 * @property CarbonImmutable $month
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BillingPeriod extends Model
{
    /** @use HasFactory<BillingPeriodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'immutable_date',
        ];
    }
}
