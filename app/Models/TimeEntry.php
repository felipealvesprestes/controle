<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A piece of work (F-05); with no end it is the running timer (F-06). Written only through the repository.
 *
 * @property int $id
 * @property int $user_id
 * @property int $client_id
 * @property int $category_id
 * @property int $billing_period_id
 * @property string $description
 * @property string|null $reference
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $ended_at
 * @property int|null $duration_minutes
 * @property int $rate_cents
 * @property string $source
 * @property int|null $running_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Client $client
 * @property-read Category $category
 * @property-read BillingPeriod $billingPeriod
 * @property-read User $user
 */
class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'duration_minutes' => 'integer',
            'rate_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<BillingPeriod, $this>
     */
    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
