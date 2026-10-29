<?php

namespace App\Domain\Time;

/**
 * How a time entry came in (F-05, F-06, F-07).
 */
enum EntrySource: string
{
    case Manual = 'manual';
    case Timer = 'timer';
    case Import = 'import';
}
