<?php

namespace App\Application\Time;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Time\TimeEntry;
use App\Domain\Time\TimeEntryRepository;
use App\Domain\Time\TimeRuleViolation;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Moves the start of the running timer, for work that began before the timer did (F-06).
 */
final class AdjustTimerStart
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly EntryRules $rules,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {}

    public function handle(int $actorId, DateTimeImmutable $startedAt): TimeEntry
    {
        if (! $this->authorizer->allows($actorId, 'time.entry.create')) {
            throw new AccessDenied('You may not log time.');
        }

        return DB::transaction(function () use ($actorId, $startedAt): TimeEntry {
            $this->entries->lockOwner($actorId);
            $timer = $this->entries->runningTimer($actorId) ?? throw new TimeRuleViolation('timer', 'No timer is running.');

            if ($startedAt > $this->clock->now()) {
                throw new TimeRuleViolation('started_at', 'The start cannot be in the future.');
            }

            $entry = $this->rules->build($actorId, new EntryDetails($timer->clientId, $timer->categoryId, $timer->description, (string) $timer->reference, $startedAt, null), $timer, $timer->source);
            $this->entries->save($entry);
            $this->audit->record($actorId, 'timer.start_adjusted', 'time_entry', (int) $timer->id, ['started_at' => $timer->startedAt->format(DATE_ATOM)], ['started_at' => $entry->startedAt->format(DATE_ATOM)]);

            return $entry;
        });
    }
}
