<?php

namespace App\Application\Time;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Time\EntrySource;
use App\Domain\Time\TimeEntry;
use App\Domain\Time\TimeEntryRepository;
use App\Domain\Time\TimeRuleViolation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Starts the actor's timer now; the description can wait until it stops (F-06, RN-14).
 */
final class StartTimer
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly EntryRules $rules,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {}

    public function handle(int $actorId, int $clientId, int $categoryId, string $description = '', string $reference = ''): TimeEntry
    {
        if (! $this->authorizer->allows($actorId, 'time.entry.create')) {
            throw new AccessDenied('You may not log time.');
        }

        // The unique index on the running timer settles two starts at once, from two devices.
        try {
            return DB::transaction(function () use ($actorId, $clientId, $categoryId, $description, $reference): TimeEntry {
                $this->entries->lockOwner($actorId);

                if ($this->entries->runningTimer($actorId) !== null) {
                    throw new TimeRuleViolation('timer', 'A timer is already running. Stop it first.');
                }

                $entry = $this->rules->build($actorId, new EntryDetails($clientId, $categoryId, $description, $reference, $this->clock->now(), null), null, EntrySource::Timer);
                $entry = $entry->withId($this->entries->save($entry));
                $this->audit->record($actorId, 'timer.started', 'time_entry', (int) $entry->id, null, $entry->toArray());

                return $entry;
            });
        } catch (UniqueConstraintViolationException) {
            throw new TimeRuleViolation('timer', 'A timer is already running. Stop it first.');
        }
    }
}
