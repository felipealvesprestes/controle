<?php

use App\Application\Time\DeleteTimeEntry;
use App\Application\Time\EntryDetails;
use App\Application\Time\RestoreTimeEntry;
use App\Application\Time\UpdateTimeEntry;
use App\Domain\Access\AccessDenied;
use App\Domain\Time\EntrySource;
use App\Domain\Time\TimeRuleViolation;
use App\Livewire\Time\TimeEntriesIndex;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $this->worker = userWithPermissions(['time.entry.create', 'time.entry.edit', 'time.entry.delete']);
    $this->theirs = TimeEntry::factory()->create(['description' => 'Entrada de outra pessoa']);
});

it('reads another user\'s entry as missing without the scope permission — SEC-03', function () {
    $details = new EntryDetails($this->theirs->client_id, $this->theirs->category_id, 'x', '', CarbonImmutable::now()->subHours(3), CarbonImmutable::now()->subHours(2));

    expect(fn () => app(UpdateTimeEntry::class)->handle($this->worker->id, $this->theirs->id, $details))->toThrow(TimeRuleViolation::class, 'This time entry no longer exists.')
        ->and(fn () => app(DeleteTimeEntry::class)->handle($this->worker->id, $this->theirs->id))->toThrow(TimeRuleViolation::class, 'This time entry no longer exists.')
        ->and(fn () => app(RestoreTimeEntry::class)->handle($this->worker->id, $this->theirs->user_id, $details, EntrySource::Manual))->toThrow(AccessDenied::class)
        ->and($this->theirs->fresh()?->description)->toBe('Entrada de outra pessoa');
});

it('does not open, duplicate or delete another user\'s entry through the screen — SEC-03', function () {
    $this->actingAs($this->worker);

    Livewire::test(TimeEntriesIndex::class)
        ->call('edit', $this->theirs->id)
        ->assertSet('editing', false)
        ->assertDontSee('Entrada de outra pessoa')
        ->call('duplicate', $this->theirs->id)
        ->assertSet('description', '')
        ->call('deleteEntry', $this->theirs->id)
        ->assertHasErrors(['entry']);

    expect($this->theirs->fresh())->not->toBeNull();
});

it('does not let the browser pick the entry being saved — SEC-03', function () {
    $this->actingAs($this->worker);

    Livewire::test(TimeEntriesIndex::class)->set('editingId', $this->theirs->id);
})->throws(CannotUpdateLockedPropertyException::class);
