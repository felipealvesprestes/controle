<?php

namespace App\Livewire\Concerns;

use App\Application\Time\DeleteTimeEntry;
use App\Application\Time\EntryDetails;
use App\Application\Time\EntryRules;
use App\Application\Time\LogTimeEntry;
use App\Application\Time\RestoreTimeEntry;
use App\Application\Time\UpdateTimeEntry;
use App\Domain\Billing\BillingPeriodRepository;
use App\Domain\Client\ClientRepository;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Shared\Money;
use App\Domain\Shared\Points;
use App\Domain\Time\EntrySource;
use App\Domain\Time\LongEntryNeedsConfirmation;
use App\Domain\Time\TimeEntry;
use App\Domain\Time\TimeRuleViolation;
use App\Domain\Time\WorkInterval;
use App\Models\Category;
use App\Models\Client;
use App\Models\TimeEntry as TimeEntryModel;
use Carbon\CarbonImmutable;
use Closure;
use DateTimeImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * T-04: the panel to log, change, duplicate and delete an entry, shared by T-02 and T-03 (F-05).
 * Dates and times are typed in the display time zone.
 */
trait EditsTimeEntries
{
    public bool $editing = false;

    /** Null while creating. */
    #[Locked]
    public ?int $editingId = null;

    /** The entry being shown belongs to a period that is no longer open (RN-10). */
    #[Locked]
    public bool $readOnly = false;

    public string $clientId = '';

    public string $categoryId = '';

    public string $reference = '';

    public string $description = '';

    public string $date = '';

    public string $start = '';

    public string $end = '';

    public bool $endsNextDay = false;

    /** Set when the entry lasts more than four hours and waits for the person to confirm (RN-02). */
    #[Locked]
    public ?string $longDuration = null;

    /** The entry in the way of the last save (RN-03). */
    #[Locked]
    public ?int $conflictId = null;

    /**
     * The last deleted entry, offered for undo for a few seconds.
     *
     * @var array{owner: int, client: int, category: int, description: string, reference: string, start: string, end: string, source: string, token: string}|null
     */
    #[Locked]
    public ?array $deleted = null;

    public function create(): void
    {
        $last = $this->lastOwnEntry();
        $this->fillForm(null, $last?->client_id, $last?->category_id, '', '');
    }

    public function duplicate(int $entryId): void
    {
        $entry = $this->attemptVisible($entryId);

        if ($entry !== null) {
            $this->fillForm(null, $entry->clientId, $entry->categoryId, $entry->description, (string) $entry->reference);
        }
    }

    public function edit(int $entryId): void
    {
        $entry = $this->attemptVisible($entryId);

        if ($entry === null || $entry->endedAt === null) {
            return;
        }

        $this->fillForm($entry->id, $entry->clientId, $entry->categoryId, $entry->description, (string) $entry->reference);
        $start = $this->local($entry->startedAt);
        $end = $this->local($entry->endedAt);
        $this->date = $start->format('Y-m-d');
        $this->start = $start->format('H:i');
        $this->end = $end->format('H:i');
        $this->endsNextDay = $end->format('Y-m-d') !== $this->date;
        $period = app(BillingPeriodRepository::class)->find($entry->billingPeriodId);
        $this->readOnly = Gate::denies('time.entry.edit') || ($period !== null && ! $period->status->acceptsEntries());
    }

    public function save(LogTimeEntry $logTimeEntry, UpdateTimeEntry $updateTimeEntry): void
    {
        $this->saveEntry($logTimeEntry, $updateTimeEntry, false);
    }

    public function confirmLong(LogTimeEntry $logTimeEntry, UpdateTimeEntry $updateTimeEntry): void
    {
        $this->saveEntry($logTimeEntry, $updateTimeEntry, true);
    }

    public function deleteEntry(DeleteTimeEntry $deleteTimeEntry, int $entryId): void
    {
        $this->attemptEntry(function () use ($deleteTimeEntry, $entryId): void {
            $entry = $deleteTimeEntry->handle((int) Auth::id(), $entryId);
            $this->deleted = [
                'owner' => $entry->userId,
                'client' => $entry->clientId,
                'category' => $entry->categoryId,
                'description' => $entry->description,
                'reference' => (string) $entry->reference,
                'start' => $entry->startedAt->format(DATE_ATOM),
                'end' => (string) $entry->endedAt?->format(DATE_ATOM),
                'source' => $entry->source->value,
                'token' => bin2hex(random_bytes(4)),
            ];
        }, null);
    }

    public function undoDelete(RestoreTimeEntry $restoreTimeEntry): void
    {
        $deleted = $this->deleted;

        if ($deleted === null) {
            return;
        }

        $this->deleted = null;
        $this->attemptEntry(fn () => $restoreTimeEntry->handle((int) Auth::id(), $deleted['owner'], new EntryDetails(
            $deleted['client'],
            $deleted['category'],
            $deleted['description'],
            $deleted['reference'],
            new DateTimeImmutable($deleted['start']),
            new DateTimeImmutable($deleted['end']),
            true,
        ), EntrySource::from($deleted['source'])), __('Entry restored.'));
    }

    /**
     * Fills the description from an earlier entry with the same reference.
     */
    public function updatedReference(): void
    {
        if ($this->description !== '' || trim($this->reference) === '') {
            return;
        }

        $this->description = $this->ownEntriesOfClient()->where('reference', trim($this->reference))->latest('started_at')->first()->description ?? '';
    }

    /**
     * Earlier references and descriptions of the chosen client, for autocomplete.
     *
     * @return array{references: list<string>, descriptions: list<string>}
     */
    #[Computed]
    public function suggestions(): array
    {
        $recent = $this->ownEntriesOfClient()->latest('started_at')->limit(100)->get(['reference', 'description']);

        return [
            'references' => array_values(array_unique(array_filter($recent->map(fn (TimeEntryModel $entry): ?string => $entry->reference)->all()))),
            'descriptions' => array_values(array_unique($recent->map(fn (TimeEntryModel $entry): string => $entry->description)->all())),
        ];
    }

    /**
     * Active clients, the most used by the person first (RN-13); while editing, the entry's own client too.
     *
     * @return list<array{id: int, name: string, color: string}>
     */
    #[Computed]
    public function clientOptions(): array
    {
        return array_values(Client::query()
            ->where(fn (Builder $query) => $query->where('active', true)->orWhere('id', (int) $this->clientId))
            ->withCount(['timeEntries' => fn (Builder $query) => $query->where('user_id', Auth::id())])
            ->orderByDesc('time_entries_count')->orderBy('name')
            ->get()
            ->map(fn (Client $client): array => ['id' => $client->id, 'name' => $client->name, 'color' => $client->color])
            ->all());
    }

    /**
     * Active categories in their order (RN-13); while editing, the entry's own category too.
     *
     * @return list<array{id: int, name: string, color: string}>
     */
    #[Computed]
    public function categoryOptions(): array
    {
        return array_values(Category::query()
            ->where(fn (Builder $query) => $query->where('active', true)->orWhere('id', (int) $this->categoryId))
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn (Category $category): array => ['id' => $category->id, 'name' => $category->name, 'color' => $category->color])
            ->all());
    }

    /**
     * Live duration line; points and amount only inform, the period rounds (T-04).
     *
     * @return array{duration: string, points: int, amount: string}|null
     */
    #[Computed]
    public function preview(): ?array
    {
        $interval = $this->interval();

        if ($interval === null) {
            return null;
        }

        $minutes = $interval->minutes();
        $rate = app(ClientRepository::class)->rates((int) $this->clientId)->on($interval->start)->rate ?? app(ProviderProfileRepository::class)->billingDefaults()->rate;

        return [
            'duration' => $minutes->formatted(),
            'points' => Points::coveringMinutes($minutes)->value,
            'amount' => Money::fromCentMinutes($rate->centMinutesFor($minutes))->formatted(),
        ];
    }

    /**
     * The end time is earlier than the start: ask whether it ended the next day (RN-02).
     */
    #[Computed]
    public function endsBeforeStart(): bool
    {
        return $this->isTime($this->start) && $this->isTime($this->end) && $this->end <= $this->start;
    }

    private function saveEntry(LogTimeEntry $logTimeEntry, UpdateTimeEntry $updateTimeEntry, bool $longConfirmed): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->resetErrorBag();
        $start = $this->moment($this->date, $this->start);
        $end = $this->moment($this->date, $this->end);

        if ($start === null || $end === null || $this->clientId === '' || $this->categoryId === '') {
            $this->addError($start === null ? 'start' : ($end === null ? 'end' : ($this->clientId === '' ? 'clientId' : 'categoryId')), __('Fill in this field.'));

            return;
        }

        $details = new EntryDetails((int) $this->clientId, (int) $this->categoryId, $this->description, $this->reference, $start, $this->endsNextDay ? $end->modify('+1 day') : $end, $longConfirmed);
        $editingId = $this->editingId;

        $this->attemptEntry(
            fn () => $editingId === null ? $logTimeEntry->handle((int) Auth::id(), $details) : $updateTimeEntry->handle((int) Auth::id(), $editingId, $details),
            __('Entry saved.'),
        );
    }

    private function attemptEntry(Closure $action, ?string $success): void
    {
        $this->longDuration = null;
        $this->conflictId = null;

        try {
            $action();
        } catch (LongEntryNeedsConfirmation $confirmation) {
            $this->longDuration = $confirmation->minutes->formatted();

            return;
        } catch (TimeRuleViolation $violation) {
            $this->resetErrorBag();
            $this->addError(self::FIELDS[$violation->field] ?? $violation->field, __($violation->getMessage(), $violation->replace));
            $this->conflictId = $violation->conflictId;

            return;
        }

        $this->resetErrorBag();
        $this->editing = false;
        $this->entriesChanged();

        if ($success !== null) {
            Flux::toast(variant: 'success', text: $success);
        }
    }

    /** Use case fields and the properties that show their errors. */
    private const array FIELDS = [
        'client_id' => 'clientId',
        'category_id' => 'categoryId',
        'started_at' => 'start',
        'ended_at' => 'end',
    ];

    private function attemptVisible(int $entryId): ?TimeEntry
    {
        try {
            return app(EntryRules::class)->visibleEntry((int) Auth::id(), $entryId);
        } catch (TimeRuleViolation $violation) {
            $this->addError($violation->field, __($violation->getMessage()));

            return null;
        }
    }

    private function fillForm(?int $entryId, ?int $clientId, ?int $categoryId, string $description, string $reference): void
    {
        $this->resetErrorBag();
        $this->editingId = $entryId;
        $this->readOnly = false;
        $this->longDuration = null;
        $this->conflictId = null;
        $this->clientId = $clientId === null ? '' : (string) $clientId;
        $this->categoryId = $categoryId === null ? '' : (string) $categoryId;
        $this->description = $description;
        $this->reference = $reference;
        $this->date = $this->today()->format('Y-m-d');
        $this->start = $this->suggestedStart();
        $this->end = '';
        $this->endsNextDay = false;
        $this->editing = true;
    }

    /**
     * The end of the person's last entry of the day, so entries follow each other (F-05).
     */
    private function suggestedStart(): string
    {
        $today = $this->today();
        $lastEnd = TimeEntryModel::query()->where('user_id', Auth::id())->whereNotNull('ended_at')
            ->where('started_at', '>=', $today->utc())->where('started_at', '<', $today->addDay()->utc())
            ->max('ended_at');

        return is_string($lastEnd) ? CarbonImmutable::parse($lastEnd, 'UTC')->timezone(Config::string('app.display_timezone'))->format('H:i') : '';
    }

    private function lastOwnEntry(): ?TimeEntryModel
    {
        return TimeEntryModel::query()->where('user_id', Auth::id())->latest('started_at')->latest('id')->first();
    }

    /**
     * @return Builder<TimeEntryModel>
     */
    private function ownEntriesOfClient(): Builder
    {
        return TimeEntryModel::query()->where('user_id', Auth::id())->where('client_id', (int) $this->clientId);
    }

    private function interval(): ?WorkInterval
    {
        $start = $this->moment($this->date, $this->start);
        $end = $this->moment($this->date, $this->end);

        if ($start === null || $end === null) {
            return null;
        }

        try {
            return WorkInterval::between($start, $this->endsNextDay ? $end->modify('+1 day') : $end);
        } catch (TimeRuleViolation) {
            return null;
        }
    }

    private function moment(string $date, string $time): ?CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1 || ! $this->isTime($time)) {
            return null;
        }

        $moment = CarbonImmutable::createFromFormat('!Y-m-d H:i', $date.' '.$time, Config::string('app.display_timezone'));

        return $moment instanceof CarbonImmutable && $moment->format('Y-m-d H:i') === $date.' '.$time ? $moment : null;
    }

    private function isTime(string $time): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::today(Config::string('app.display_timezone'));
    }

    private function local(DateTimeImmutable $moment): CarbonImmutable
    {
        return CarbonImmutable::instance($moment)->timezone(Config::string('app.display_timezone'));
    }

    /**
     * Forgets the cached lists after a write.
     */
    abstract protected function entriesChanged(): void;
}
