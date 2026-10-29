<?php

use App\Application\Time\AdjustTimerStart;
use App\Application\Time\DiscardTimer;
use App\Application\Time\StartTimer;
use App\Application\Time\StopTimer;
use App\Domain\Access\AccessDenied;
use App\Domain\Time\LongEntryNeedsConfirmation;
use App\Domain\Time\TimeEntryRepository;
use App\Domain\Time\TimeRuleViolation;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Client;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

function saoPaulo(string $moment): CarbonImmutable
{
    return CarbonImmutable::parse($moment, 'America/Sao_Paulo');
}

beforeEach(function () {
    $this->travelTo(saoPaulo('2026-10-28 09:00:20'));
    $this->user = User::factory()->admin()->create();
    $this->client = Client::factory()->create(['name' => 'WIS']);
    $this->category = Category::factory()->create();
});

it('starts a timer with no end and no description yet — F-06', function () {
    $timer = app(StartTimer::class)->handle($this->user->id, $this->client->id, $this->category->id);

    $model = TimeEntry::query()->findOrFail($timer->id);
    expect($model->ended_at)->toBeNull()
        ->and($model->duration_minutes)->toBeNull()
        ->and($model->source)->toBe('timer')
        ->and($model->started_at->toDateTimeString())->toBe('2026-10-28 12:00:00')
        ->and(AuditLog::query()->where('event', 'timer.started')->count())->toBe(1);
});

it('runs a single timer per user, also against two devices at once — RN-14', function () {
    app(StartTimer::class)->handle($this->user->id, $this->client->id, $this->category->id);

    expect(fn () => app(StartTimer::class)->handle($this->user->id, $this->client->id, $this->category->id))
        ->toThrow(TimeRuleViolation::class, 'A timer is already running. Stop it first.')
        ->and(fn () => TimeEntry::factory()->running()->create(['user_id' => $this->user->id]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('turns the unique index into the same refusal when two starts race — RN-14', function () {
    $repository = Mockery::mock(TimeEntryRepository::class);
    $repository->shouldReceive('lockOwner', 'firstOverlapping')->andReturnNull();
    $repository->shouldReceive('runningTimer')->andReturnNull();
    $repository->shouldReceive('save')->andThrow(new UniqueConstraintViolationException('mysql', 'insert', [], new Exception('duplicate')));
    app()->instance(TimeEntryRepository::class, $repository);

    app(StartTimer::class)->handle($this->user->id, $this->client->id, $this->category->id);
})->throws(TimeRuleViolation::class, 'A timer is already running. Stop it first.');

it('lets another user run a timer at the same time — RN-14', function () {
    TimeEntry::factory()->running()->create();

    expect(app(StartTimer::class)->handle($this->user->id, $this->client->id, $this->category->id)->id)->not->toBeNull();
});

it('stops the timer into a regular entry — F-06', function () {
    $timer = app(StartTimer::class)->handle($this->user->id, $this->client->id, $this->category->id);
    $this->travelTo(saoPaulo('2026-10-28 10:15:50'));

    $entry = app(StopTimer::class)->handle($this->user->id, 'Revisar PR', 'MRP-62');

    $model = TimeEntry::query()->findOrFail($timer->id);
    expect($entry->id)->toBe($timer->id)
        ->and($model->duration_minutes)->toBe(75)
        ->and($model->description)->toBe('Revisar PR')
        ->and($model->running_user_id)->toBeNull();
});

it('applies the duration and description rules when stopping — RN-02', function () {
    TimeEntry::factory()->running(saoPaulo('2026-10-28 03:00'))->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);

    expect(fn () => app(StopTimer::class)->handle($this->user->id, '', ''))->toThrow(TimeRuleViolation::class, 'Describe what was done.')
        ->and(fn () => app(StopTimer::class)->handle($this->user->id, 'Deploy', ''))->toThrow(LongEntryNeedsConfirmation::class)
        ->and(app(StopTimer::class)->handle($this->user->id, 'Deploy', '', true)->minutes()?->value)->toBe(360);
});

it('asks for the real end of a timer left running more than 12 hours — F-06', function () {
    TimeEntry::factory()->running(saoPaulo('2026-10-27 18:00'))->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);

    expect(fn () => app(StopTimer::class)->handle($this->user->id, 'Deploy', ''))->toThrow(TimeRuleViolation::class, 'An entry cannot last more than 12 hours.')
        ->and(fn () => app(StopTimer::class)->handle($this->user->id, 'Deploy', '', false, saoPaulo('2026-10-28 10:00')))->toThrow(TimeRuleViolation::class, 'The end cannot be in the future.')
        ->and(app(StopTimer::class)->handle($this->user->id, 'Deploy', '', false, saoPaulo('2026-10-27 19:30'))->minutes()?->value)->toBe(90);
});

it('refuses to stop within the first minute or with no timer', function () {
    app(StartTimer::class)->handle($this->user->id, $this->client->id, $this->category->id);

    expect(fn () => app(StopTimer::class)->handle($this->user->id, 'x', ''))->toThrow(TimeRuleViolation::class, 'Less than a minute has passed. Keep going or discard the timer.');

    app(DiscardTimer::class)->handle($this->user->id);

    expect(fn () => app(StopTimer::class)->handle($this->user->id, 'x', ''))->toThrow(TimeRuleViolation::class, 'No timer is running.')
        ->and(fn () => app(DiscardTimer::class)->handle($this->user->id))->toThrow(TimeRuleViolation::class, 'No timer is running.')
        ->and(fn () => app(AdjustTimerStart::class)->handle($this->user->id, saoPaulo('2026-10-28 08:00')))->toThrow(TimeRuleViolation::class, 'No timer is running.')
        ->and(TimeEntry::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('event', 'timer.discarded')->count())->toBe(1);
});

it('moves the start back, not into the future nor over another entry — F-06, RN-03', function () {
    $timer = app(StartTimer::class)->handle($this->user->id, $this->client->id, $this->category->id);
    TimeEntry::factory()->at('2026-10-28 07:00', '2026-10-28 08:00')->create(['user_id' => $this->user->id]);

    app(AdjustTimerStart::class)->handle($this->user->id, saoPaulo('2026-10-28 08:00'));

    expect(TimeEntry::query()->findOrFail($timer->id)->started_at->toDateTimeString())->toBe('2026-10-28 11:00:00')
        ->and(fn () => app(AdjustTimerStart::class)->handle($this->user->id, saoPaulo('2026-10-28 07:30')))->toThrow(TimeRuleViolation::class, 'Overlaps the entry of :client on :day, :start to :end.')
        ->and(fn () => app(AdjustTimerStart::class)->handle($this->user->id, saoPaulo('2026-10-28 09:30')))->toThrow(TimeRuleViolation::class, 'The start cannot be in the future.');
});

it('refuses every timer action without the permission — RN-18', function (string $action) {
    $id = userWithPermissions(['time.entry.view'])->id;

    match ($action) {
        'start' => app(StartTimer::class)->handle($id, $this->client->id, $this->category->id),
        'stop' => app(StopTimer::class)->handle($id, 'x', ''),
        'adjust' => app(AdjustTimerStart::class)->handle($id, now()->toDateTimeImmutable()),
        'discard' => app(DiscardTimer::class)->handle($id),
    };
})->with(['start', 'stop', 'adjust', 'discard'])->throws(AccessDenied::class);
