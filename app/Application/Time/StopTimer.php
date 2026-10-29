<?php

namespace App\Application\Time;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Time\TimeEntry;
use App\Domain\Time\TimeEntryRepository;
use App\Domain\Time\TimeRuleViolation;
use App\Domain\Time\WorkInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Stops the actor's timer, which becomes a regular entry under the duration and overlap rules (F-06, RN-02, RN-03, RN-14).
 */
final class StopTimer
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly EntryRules $rules,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {}

    /**
     * @param  DateTimeImmutable|null  $endedAt  An earlier end, for a timer left running; null ends it now.
     */
    public function handle(int $actorId, string $description, string $reference, bool $longConfirmed = false, ?DateTimeImmutable $endedAt = null): TimeEntry
    {
        if (! $this->authorizer->allows($actorId, 'time.entry.create')) {
            throw new AccessDenied('You may not log time.');
        }

        return DB::transaction(function () use ($actorId, $description, $reference, $longConfirmed, $endedAt): TimeEntry {
            $this->entries->lockOwner($actorId);
            $timer = $this->entries->runningTimer($actorId) ?? throw new TimeRuleViolation('timer', 'No timer is running.');
            $now = $this->clock->now();

            if ($endedAt !== null && $endedAt > $now) {
                throw new TimeRuleViolation('ended_at', 'The end cannot be in the future.');
            }

            if ($endedAt === null && WorkInterval::toMinute($now) <= $timer->startedAt) {
                throw new TimeRuleViolation('timer', 'Less than a minute has passed. Keep going or discard the timer.');
            }

            $entry = $this->rules->build($actorId, new EntryDetails($timer->clientId, $timer->categoryId, $description, $reference, $timer->startedAt, $endedAt ?? $now, $longConfirmed), $timer, $timer->source);
            $this->entries->save($entry);
            $this->audit->record($actorId, 'timer.stopped', 'time_entry', (int) $timer->id, $timer->toArray(), $entry->toArray());

            return $entry;
        });
    }
}
