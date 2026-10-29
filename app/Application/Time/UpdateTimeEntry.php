<?php

namespace App\Application\Time;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Time\TimeEntry;
use App\Domain\Time\TimeEntryRepository;
use App\Domain\Time\TimeRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Changes an entry of an open period; the owner stays the same (F-05, RN-10).
 */
final class UpdateTimeEntry
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly EntryRules $rules,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $entryId, EntryDetails $details): TimeEntry
    {
        if (! $this->authorizer->allows($actorId, 'time.entry.edit')) {
            throw new AccessDenied('You may not change time entries.');
        }

        return DB::transaction(function () use ($actorId, $entryId, $details): TimeEntry {
            // Read again under the owner's lock: a delete or stop in between would otherwise be overwritten.
            $this->entries->lockOwner($this->rules->visibleEntry($actorId, $entryId)->userId);
            $before = $this->rules->visibleEntry($actorId, $entryId);

            if ($before->isRunning()) {
                throw new TimeRuleViolation('entry', 'Stop the timer before changing this entry.');
            }

            $this->rules->assertPeriodOpen($before);
            $entry = $this->rules->build($before->userId, $details, $before, $before->source);
            $this->entries->save($entry);
            $this->audit->record($actorId, 'time_entry.updated', 'time_entry', $entryId, $before->toArray(), $entry->toArray());

            return $entry;
        });
    }
}
