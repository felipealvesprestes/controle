<?php

namespace Database\Factories;

use App\Domain\Time\EntrySource;
use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Client;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state: 09:00 to 10:00 today, local time, at R$ 60/h, in the client's open period.
     * Pass started_at and ended_at as CarbonImmutable; they are stored in UTC.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'category_id' => Category::factory(),
            'description' => fake()->sentence(4),
            'reference' => null,
            'started_at' => CarbonImmutable::today(Config::string('app.display_timezone'))->setTime(9, 0)->utc(),
            'ended_at' => fn (array $attributes) => self::moment($attributes['started_at'])->addHour(),
            'duration_minutes' => fn (array $attributes) => $attributes['ended_at'] === null ? null : (int) self::moment($attributes['started_at'])->diffInMinutes(self::moment($attributes['ended_at'])),
            'rate_cents' => 6000,
            'source' => EntrySource::Manual->value,
            'billing_period_id' => fn (array $attributes) => BillingPeriod::query()->firstOrCreate(
                ['client_id' => $attributes['client_id'], 'month' => self::moment($attributes['started_at'])->timezone(Config::string('app.display_timezone'))->startOfMonth()->toDateString()],
                ['status' => 'open'],
            )->id,
        ];
    }

    /**
     * Local start and end, e.g. at('2026-10-28 23:00', '2026-10-29 01:00').
     */
    public function at(string $start, string $end): static
    {
        $zone = Config::string('app.display_timezone');

        return $this->state(fn (array $attributes) => [
            'started_at' => CarbonImmutable::parse($start, $zone)->utc(),
            'ended_at' => CarbonImmutable::parse($end, $zone)->utc(),
        ]);
    }

    /**
     * The running timer, started at the given moment or an hour ago.
     */
    public function running(?CarbonImmutable $startedAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => ($startedAt ?? CarbonImmutable::now()->subHour())->startOfMinute()->utc(),
            'ended_at' => null,
            'source' => EntrySource::Timer->value,
        ]);
    }

    private static function moment(mixed $value): CarbonImmutable
    {
        return $value instanceof CarbonImmutable ? $value : CarbonImmutable::parse(is_string($value) ? $value : 'now');
    }
}
