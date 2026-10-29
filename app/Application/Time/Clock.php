<?php

namespace App\Application\Time;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Config;

/**
 * The current moment and the local calendar: days and months follow the display time zone, not UTC (RN-02, RN-08).
 */
final class Clock
{
    public function now(): DateTimeImmutable
    {
        return $this->local(CarbonImmutable::now()->toDateTimeImmutable());
    }

    public function local(DateTimeImmutable $moment): DateTimeImmutable
    {
        return $moment->setTimezone(new DateTimeZone(Config::string('app.display_timezone')));
    }
}
