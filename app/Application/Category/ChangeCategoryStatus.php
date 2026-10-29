<?php

namespace App\Application\Category;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Category\CategoryRepository;
use App\Domain\Category\CategoryRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Deactivates or reactivates a category. An inactive category leaves the choices of new entries
 * and keeps its history; a reactivated one goes to the end of the list (RN-13).
 */
final class ChangeCategoryStatus
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly CategoryRepository $categories,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $categoryId, bool $active): void
    {
        if (! $this->authorizer->allows($actorId, 'category.status.edit')) {
            throw new AccessDenied('You may not change the category status.');
        }

        $category = $this->categories->find($categoryId) ?? throw new CategoryRuleViolation('category', 'This category no longer exists.');

        if ($category->active === $active) {
            return;
        }

        DB::transaction(function () use ($actorId, $categoryId, $category, $active): void {
            $this->categories->save($category->with(active: $active, sortOrder: $active ? $this->categories->nextSortOrder() : null));
            $this->audit->record($actorId, $active ? 'category.reactivated' : 'category.deactivated', 'category', $categoryId);
        });
    }
}
