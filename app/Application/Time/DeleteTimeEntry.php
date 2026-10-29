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
 * Deletes an entry of an open period (F-05, RN-10). The deleted entry comes back so the screen can offer to undo.
 */
final class DeleteTimeEntry
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly EntryRules $rules,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $entryId): TimeEntry
    {
        if (! $this->authorizer->allows($actorId, 'time.entry.delete')) {
            throw new AccessDenied('You may not delete time entries.');
        }

        return DB::transaction(function () use ($actorId, $entryId): TimeEntry {
            $entry = $this->rules->visibleEntry($actorId, $entryId);

            if ($entry->isRunning()) {
                throw new TimeRuleViolation('entry', 'Stop or discard the timer instead.');
            }

            $this->rules->assertPeriodOpen($entry);
            $this->entries->delete($entryId);
            $this->audit->record($actorId, 'time_entry.deleted', 'time_entry', $entryId, $entry->toArray(), null);

            return $entry;
        });
    }
}
