<?php

use App\Application\Category\DeleteCategory;
use App\Application\Client\DeleteClient;
use App\Application\Time\DeleteTimeEntry;
use App\Application\Time\EntryDetails;
use App\Application\Time\LogTimeEntry;
use App\Application\Time\RestoreTimeEntry;
use App\Application\Time\UpdateTimeEntry;
use App\Domain\Access\AccessDenied;
use App\Domain\Category\CategoryRuleViolation;
use App\Domain\Client\ClientRuleViolation;
use App\Domain\Time\EntrySource;
use App\Domain\Time\LongEntryNeedsConfirmation;
use App\Domain\Time\TimeRuleViolation;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Client;
use App\Models\ClientRate;
use App\Models\ProviderProfile;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;

function local(string $moment): CarbonImmutable
{
    return CarbonImmutable::parse($moment, 'America/Sao_Paulo');
}

/**
 * @param  array<string, mixed>  $overrides
 */
function entryDetails(Client $client, Category $category, string $start, string $end, array $overrides = []): EntryDetails
{
    return new EntryDetails(
        $overrides['client'] ?? $client->id,
        $overrides['category'] ?? $category->id,
        $overrides['description'] ?? 'Ajustar seletor',
        $overrides['reference'] ?? 'MRP-91',
        local($start),
        local($end),
        $overrides['long'] ?? false,
    );
}

beforeEach(function () {
    $this->travelTo(local('2026-10-28 18:00'));
    $this->user = User::factory()->admin()->create();
    $this->client = Client::factory()->create(['name' => 'WIS']);
    $this->category = Category::factory()->create(['name' => 'Desenvolvimento']);
});

describe('logging', function () {
    it('logs an entry across midnight on the day of the start — RN-02, RN-08', function () {
        $entry = app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-31 23:00', '2026-11-01 01:00'));

        $model = TimeEntry::query()->findOrFail($entry->id);
        expect($model->duration_minutes)->toBe(120)
            ->and($model->started_at->toDateTimeString())->toBe('2026-11-01 02:00:00')
            ->and($model->billingPeriod->month->toDateString())->toBe('2026-10-01')
            ->and($model->source)->toBe('manual')
            ->and($model->reference)->toBe('MRP-91')
            ->and(AuditLog::query()->where('event', 'time_entry.created')->sole()->subject_id)->toBe($entry->id);
    });

    it('creates the period of the month on the first entry and reuses it — RN-08', function () {
        app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-01 09:00', '2026-10-01 10:00'));
        app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-02 09:00', '2026-10-02 10:00'));

        expect(BillingPeriod::query()->where('client_id', $this->client->id)->count())->toBe(1);
    });

    it('records the client rate valid on the day of the start, or the provider default — RN-04', function () {
        $rate = new ClientRate;
        $rate->forceFill(['client_id' => $this->client->id, 'rate_cents' => 7500, 'valid_from' => '2026-10-15'])->save();
        ProviderProfile::query()->forceCreate(['default_rate_cents' => 5000, 'default_rounding_policy' => 'per_period', 'tax_regime' => 'simples_me_epp']);

        $before = app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-14 09:00', '2026-10-14 10:00'));
        $after = app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-15 09:00', '2026-10-15 10:00'));

        expect($before->rate->perHour->cents)->toBe(5000)
            ->and($after->rate->perHour->cents)->toBe(7500);
    });

    it('refuses an overlap with the conflicting entry and accepts touching ends — RN-03', function () {
        $existing = TimeEntry::factory()->at('2026-10-28 09:00', '2026-10-28 10:00')->create(['user_id' => $this->user->id, 'client_id' => Client::factory()->create(['name' => 'Outro'])->id]);

        $touching = app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-28 10:00', '2026-10-28 11:00'));

        try {
            app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-28 09:30', '2026-10-28 09:45'));
            $this->fail('The overlap was accepted.');
        } catch (TimeRuleViolation $violation) {
            expect($violation->conflictId)->toBe($existing->id)
                ->and(__($violation->getMessage(), $violation->replace))->toBe('Sobrepõe o lançamento de Outro em 28/10, das 09:00 às 10:00.');
        }

        expect($touching->id)->not->toBeNull();
    });

    it('lets two users log the same interval — RN-03', function () {
        TimeEntry::factory()->at('2026-10-28 09:00', '2026-10-28 10:00')->create();

        expect(app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-28 09:00', '2026-10-28 10:00'))->id)->not->toBeNull();
    });

    it('treats the running timer as reaching into the future — RN-03', function () {
        TimeEntry::factory()->running(local('2026-10-28 16:00'))->create(['user_id' => $this->user->id]);

        app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-28 17:00', '2026-10-28 17:30'));
    })->throws(TimeRuleViolation::class, 'Overlaps the entry of :client on :day, :start to :end.');

    it('asks to confirm more than 4 hours and saves once confirmed — RN-02', function () {
        expect(fn () => app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-28 08:00', '2026-10-28 13:00')))
            ->toThrow(LongEntryNeedsConfirmation::class);

        $entry = app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-28 08:00', '2026-10-28 13:00', ['long' => true]));

        expect($entry->minutes()?->value)->toBe(300);
    });

    it('refuses an inactive client or category and a missing description — RN-13', function (string $field, Closure $overrides, string $message) {
        try {
            app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-10-28 09:00', '2026-10-28 10:00', $overrides()));
            $this->fail('The entry was accepted.');
        } catch (TimeRuleViolation $violation) {
            expect($violation->field)->toBe($field)->and($violation->getMessage())->toBe($message);
        }
    })->with([
        'inactive client' => ['client_id', fn () => ['client' => Client::factory()->inactive()->create()->id], 'Choose an active client.'],
        'missing client' => ['client_id', fn () => ['client' => 999999], 'Choose an active client.'],
        'inactive category' => ['category_id', fn () => ['category' => Category::factory()->inactive()->create()->id], 'Choose an active category.'],
        'blank description' => ['description', fn () => ['description' => "  \n "], 'Describe what was done.'],
        'long description' => ['description', fn () => ['description' => str_repeat('a', 256)], 'Too long for this field.'],
        'long reference' => ['reference', fn () => ['reference' => str_repeat('A', 41)], 'Too long for this field.'],
    ]);

    it('refuses an entry in a closed period — RN-10', function () {
        BillingPeriod::factory()->closed()->create(['client_id' => $this->client->id, 'month' => '2026-09-01']);

        app(LogTimeEntry::class)->handle($this->user->id, entryDetails($this->client, $this->category, '2026-09-30 09:00', '2026-09-30 10:00'));
    })->throws(TimeRuleViolation::class, 'The period of :month for :client is closed.');

    it('refuses without the permission — RN-18', function () {
        app(LogTimeEntry::class)->handle(User::factory()->create()->id, entryDetails($this->client, $this->category, '2026-10-28 09:00', '2026-10-28 10:00'));
    })->throws(AccessDenied::class);
});

describe('changing and deleting', function () {
    beforeEach(function () {
        $this->entry = TimeEntry::factory()->at('2026-10-28 09:00', '2026-10-28 10:00')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id]);
    });

    it('changes an entry, keeping its own inactive client, without counting itself as an overlap — RN-03, RN-13', function () {
        $this->client->forceFill(['active' => false])->save();

        $entry = app(UpdateTimeEntry::class)->handle($this->user->id, $this->entry->id, entryDetails($this->client, $this->category, '2026-10-28 09:30', '2026-10-28 10:15'));

        expect($entry->id)->toBe($this->entry->id)
            ->and($this->entry->refresh()->duration_minutes)->toBe(45)
            ->and(AuditLog::query()->where('event', 'time_entry.updated')->count())->toBe(1);
    });

    it('moves an entry to the period of its new month — RN-08', function () {
        app(UpdateTimeEntry::class)->handle($this->user->id, $this->entry->id, entryDetails($this->client, $this->category, '2026-11-02 09:00', '2026-11-02 10:00'));

        expect($this->entry->refresh()->billingPeriod->month->toDateString())->toBe('2026-11-01');
    });

    it('neither changes nor deletes an entry of a closed period — RN-10', function () {
        $this->entry->billingPeriod->forceFill(['status' => 'closed'])->save();

        expect(fn () => app(UpdateTimeEntry::class)->handle($this->user->id, $this->entry->id, entryDetails($this->client, $this->category, '2026-10-28 09:00', '2026-10-28 09:30')))
            ->toThrow(TimeRuleViolation::class, 'The period of :month for :client is closed.')
            ->and(fn () => app(DeleteTimeEntry::class)->handle($this->user->id, $this->entry->id))
            ->toThrow(TimeRuleViolation::class, 'The period of :month for :client is closed.')
            ->and($this->entry->fresh())->not->toBeNull();
    });

    it('deletes an entry and restores it for its owner — F-05', function () {
        $other = User::factory()->create();
        $theirs = TimeEntry::factory()->at('2026-10-28 11:00', '2026-10-28 12:00')->create(['user_id' => $other->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id, 'source' => 'timer']);

        $deleted = app(DeleteTimeEntry::class)->handle($this->user->id, $theirs->id);
        expect(TimeEntry::query()->find($theirs->id))->toBeNull();

        $restored = app(RestoreTimeEntry::class)->handle($this->user->id, $other->id, entryDetails($this->client, $this->category, '2026-10-28 11:00', '2026-10-28 12:00'), EntrySource::from($deleted->source->value));

        expect($restored->userId)->toBe($other->id)
            ->and(TimeEntry::query()->findOrFail($restored->id)->source)->toBe('timer')
            ->and(AuditLog::query()->whereIn('event', ['time_entry.deleted', 'time_entry.restored'])->count())->toBe(2);
    });

    it('refuses to change or delete the running timer as an entry', function () {
        $timer = TimeEntry::factory()->running()->create(['user_id' => $this->user->id]);

        expect(fn () => app(UpdateTimeEntry::class)->handle($this->user->id, $timer->id, entryDetails($this->client, $this->category, '2026-10-28 11:00', '2026-10-28 12:00')))
            ->toThrow(TimeRuleViolation::class, 'Stop the timer before changing this entry.')
            ->and(fn () => app(DeleteTimeEntry::class)->handle($this->user->id, $timer->id))
            ->toThrow(TimeRuleViolation::class, 'Stop or discard the timer instead.');
    });

    it('refuses without the permissions — RN-18', function () {
        $viewer = userWithPermissions(['time.entry.view']);
        $details = entryDetails($this->client, $this->category, '2026-10-28 09:00', '2026-10-28 10:00');

        expect(fn () => app(UpdateTimeEntry::class)->handle($viewer->id, $this->entry->id, $details))->toThrow(AccessDenied::class)
            ->and(fn () => app(DeleteTimeEntry::class)->handle($viewer->id, $this->entry->id))->toThrow(AccessDenied::class)
            ->and(fn () => app(RestoreTimeEntry::class)->handle($viewer->id, $viewer->id, $details, EntrySource::Manual))->toThrow(AccessDenied::class);
    });

    it('keeps a client or category with entries; only deactivation is left — RN-13', function () {
        expect(fn () => app(DeleteClient::class)->handle($this->user->id, $this->client->id))->toThrow(ClientRuleViolation::class, 'This client has time entries. Deactivate it instead.')
            ->and(fn () => app(DeleteCategory::class)->handle($this->user->id, $this->category->id))->toThrow(CategoryRuleViolation::class, 'This category has time entries. Deactivate it instead.');
    });

    it('deletes a client whose entries are gone, with its empty periods — RN-13', function () {
        app(DeleteTimeEntry::class)->handle($this->user->id, $this->entry->id);

        app(DeleteClient::class)->handle($this->user->id, $this->client->id);

        expect(Client::query()->find($this->client->id))->toBeNull()
            ->and(BillingPeriod::query()->where('client_id', $this->client->id)->exists())->toBeFalse();
    });
});
