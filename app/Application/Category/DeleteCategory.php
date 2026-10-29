<?php

namespace App\Application\Category;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Category\CategoryRepository;
use App\Domain\Category\CategoryRuleViolation;
use App\Domain\Time\TimeEntryRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a category created by mistake; one with time entries can only be deactivated (RN-13).
 */
final class DeleteCategory
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly CategoryRepository $categories,
        private readonly TimeEntryRepository $entries,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $categoryId): void
    {
        if (! $this->authorizer->allows($actorId, 'category.delete')) {
            throw new AccessDenied('You may not delete categories.');
        }

        $category = $this->categories->find($categoryId) ?? throw new CategoryRuleViolation('category', 'This category no longer exists.');

        $hasEntries = new CategoryRuleViolation('category', 'This category has time entries. Deactivate it instead.');

        try {
            DB::transaction(function () use ($actorId, $categoryId, $category, $hasEntries): void {
                if ($this->entries->categoryHasEntries($categoryId)) {
                    throw $hasEntries;
                }

                $this->categories->delete($categoryId);
                $this->audit->record($actorId, 'category.deleted', 'category', $categoryId, $category->toArray(), null);
            });
        } catch (QueryException $exception) {
            // MySQL 1451: an entry arrived between the check and the delete, and the foreign key kept the category (A-16).
            throw ($exception->errorInfo[1] ?? null) === 1451 ? $hasEntries : $exception;
        }
    }
}
