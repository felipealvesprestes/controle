<?php

namespace App\Domain\Time;

use App\Domain\Shared\Minutes;
use DateTimeImmutable;

/**
 * Start and end of a piece of work, to the minute (RN-02).
 */
final readonly class WorkInterval
{
    /** Longer entries are refused: 12 hours. */
    public const int MAX_MINUTES = 720;

    /** Longer entries need a confirmation: 4 hours. */
    public const int CONFIRM_ABOVE_MINUTES = 240;

    private function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {}

    /**
     * Drops the seconds of both ends; the end may fall on the next day.
     */
    public static function between(DateTimeImmutable $start, DateTimeImmutable $end): self
    {
        $start = self::toMinute($start);
        $end = self::toMinute($end);

        if ($end <= $start) {
            throw new TimeRuleViolation('ended_at', 'The end must be after the start.');
        }

        $interval = new self($start, $end);

        if ($interval->minutes()->value > self::MAX_MINUTES) {
            throw new TimeRuleViolation('ended_at', 'An entry cannot last more than 12 hours.');
        }

        return $interval;
    }

    public static function toMinute(DateTimeImmutable $moment): DateTimeImmutable
    {
        return $moment->setTime((int) $moment->format('H'), (int) $moment->format('i'));
    }

    public function minutes(): Minutes
    {
        return Minutes::of(intdiv($this->end->getTimestamp() - $this->start->getTimestamp(), 60));
    }

    public function needsConfirmation(): bool
    {
        return $this->minutes()->value > self::CONFIRM_ABOVE_MINUTES;
    }
}
