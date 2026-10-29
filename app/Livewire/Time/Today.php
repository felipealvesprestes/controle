<?php

namespace App\Livewire\Time;

use App\Application\Time\AdjustTimerStart;
use App\Application\Time\DiscardTimer;
use App\Application\Time\StartTimer;
use App\Application\Time\StopTimer;
use App\Domain\Shared\Minutes;
use App\Domain\Shared\Money;
use App\Domain\Time\LongEntryNeedsConfirmation;
use App\Domain\Time\TimeRuleViolation;
use App\Domain\Time\WorkInterval;
use App\Livewire\Concerns\EditsTimeEntries;
use App\Models\Client;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Closure;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * T-02: the timer and what the person did today (F-05, F-06).
 *
 * @property-read TimeEntry|null $timer
 * @property-read list<TimeEntry> $entries
 */
class Today extends Component
{
    use EditsTimeEntries;

    public string $timerClientId = '';

    public string $timerCategoryId = '';

    /** Opened from the timer bar of another screen. */
    #[Url(except: false)]
    public bool $stop = false;

    public bool $stopping = false;

    public string $stopDescription = '';

    public string $stopReference = '';

    /** Local date and time, for a timer left running too long. */
    public string $stopEnd = '';

    public bool $adjusting = false;

    /** Local date and time. */
    public string $adjustStart = '';

    /** Set when the timer ran more than four hours and waits for the person to confirm (RN-02). */
    #[Locked]
    public ?string $longTimer = null;

    public function mount(): void
    {
        $last = TimeEntry::query()->where('user_id', Auth::id())->latest('started_at')->latest('id')->first();
        $this->timerClientId = $last === null ? '' : (string) $last->client_id;
        $this->timerCategoryId = $last === null ? '' : (string) $last->category_id;

        if ($this->stop && $this->timer !== null) {
            $this->openStop();
        }

        $this->stop = false;
    }

    #[Computed]
    public function timer(): ?TimeEntry
    {
        return TimeEntry::query()->with(['client', 'category'])->where('user_id', Auth::id())->whereNull('ended_at')->first();
    }

    /**
     * @return list<TimeEntry>
     */
    #[Computed]
    public function entries(): array
    {
        $today = CarbonImmutable::today(Config::string('app.display_timezone'));

        return array_values(TimeEntry::query()->with(['client', 'category', 'billingPeriod'])
            ->where('user_id', Auth::id())->whereNotNull('ended_at')
            ->where('started_at', '>=', $today->utc())->where('started_at', '<', $today->addDay()->utc())
            ->orderBy('started_at')->get()->all());
    }

    /**
     * Hours and partial amount of today, every client together.
     *
     * @return array{duration: string, amount: string}
     */
    #[Computed]
    public function summary(): array
    {
        $minutes = Minutes::zero();
        $centMinutes = 0;

        foreach ($this->entries as $entry) {
            $minutes = $minutes->plus(Minutes::of((int) $entry->duration_minutes));
            $centMinutes += $entry->rate_cents * (int) $entry->duration_minutes;
        }

        return ['duration' => $minutes->formatted(), 'amount' => Money::fromCentMinutes($centMinutes)->formatted()];
    }

    #[Computed]
    public function hasActiveClients(): bool
    {
        return Client::query()->where('active', true)->exists();
    }

    #[Computed]
    public function lastEntry(): ?TimeEntry
    {
        return TimeEntry::query()->with('client')->where('user_id', Auth::id())->whereNotNull('ended_at')->latest('started_at')->latest('id')->first();
    }

    public function startTimer(StartTimer $startTimer): void
    {
        if ($this->timerClientId === '' || $this->timerCategoryId === '') {
            $this->addError($this->timerClientId === '' ? 'timerClientId' : 'timerCategoryId', __('Fill in this field.'));

            return;
        }

        $this->attemptTimer(fn () => $startTimer->handle((int) Auth::id(), (int) $this->timerClientId, (int) $this->timerCategoryId), __('Timer started.'));
    }

    public function openStop(): void
    {
        $timer = $this->timer;

        if ($timer === null) {
            return;
        }

        $this->resetErrorBag();
        $this->longTimer = null;
        $this->stopDescription = $timer->description;
        $this->stopReference = (string) $timer->reference;
        $this->stopEnd = '';

        // Twelve hours or more cannot be logged: ask for the real end (F-06).
        if ($timer->started_at->diffInMinutes(CarbonImmutable::now()) > WorkInterval::MAX_MINUTES) {
            $this->stopEnd = $timer->started_at->timezone(Config::string('app.display_timezone'))->addHour()->format('Y-m-d\TH:i');
        }

        $this->stopping = true;
    }

    public function stopTimer(StopTimer $stopTimer, bool $longConfirmed = false): void
    {
        $end = $this->stopEnd === '' ? null : $this->localDateTime($this->stopEnd);

        if ($this->stopEnd !== '' && $end === null) {
            $this->addError('stopEnd', __('Fill in this field.'));

            return;
        }

        $this->attemptTimer(fn () => $stopTimer->handle((int) Auth::id(), $this->stopDescription, $this->stopReference, $longConfirmed, $end), __('Entry saved.'));
    }

    public function openAdjust(): void
    {
        $timer = $this->timer;

        if ($timer !== null) {
            $this->resetErrorBag();
            $this->adjustStart = $timer->started_at->timezone(Config::string('app.display_timezone'))->format('Y-m-d\TH:i');
            $this->adjusting = true;
        }
    }

    public function adjustTimer(AdjustTimerStart $adjustTimerStart): void
    {
        $start = $this->localDateTime($this->adjustStart);

        if ($start === null) {
            $this->addError('adjustStart', __('Fill in this field.'));

            return;
        }

        $this->attemptTimer(fn () => $adjustTimerStart->handle((int) Auth::id(), $start), __('Start adjusted.'));
    }

    public function discardTimer(DiscardTimer $discardTimer): void
    {
        $this->attemptTimer(fn () => $discardTimer->handle((int) Auth::id()), __('Timer discarded.'));
    }

    protected function entriesChanged(): void
    {
        unset($this->timer, $this->entries, $this->summary, $this->lastEntry);
    }

    private function attemptTimer(Closure $action, string $success): void
    {
        $this->longTimer = null;

        try {
            $action();
        } catch (LongEntryNeedsConfirmation $confirmation) {
            $this->longTimer = $confirmation->minutes->formatted();

            return;
        } catch (TimeRuleViolation $violation) {
            $this->resetErrorBag();
            $this->addError(self::TIMER_FIELDS[$violation->field] ?? $violation->field, __($violation->getMessage(), $violation->replace));

            return;
        }

        $this->resetErrorBag();
        $this->stopping = false;
        $this->adjusting = false;
        $this->entriesChanged();
        Flux::toast(variant: 'success', text: $success);
    }

    /** Use case fields and the properties that show their errors on this screen. */
    private const array TIMER_FIELDS = [
        'client_id' => 'timerClientId',
        'category_id' => 'timerCategoryId',
        'started_at' => 'timer',
        'ended_at' => 'stopEnd',
        'description' => 'stopDescription',
        'reference' => 'stopReference',
    ];

    private function localDateTime(string $value): ?CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}T([01]\d|2[0-3]):[0-5]\d$/', $value) !== 1) {
            return null;
        }

        $moment = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $value, Config::string('app.display_timezone'));

        return $moment instanceof CarbonImmutable && $moment->format('Y-m-d\TH:i') === $value ? $moment : null;
    }

    public function render(): View
    {
        return view('livewire.time.today')->title(__('Today'));
    }
}
