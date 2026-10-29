<?php

namespace App\Application\Time;

use App\Domain\Access\Authorizer;
use App\Domain\Billing\BillingPeriodRepository;
use App\Domain\Category\CategoryRepository;
use App\Domain\Client\ClientRepository;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Time\EntrySource;
use App\Domain\Time\LongEntryNeedsConfirmation;
use App\Domain\Time\TimeEntry;
use App\Domain\Time\TimeEntryRepository;
use App\Domain\Time\TimeRuleViolation;
use App\Domain\Time\WorkInterval;
use DateTimeImmutable;

/**
 * The checks every write of an entry or timer goes through, shared by the use cases of F-05 and F-06.
 */
final class EntryRules
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
        private readonly CategoryRepository $categories,
        private readonly ProviderProfileRepository $provider,
        private readonly TimeEntryRepository $entries,
        private readonly BillingPeriodRepository $periods,
        private readonly Clock $clock,
    ) {}

    /**
     * The entry, when the actor may see it: their own, or anyone's with the scope permission (SEC-03).
     * Someone else's entry reads as missing, so its id reveals nothing.
     */
    public function visibleEntry(int $actorId, int $entryId): TimeEntry
    {
        $entry = $this->entries->find($entryId);

        if ($entry === null || ($entry->userId !== $actorId && ! $this->authorizer->allows($actorId, 'time.entry.scope.all'))) {
            throw new TimeRuleViolation('entry', 'This time entry no longer exists.');
        }

        return $entry;
    }

    /**
     * Refuses an entry whose period is no longer open (RN-10).
     */
    public function assertPeriodOpen(TimeEntry $entry): void
    {
        $period = $this->periods->find($entry->billingPeriodId);

        if ($period !== null && ! $period->status->acceptsEntries()) {
            throw $this->closedPeriod($entry->clientId, $period->month);
        }
    }

    /**
     * Validates the details and returns the entry to save, with its period and rate (RN-02 to RN-04, RN-08, RN-10, RN-13).
     * Call it inside a transaction, after locking the owner.
     *
     * @param  TimeEntry|null  $existing  The entry being changed, which keeps its id and may keep an inactive client or category.
     */
    public function build(int $ownerId, EntryDetails $details, ?TimeEntry $existing, EntrySource $source): TimeEntry
    {
        $client = $this->clients->find($details->clientId);

        if ($client === null || (! $client->active && $existing?->clientId !== $client->id)) {
            throw new TimeRuleViolation('client_id', 'Choose an active client.');
        }

        $category = $this->categories->find($details->categoryId);

        if ($category === null || (! $category->active && $existing?->categoryId !== $category->id)) {
            throw new TimeRuleViolation('category_id', 'Choose an active category.');
        }

        $description = trim((string) preg_replace('/\s+/u', ' ', $details->description));

        if ($description === '' && $details->endedAt !== null) {
            throw new TimeRuleViolation('description', 'Describe what was done.');
        }

        if (mb_strlen($description) > TimeEntry::DESCRIPTION_LIMIT) {
            throw new TimeRuleViolation('description', 'Too long for this field.');
        }

        $reference = trim($details->reference);

        if (mb_strlen($reference) > TimeEntry::REFERENCE_LIMIT) {
            throw new TimeRuleViolation('reference', 'Too long for this field.');
        }

        $start = $this->clock->local(WorkInterval::toMinute($details->startedAt));
        $end = null;

        if ($details->endedAt !== null) {
            $interval = WorkInterval::between($start, $this->clock->local($details->endedAt));
            $end = $interval->end;

            if ($interval->needsConfirmation() && ! $details->longConfirmed) {
                throw new LongEntryNeedsConfirmation($interval->minutes());
            }
        }

        $conflict = $this->entries->firstOverlapping($ownerId, $start, $end, $existing?->id);

        if ($conflict !== null) {
            throw $this->overlap($conflict);
        }

        $period = $this->periods->forMonth($details->clientId, $start);

        if (! $period->status->acceptsEntries()) {
            throw $this->closedPeriod($details->clientId, $period->month);
        }

        $rate = $this->clients->rates($details->clientId)->on($start)->rate ?? $this->provider->billingDefaults()->rate;

        return new TimeEntry($existing?->id, $ownerId, $details->clientId, $details->categoryId, $period->id, $description, $reference === '' ? null : $reference, $start, $end, $rate, $source);
    }

    private function overlap(TimeEntry $conflict): TimeRuleViolation
    {
        $start = $this->clock->local($conflict->startedAt);

        return new TimeRuleViolation('started_at', 'Overlaps the entry of :client on :day, :start to :end.', [
            'client' => $this->clients->find($conflict->clientId)->name ?? '',
            'day' => $start->format('d/m'),
            'start' => $start->format('H:i'),
            'end' => $conflict->endedAt === null ? '…' : $this->clock->local($conflict->endedAt)->format('H:i'),
        ], $conflict->id);
    }

    private function closedPeriod(int $clientId, DateTimeImmutable $month): TimeRuleViolation
    {
        return new TimeRuleViolation('started_at', 'The period of :month for :client is closed.', [
            'month' => $month->format('m/Y'),
            'client' => $this->clients->find($clientId)->name ?? '',
        ]);
    }
}
