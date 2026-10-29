<?php

namespace App\Infrastructure\Time;

use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Money;
use App\Domain\Time\EntrySource;
use App\Domain\Time\TimeEntry;
use App\Domain\Time\TimeEntryRepository;
use App\Models\TimeEntry as TimeEntryModel;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;

final class EloquentTimeEntryRepository implements TimeEntryRepository
{
    public function find(int $id): ?TimeEntry
    {
        $model = TimeEntryModel::query()->find($id);

        return $model === null ? null : self::toDomain($model);
    }

    public function lockOwner(int $userId): void
    {
        User::query()->whereKey($userId)->lockForUpdate()->first(['id']);
    }

    public function firstOverlapping(int $userId, DateTimeImmutable $start, ?DateTimeImmutable $end, ?int $exceptId = null): ?TimeEntry
    {
        $query = TimeEntryModel::query()
            ->where('user_id', $userId)
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->where(fn (Builder $query) => $query->whereNull('ended_at')->orWhere('ended_at', '>', self::utc($start)));

        if ($end !== null) {
            $query->where('started_at', '<', self::utc($end));
        }

        $model = $query->orderBy('started_at')->first();

        return $model === null ? null : self::toDomain($model);
    }

    public function runningTimer(int $userId): ?TimeEntry
    {
        $model = TimeEntryModel::query()->where('user_id', $userId)->whereNull('ended_at')->first();

        return $model === null ? null : self::toDomain($model);
    }

    public function save(TimeEntry $entry): int
    {
        $model = $entry->id === null ? new TimeEntryModel : TimeEntryModel::query()->findOrFail($entry->id);

        $model->forceFill([
            'user_id' => $entry->userId,
            'client_id' => $entry->clientId,
            'category_id' => $entry->categoryId,
            'billing_period_id' => $entry->billingPeriodId,
            'description' => $entry->description,
            'reference' => $entry->reference,
            'started_at' => self::utc($entry->startedAt),
            'ended_at' => $entry->endedAt === null ? null : self::utc($entry->endedAt),
            'duration_minutes' => $entry->minutes()?->value,
            'rate_cents' => $entry->rate->perHour->cents,
            'source' => $entry->source->value,
        ])->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        TimeEntryModel::query()->whereKey($id)->delete();
    }

    public function userHasEntries(int $userId): bool
    {
        return TimeEntryModel::query()->where('user_id', $userId)->whereNotNull('ended_at')->exists();
    }

    public function clientHasEntries(int $clientId): bool
    {
        return TimeEntryModel::query()->where('client_id', $clientId)->exists();
    }

    public function categoryHasEntries(int $categoryId): bool
    {
        return TimeEntryModel::query()->where('category_id', $categoryId)->exists();
    }

    public static function toDomain(TimeEntryModel $model): TimeEntry
    {
        return new TimeEntry(
            id: $model->id,
            userId: $model->user_id,
            clientId: $model->client_id,
            categoryId: $model->category_id,
            billingPeriodId: $model->billing_period_id,
            description: $model->description,
            reference: $model->reference,
            startedAt: $model->started_at,
            endedAt: $model->ended_at,
            rate: HourlyRate::of(Money::fromCents($model->rate_cents)),
            source: EntrySource::from($model->source),
        );
    }

    /**
     * Eloquent writes a date in its own time zone without converting it; the database keeps UTC.
     */
    private static function utc(DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
