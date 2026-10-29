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
 * Undoes a deletion by saving the entry again for its owner, through every rule of a new entry (F-05).
 */
final class RestoreTimeEntry
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly EntryRules $rules,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $ownerId, EntryDetails $details, EntrySource $source): TimeEntry
    {
        if (! $this->authorizer->allows($actorId, 'time.entry.delete')
            || ($ownerId !== $actorId && ! $this->authorizer->allows($actorId, 'time.entry.scope.all'))) {
            throw new AccessDenied('You may not restore this time entry.');
        }

        return DB::transaction(function () use ($actorId, $ownerId, $details, $source): TimeEntry {
            $this->entries->lockOwner($ownerId);
            $entry = $this->rules->build($ownerId, $details, null, $source);
            $entry = $entry->withId($this->entries->save($entry));
            $this->audit->record($actorId, 'time_entry.restored', 'time_entry', (int) $entry->id, null, $entry->toArray());

            return $entry;
        });
    }
}
