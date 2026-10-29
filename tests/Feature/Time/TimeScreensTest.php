<?php

use App\Livewire\Time\TimeEntriesIndex;
use App\Livewire\Time\TimerBar;
use App\Livewire\Time\Today;
use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Client;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

function inSaoPaulo(string $moment): CarbonImmutable
{
    return CarbonImmutable::parse($moment, 'America/Sao_Paulo');
}

beforeEach(function () {
    $this->travelTo(inSaoPaulo('2026-10-28 18:00'));
    $this->user = User::factory()->admin()->create();
    $this->client = Client::factory()->create(['name' => 'WIS']);
    $this->category = Category::factory()->create(['name' => 'Desenvolvimento']);
    $this->actingAs($this->user);
});

describe('navigation and access', function () {
    it('shows Today and Time entries in the menu and opens both — T-02, T-03', function () {
        $this->get(route('home.redirect'))->assertRedirect(route('time.today'));
        $this->get(route('time.today'))->assertOk()->assertSee(route('time-entries.index'))->assertSee(__('No entries today.'));
        $this->get(route('time-entries.index'))->assertOk()->assertSee(__('No entries in this month.'));
    });

    it('answers 403 and hides the menu without permission — RN-18', function () {
        $this->actingAs(User::factory()->create());

        $this->get(route('time.today'))->assertForbidden();
        $this->get(route('time-entries.index'))->assertForbidden();
        $this->get(route('profile.edit'))->assertDontSee(route('time.today'));
    });

    it('shows a read-only Today without the create permission and hides the amounts', function () {
        $viewer = userWithPermissions(['time.entry.view']);
        TimeEntry::factory()->create(['user_id' => $viewer->id, 'client_id' => $this->client->id, 'description' => 'Revisar PR']);
        $this->actingAs($viewer);

        Livewire::test(Today::class)
            ->assertSee('Revisar PR')
            ->assertDontSeeHtml('data-test="start-timer-button"')
            ->assertDontSeeHtml('data-test="new-entry-button"')
            ->assertDontSee('R$ 60,00');
    });

    it('asks for a client before the timer — T-02', function () {
        $this->client->forceFill(['active' => false])->save();

        Livewire::test(Today::class)->assertSee(__('Register your first client'))->assertSee(route('clients.index'));
    });
});

describe('today', function () {
    it('sums the day and suggests the start after the last entry — T-02, F-05', function () {
        TimeEntry::factory()->at('2026-10-28 09:30', '2026-10-28 10:15')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id, 'reference' => 'MRP-62']);
        TimeEntry::factory()->at('2026-10-28 13:10', '2026-10-28 14:10')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id]);
        TimeEntry::factory()->at('2026-10-27 13:10', '2026-10-27 14:10')->create(['user_id' => $this->user->id]);

        Livewire::test(Today::class)
            ->assertSee('1h45')
            ->assertSee('R$ 105,00')
            ->assertSee('MRP-62')
            ->assertSee(__('Repeat last: :entry', ['entry' => 'WIS']))
            ->call('create')
            ->assertSet('editing', true)
            ->assertSet('clientId', (string) $this->client->id)
            ->assertSet('categoryId', (string) $this->category->id)
            ->assertSet('date', '2026-10-28')
            ->assertSet('start', '14:10');
    });

    it('logs an entry from the panel with the live duration — T-04', function () {
        Livewire::test(Today::class)
            ->call('create')
            ->set('clientId', (string) $this->client->id)
            ->set('categoryId', (string) $this->category->id)
            ->set('description', 'Permitir empresas inativas')
            ->set('start', '09:30')
            ->set('end', '10:15')
            ->assertSee('0h45')
            ->assertSee('3 pts')
            ->assertSee('R$ 45,00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false)
            ->assertSee('Permitir empresas inativas');

        expect(TimeEntry::query()->sole()->duration_minutes)->toBe(45);
    });

    it('asks whether it ended the next day — RN-02', function () {
        $component = Livewire::test(Today::class)
            ->call('create')
            ->set('clientId', (string) $this->client->id)
            ->set('categoryId', (string) $this->category->id)
            ->set('description', 'Deploy')
            ->set('date', '2026-10-27')
            ->set('start', '23:00')
            ->set('end', '01:00')
            ->assertSee(__('Ended the next day?'))
            ->call('save')
            ->assertHasErrors(['end']);

        $component->set('endsNextDay', true)->assertSee('2h00')->call('save')->assertHasNoErrors();

        expect(TimeEntry::query()->sole()->duration_minutes)->toBe(120);
    });

    it('confirms an entry over 4 hours before saving — RN-02', function () {
        Livewire::test(Today::class)
            ->call('create')
            ->set('clientId', (string) $this->client->id)
            ->set('categoryId', (string) $this->category->id)
            ->set('description', 'Migração')
            ->set('start', '08:00')
            ->set('end', '13:00')
            ->call('save')
            ->assertSet('longDuration', '5h00')
            ->assertSee(__('Save anyway'))
            ->call('confirmLong')
            ->assertSet('editing', false);

        expect(TimeEntry::query()->count())->toBe(1);
    });

    it('shows the conflicting entry and opens it — RN-03', function () {
        $existing = TimeEntry::factory()->at('2026-10-28 09:00', '2026-10-28 10:00')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id]);

        Livewire::test(Today::class)
            ->call('create')
            ->set('clientId', (string) $this->client->id)
            ->set('categoryId', (string) $this->category->id)
            ->set('description', 'Outra coisa')
            ->set('start', '09:30')
            ->set('end', '10:30')
            ->call('save')
            ->assertHasErrors(['start'])
            ->assertSet('conflictId', $existing->id)
            ->assertSee(__('Open the conflicting entry'))
            ->call('edit', $existing->id)
            ->assertSet('editingId', $existing->id)
            ->assertSet('start', '09:00');
    });

    it('asks for the missing fields and refuses a typed time that does not exist', function (string $property, string $value, string $error) {
        Livewire::test(Today::class)
            ->call('create')
            ->set('clientId', (string) $this->client->id)
            ->set('categoryId', (string) $this->category->id)
            ->set('start', '09:00')
            ->set('end', '10:00')
            ->set($property, $value)
            ->call('save')
            ->assertHasErrors([$error]);
    })->with([
        'no client' => ['clientId', '', 'clientId'],
        'no category' => ['categoryId', '', 'categoryId'],
        'bad start' => ['start', '25:00', 'start'],
        'bad end' => ['end', '9h', 'end'],
        'impossible date' => ['date', '2026-02-30', 'start'],
    ]);

    it('fills the description from an earlier reference and suggests both — T-04', function () {
        TimeEntry::factory()->at('2026-10-20 09:00', '2026-10-20 10:00')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'reference' => 'MRP-73', 'description' => 'Vínculo de nota']);

        Livewire::test(Today::class)
            ->call('create')
            ->assertSet('clientId', (string) $this->client->id)
            ->assertSeeHtml('<option value="MRP-73">')
            ->set('reference', 'MRP-73')
            ->assertSet('description', 'Vínculo de nota')
            ->set('description', 'Outra')
            ->set('reference', 'MRP-73')
            ->assertSet('description', 'Outra');
    });

    it('duplicates an entry with today as the date', function () {
        $entry = TimeEntry::factory()->at('2026-10-20 09:00', '2026-10-20 10:00')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id, 'reference' => 'MRP-90', 'description' => 'Filtrar empresas']);

        Livewire::test(Today::class)
            ->call('duplicate', $entry->id)
            ->assertSet('editingId', null)
            ->assertSet('reference', 'MRP-90')
            ->assertSet('description', 'Filtrar empresas')
            ->assertSet('date', '2026-10-28')
            ->assertSet('end', '');
    });

    it('deletes with undo for a few seconds — F-05', function () {
        $entry = TimeEntry::factory()->at('2026-10-28 09:00', '2026-10-28 10:00')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id, 'description' => 'Revisar']);

        $component = Livewire::test(Today::class)
            ->call('deleteEntry', $entry->id)
            ->assertSee(__('Entry deleted.'))
            ->assertDontSee('Revisar');

        expect(TimeEntry::query()->count())->toBe(0);

        $component->call('undoDelete')->assertSet('deleted', null)->assertSee('Revisar')->call('undoDelete');

        expect(TimeEntry::query()->sole()->description)->toBe('Revisar');
    });

    it('protects the undo snapshot from the browser — SEC-03', function () {
        Livewire::test(Today::class)->set('deleted', ['owner' => 1]);
    })->throws(CannotUpdateLockedPropertyException::class);

    it('shows a closed period entry read-only — RN-10', function () {
        $entry = TimeEntry::factory()->at('2026-10-28 09:00', '2026-10-28 10:00')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id]);
        $entry->billingPeriod->forceFill(['status' => 'closed'])->save();

        Livewire::test(Today::class)
            ->assertSee(__('Period closed: read only'))
            ->assertDontSeeHtml('data-test="entry-actions-'.$entry->id.'"')
            ->call('edit', $entry->id)
            ->assertSet('readOnly', true)
            ->assertSee(__('This entry belongs to a period that is no longer open. Reopen the period to change it.'))
            ->set('start', '09:30')
            ->call('save');

        expect($entry->refresh()->duration_minutes)->toBe(60);
    });

    it('does not open a running timer or a missing entry in the panel', function () {
        $timer = TimeEntry::factory()->running()->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);

        Livewire::test(Today::class)
            ->call('edit', $timer->id)
            ->assertSet('editing', false)
            ->call('edit', 999999)
            ->assertHasErrors(['entry'])
            ->call('duplicate', 999999)
            ->assertSet('editing', false);
    });

    it('reports a rule broken by a delete', function () {
        $entry = TimeEntry::factory()->at('2026-10-28 09:00', '2026-10-28 10:00')->create(['user_id' => $this->user->id]);
        $entry->billingPeriod->forceFill(['status' => 'closed'])->save();

        Livewire::test(Today::class)->call('deleteEntry', $entry->id)->assertHasErrors(['start']);
    });
});

describe('timer on today', function () {
    it('starts with the last client and category, shows the elapsed time and stops — F-06', function () {
        TimeEntry::factory()->at('2026-10-27 09:00', '2026-10-27 10:00')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id]);

        $component = Livewire::test(Today::class)
            ->assertSet('timerClientId', (string) $this->client->id)
            ->call('startTimer')
            ->assertHasNoErrors()
            ->assertSee(__('since :time', ['time' => '18:00']));

        $this->travelTo(inSaoPaulo('2026-10-28 18:40:05'));

        $component->call('openStop')
            ->assertSet('stopping', true)
            ->assertSet('stopEnd', '')
            ->set('stopDescription', 'Revisar PR')
            ->set('stopReference', 'MRP-62')
            ->call('stopTimer')
            ->assertHasNoErrors()
            ->assertSet('stopping', false)
            ->assertSee('MRP-62');

        expect(TimeEntry::query()->whereNull('ended_at')->exists())->toBeFalse();
    });

    it('asks for client and category before starting', function () {
        Livewire::test(Today::class)->call('startTimer')->assertHasErrors(['timerClientId'])
            ->set('timerClientId', (string) $this->client->id)->call('startTimer')->assertHasErrors(['timerCategoryId']);
    });

    it('opens the stop panel from the timer bar link — F-06', function () {
        TimeEntry::factory()->running()->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'description' => 'Rascunho']);

        Livewire::withQueryParams(['stop' => '1'])->test(Today::class)
            ->assertSet('stopping', true)
            ->assertSet('stopDescription', 'Rascunho')
            ->assertSet('stop', false);
    });

    it('asks for the end of a timer running more than 12 hours and confirms over 4 — F-06, RN-02', function () {
        TimeEntry::factory()->running(inSaoPaulo('2026-10-28 03:00'))->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);

        Livewire::test(Today::class)
            ->call('openStop')
            ->assertSet('stopEnd', '2026-10-28T04:00')
            ->set('stopDescription', 'Plantão')
            ->set('stopEnd', 'ontem')
            ->call('stopTimer')
            ->assertHasErrors(['stopEnd'])
            ->set('stopEnd', '2026-10-28T09:00')
            ->call('stopTimer')
            ->assertSet('longTimer', '6h00')
            ->call('stopTimer', true)
            ->assertSet('stopping', false);

        expect(TimeEntry::query()->sole()->duration_minutes)->toBe(360);
    });

    it('shows the rule broken when stopping', function () {
        TimeEntry::factory()->running()->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'description' => '']);

        Livewire::test(Today::class)->call('openStop')->call('stopTimer')->assertHasErrors(['stopDescription']);
    });

    it('adjusts the start and discards the timer — F-06', function () {
        $timer = TimeEntry::factory()->running(inSaoPaulo('2026-10-28 17:00'))->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);

        Livewire::test(Today::class)
            ->call('openAdjust')
            ->assertSet('adjustStart', '2026-10-28T17:00')
            ->set('adjustStart', 'cedo')
            ->call('adjustTimer')
            ->assertHasErrors(['adjustStart'])
            ->set('adjustStart', '2026-10-28T19:00')
            ->call('adjustTimer')
            ->assertHasErrors(['timer'])
            ->set('adjustStart', '2026-10-28T16:30')
            ->call('adjustTimer')
            ->assertHasNoErrors()
            ->assertSet('adjusting', false)
            ->call('discardTimer');

        expect(TimeEntry::query()->find($timer->id))->toBeNull();
    });

    it('ignores the timer actions with no timer running', function () {
        Livewire::test(Today::class)->call('openStop')->assertSet('stopping', false)->call('openAdjust')->assertSet('adjusting', false);
    });

    it('shows the running timer on the other screens only — F-06', function () {
        TimeEntry::factory()->running()->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);

        $this->get(route('time-entries.index'))->assertSeeHtml('data-test="timer-bar"')->assertSee(route('time.today', ['stop' => 1]), false);
        $this->get(route('time.today'))->assertDontSeeHtml('data-test="timer-bar"');
        Livewire::actingAs(userWithPermissions(['time.entry.view']))->test(TimerBar::class)->assertDontSeeHtml('data-test="timer-bar"');
    });
});

describe('time entries list', function () {
    it('groups the month by day with totals and filters — T-03', function () {
        $other = Client::factory()->create(['name' => 'Acme']);
        TimeEntry::factory()->at('2026-10-26 09:00', '2026-10-26 10:30')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'reference' => 'MRP-90', 'description' => 'Filtrar empresas']);
        TimeEntry::factory()->at('2026-10-23 14:00', '2026-10-23 14:45')->create(['user_id' => $this->user->id, 'client_id' => $other->id, 'description' => 'Ajustar seletor']);
        TimeEntry::factory()->at('2026-09-30 14:00', '2026-09-30 14:45')->create(['user_id' => $this->user->id, 'description' => 'Setembro']);

        Livewire::test(TimeEntriesIndex::class)
            ->assertSet('month', '2026-10')
            ->assertSee('2h15')
            ->assertSeeInOrder(['26/10', 'Filtrar empresas', '23/10', 'Ajustar seletor'])
            ->assertDontSee('Setembro')
            ->set('filterClientId', (string) $other->id)
            ->assertSee('Ajustar seletor')->assertDontSee('Filtrar empresas')
            ->set('filterClientId', '')
            ->set('search', 'mrp-9')
            ->assertSee('Filtrar empresas')->assertDontSee('Ajustar seletor')
            ->set('search', '100%')
            ->assertSee(__('Nothing found.'))
            ->call('clearFilters')
            ->assertSee('Ajustar seletor')
            ->set('filterCategoryId', (string) $this->category->id)
            ->assertSee(__('Nothing found.'))
            ->set('month', '2026-09')
            ->set('filterCategoryId', '')
            ->assertSee('Setembro');
    });

    it('falls back to the current month for a broken filter', function () {
        session(['lw'.crc32(TimeEntriesIndex::class.'month') => 'x']);

        Livewire::test(TimeEntriesIndex::class)->set('month', '2026-13')->assertSee(__('No entries in this month.'));
    });

    it('edits an entry from the list — T-04', function () {
        $entry = TimeEntry::factory()->at('2026-10-26 09:00', '2026-10-26 10:30')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id, 'category_id' => $this->category->id]);

        Livewire::test(TimeEntriesIndex::class)
            ->call('edit', $entry->id)
            ->assertSet('date', '2026-10-26')
            ->assertSet('endsNextDay', false)
            ->set('end', '11:00')
            ->call('save')
            ->assertHasNoErrors();

        expect($entry->refresh()->duration_minutes)->toBe(120);
    });

    it('opens an entry that crossed midnight with the next-day mark', function () {
        $entry = TimeEntry::factory()->at('2026-10-26 23:00', '2026-10-27 01:00')->create(['user_id' => $this->user->id]);

        Livewire::test(TimeEntriesIndex::class)->call('edit', $entry->id)->assertSet('endsNextDay', true)->assertSet('end', '01:00');
    });

    it('shows every user only with the scope permission — SEC-03', function () {
        $colleague = User::factory()->create(['name' => 'Colega']);
        TimeEntry::factory()->at('2026-10-26 09:00', '2026-10-26 10:00')->create(['user_id' => $colleague->id, 'description' => 'Do colega']);

        Livewire::test(TimeEntriesIndex::class)->assertSee('Do colega')->assertSee('Colega');

        $this->actingAs(userWithPermissions(['time.entry.view']));
        Livewire::test(TimeEntriesIndex::class)->assertDontSee('Do colega');
    });

    it('opens a closed period entry with its client even when inactive — RN-10, RN-13', function () {
        $entry = TimeEntry::factory()->at('2026-10-26 09:00', '2026-10-26 10:00')->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);
        BillingPeriod::query()->whereKey($entry->billing_period_id)->update(['status' => 'closed']);
        $this->client->forceFill(['active' => false])->save();

        $component = Livewire::test(TimeEntriesIndex::class)->call('edit', $entry->id)->assertSet('readOnly', true);

        expect(array_column($component->instance()->clientOptions(), 'id'))->toContain($this->client->id);
    });
});
