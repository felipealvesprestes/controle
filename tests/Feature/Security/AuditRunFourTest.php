<?php

use App\Application\Category\DeleteCategory;
use App\Application\Client\DeleteClient;
use App\Domain\Category\CategoryRuleViolation;
use App\Domain\Client\ClientRuleViolation;
use App\Domain\Time\TimeEntryRepository;
use App\Models\TimeEntry;
use App\Models\User;

/**
 * The entry lands between the "has entries" check and the delete, as when someone logs time while the client is being deleted.
 */
function entryLoggedDuringTheCheck(): void
{
    $repository = Mockery::mock(TimeEntryRepository::class);
    $repository->shouldReceive('clientHasEntries', 'categoryHasEntries')->andReturnFalse();
    app()->instance(TimeEntryRepository::class, $repository);
}

it('refuses to delete a client that got an entry during the delete, instead of a 500 — A-16', function () {
    $entry = TimeEntry::factory()->create();
    entryLoggedDuringTheCheck();

    expect(fn () => app(DeleteClient::class)->handle(User::factory()->admin()->create()->id, $entry->client_id))
        ->toThrow(ClientRuleViolation::class, 'This client has time entries. Deactivate it instead.')
        ->and($entry->client()->exists())->toBeTrue();
});

it('refuses to delete a category that got an entry during the delete, instead of a 500 — A-16', function () {
    $entry = TimeEntry::factory()->create();
    entryLoggedDuringTheCheck();

    expect(fn () => app(DeleteCategory::class)->handle(User::factory()->admin()->create()->id, $entry->category_id))
        ->toThrow(CategoryRuleViolation::class, 'This category has time entries. Deactivate it instead.')
        ->and($entry->category()->exists())->toBeTrue();
});
