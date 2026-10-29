<?php

namespace App\Application\Time;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Time\EntrySource;
use App\Domain\Time\TimeEntry;
use App\Domain\Time\TimeEntryRepository;
use Illuminate\Support\Facades\DB;

/**
 * Logs an interval that already happened, for the actor (F-05).
 */
final class LogTimeEntry
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly EntryRules $rules,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, EntryDetails $details): TimeEntry
    {
        if (! $this->authorizer->allows($actorId, 'time.entry.create')) {
            throw new AccessDenied('You may not log time.');
        }

        return DB::transaction(function () use ($actorId, $details): TimeEntry {
            $this->entries->lockOwner($actorId);
            $entry = $this->rules->build($actorId, $details, null, EntrySource::Manual);
            $entry = $entry->withId($this->entries->save($entry));
            $this->audit->record($actorId, 'time_entry.created', 'time_entry', (int) $entry->id, null, $entry->toArray());

            return $entry;
        });
    }
}
