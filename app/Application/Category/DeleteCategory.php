<?php

namespace App\Application\Category;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Category\CategoryRepository;
use App\Domain\Category\CategoryRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a category created by mistake (RN-13).
 * Time entries arrive with F-05, which adds the "has entries, deactivate instead" refusal here.
 */
final class DeleteCategory
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly CategoryRepository $categories,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $categoryId): void
    {
        if (! $this->authorizer->allows($actorId, 'category.delete')) {
            throw new AccessDenied('You may not delete categories.');
        }

        $category = $this->categories->find($categoryId) ?? throw new CategoryRuleViolation('category', 'This category no longer exists.');

        DB::transaction(function () use ($actorId, $categoryId, $category): void {
            $this->categories->delete($categoryId);
            $this->audit->record($actorId, 'category.deleted', 'category', $categoryId, $category->toArray(), null);
        });
    }
}
