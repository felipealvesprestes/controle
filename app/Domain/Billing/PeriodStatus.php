<?php

namespace App\Domain\Billing;

/**
 * Where a billing period stands (RN-09).
 */
enum PeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Invoiced = 'invoiced';
    case Paid = 'paid';

    /**
     * Only an open period takes new, changed or deleted entries (RN-10).
     */
    public function acceptsEntries(): bool
    {
        return $this === self::Open;
    }
}
