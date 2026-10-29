<?php

namespace App\Domain\Time;

use App\Domain\Shared\Minutes;
use RuntimeException;

/**
 * An entry longer than four hours waits for the person to confirm it (RN-02).
 */
final class LongEntryNeedsConfirmation extends RuntimeException
{
    public function __construct(public readonly Minutes $minutes)
    {
        parent::__construct('This entry lasts more than 4 hours.');
    }
}
