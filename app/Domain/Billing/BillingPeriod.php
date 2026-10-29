<?php

namespace App\Domain\Billing;

use DateTimeImmutable;

/**
 * The entries of one client in one calendar month (RN-08).
 */
final readonly class BillingPeriod
{
    /**
     * @param  DateTimeImmutable  $month  First day of the month.
     */
    public function __construct(
        public int $id,
        public int $clientId,
        public DateTimeImmutable $month,
        public PeriodStatus $status,
    ) {}
}
