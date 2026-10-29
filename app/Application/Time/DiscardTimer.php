<?php

namespace App\Application\Time;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Time\TimeEntryRepository;
use App\Domain\Time\TimeRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Throws away the running timer without logging anything (F-06).
 */
final class DiscardTimer
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId): void
    {
        if (! $this->authorizer->allows($actorId, 'time.entry.create')) {
            throw new AccessDenied('You may not log time.');
        }

        DB::transaction(function () use ($actorId): void {
            $this->entries->lockOwner($actorId);
            $timer = $this->entries->runningTimer($actorId) ?? throw new TimeRuleViolation('timer', 'No timer is running.');
            $this->entries->delete((int) $timer->id);
            $this->audit->record($actorId, 'timer.discarded', 'time_entry', (int) $timer->id, $timer->toArray(), null);
        });
    }
}
