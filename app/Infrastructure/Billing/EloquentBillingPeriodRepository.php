<?php

namespace App\Infrastructure\Billing;

use App\Domain\Billing\BillingPeriod;
use App\Domain\Billing\BillingPeriodRepository;
use App\Domain\Billing\PeriodStatus;
use App\Models\BillingPeriod as BillingPeriodModel;
use DateTimeImmutable;

final class EloquentBillingPeriodRepository implements BillingPeriodRepository
{
    public function find(int $id): ?BillingPeriod
    {
        $model = BillingPeriodModel::query()->find($id);

        return $model === null ? null : self::toDomain($model);
    }

    public function forMonth(int $clientId, DateTimeImmutable $day): BillingPeriod
    {
        // The unique index settles two first entries of the month saved at once.
        return self::toDomain(BillingPeriodModel::unguarded(fn (): BillingPeriodModel => BillingPeriodModel::query()->createOrFirst(
            ['client_id' => $clientId, 'month' => $day->format('Y-m-01')],
            ['status' => PeriodStatus::Open->value],
        )));
    }

    public function deleteForClient(int $clientId): void
    {
        BillingPeriodModel::query()->where('client_id', $clientId)->delete();
    }

    public static function toDomain(BillingPeriodModel $model): BillingPeriod
    {
        return new BillingPeriod($model->id, $model->client_id, $model->month, PeriodStatus::from($model->status));
    }
}
