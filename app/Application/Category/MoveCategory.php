<?php

namespace App\Application\Category;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Category\CategoryRepository;
use App\Domain\Category\CategoryRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Moves an active category to another place in the list, which is the order of the entry form (F-04).
 */
final class MoveCategory
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly CategoryRepository $categories,
        private readonly AuditTrail $audit,
    ) {}

    /**
     * @param  int  $position  Zero-based place among the active categories; out-of-range values go to the nearest end.
     */
    public function handle(int $actorId, int $categoryId, int $position): void
    {
        if (! $this->authorizer->allows($actorId, 'category.edit')) {
            throw new AccessDenied('You may not change categories.');
        }

        DB::transaction(function () use ($actorId, $categoryId, $position): void {
            $order = $this->categories->activeOrder();
            $from = array_search($categoryId, $order, true);

            if ($from === false) {
                throw new CategoryRuleViolation('category', 'Only active categories can be moved.');
            }

            $to = max(0, min($position, count($order) - 1));

            if ($from === $to) {
                return;
            }

            array_splice($order, $from, 1);
            array_splice($order, $to, 0, [$categoryId]);

            $this->categories->reorder($order);
            $this->audit->record($actorId, 'category.moved', 'category', $categoryId, ['position' => $from + 1], ['position' => $to + 1]);
        });
    }
}
