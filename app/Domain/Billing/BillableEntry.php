<?php

namespace App\Domain\Billing;

use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Minutes;

/**
 * Minutes charged at a given hourly rate: the only input the billing calculation needs.
 */
final readonly class BillableEntry
{
    public function __construct(
        public Minutes $minutes,
        public HourlyRate $rate,
    ) {}
}
