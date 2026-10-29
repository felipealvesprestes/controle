<?php

namespace App\Domain\Category;

/**
 * Categories in the order they are offered when logging time (F-04).
 */
interface CategoryRepository
{
    public function find(int $id): ?Category;

    public function nameTaken(string $name, ?int $exceptId = null): bool;

    /**
     * Ids of the active categories, in their order.
     *
     * @return list<int>
     */
    public function activeOrder(): array;

    /**
     * Position after the last category, for a new or reactivated one.
     */
    public function nextSortOrder(): int;

    /**
     * Creates the category when it has no id yet.
     *
     * @return int The category id.
     */
    public function save(Category $category): int;

    /**
     * Numbers the given categories 1, 2, 3… in the given order.
     *
     * @param  list<int>  $ids
     */
    public function reorder(array $ids): void;

    public function delete(int $id): void;
}
