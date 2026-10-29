<?php

namespace App\Domain\Billing;

use DateTimeImmutable;

/**
 * Billing periods (F-08).
 */
interface BillingPeriodRepository
{
    public function find(int $id): ?BillingPeriod;

    /**
     * The client's period of the month of the given day, created open when it does not exist yet (RN-08).
     */
    public function forMonth(int $clientId, DateTimeImmutable $day): BillingPeriod;

    /**
     * Removes the periods of a client that has no entries, before the client is deleted.
     */
    public function deleteForClient(int $clientId): void;
}
