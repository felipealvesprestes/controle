<?php

namespace App\Application\Category;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Category\Category;
use App\Domain\Category\CategoryRepository;
use App\Domain\Category\CategoryRuleViolation;
use App\Domain\Shared\Color;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates a category at the end of the list, or changes one (F-04). Turning a category
 * into not billable does not touch closed periods, which keep their own snapshot (RN-11, RN-12).
 */
final class SaveCategory
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly CategoryRepository $categories,
        private readonly AuditTrail $audit,
    ) {}

    /**
     * @param  int|null  $categoryId  Null creates a new category.
     */
    public function handle(int $actorId, ?int $categoryId, string $name, string $color, bool $billable): Category
    {
        if (! $this->authorizer->allows($actorId, $categoryId === null ? 'category.create' : 'category.edit')) {
            throw new AccessDenied('You may not change categories.');
        }

        $before = null;

        if ($categoryId !== null) {
            $before = $this->categories->find($categoryId) ?? throw new CategoryRuleViolation('category', 'This category no longer exists.');
        }

        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        if ($name === '') {
            throw new CategoryRuleViolation('name', 'Enter a name.');
        }

        if (mb_strlen($name) > Category::NAME_LIMIT) {
            throw new CategoryRuleViolation('name', 'Too long for this field.');
        }

        if ($this->categories->nameTaken($name, $categoryId)) {
            throw new CategoryRuleViolation('name', 'Another category already uses this name.');
        }

        $chosenColor = Color::tryFrom($color) ?? throw new CategoryRuleViolation('color', 'Choose a colour.');

        // The unique index settles two people saving the same name at once.
        try {
            return DB::transaction(function () use ($actorId, $before, $name, $chosenColor, $billable): Category {
                $category = $before === null
                    ? new Category(null, $name, $chosenColor, $billable, true, $this->categories->nextSortOrder())
                    : $before->with(name: $name, color: $chosenColor, billable: $billable);

                $id = $this->categories->save($category);
                $this->audit->record($actorId, $before === null ? 'category.created' : 'category.updated', 'category', $id, $before?->toArray(), $category->toArray());

                return new Category($id, $category->name, $category->color, $category->billable, $category->active, $category->sortOrder);
            });
        } catch (UniqueConstraintViolationException) {
            throw new CategoryRuleViolation('name', 'Another category already uses this name.');
        }
    }
}
