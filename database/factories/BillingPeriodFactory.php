<?php

namespace Database\Factories;

use App\Domain\Billing\PeriodStatus;
use App\Models\BillingPeriod;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPeriod>
 */
class BillingPeriodFactory extends Factory
{
    /**
     * Define the model's default state: this month, open.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'month' => now()->startOfMonth()->toDateString(),
            'status' => PeriodStatus::Open->value,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PeriodStatus::Closed->value]);
    }
}
