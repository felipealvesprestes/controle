<?php

namespace App\Infrastructure\Category;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryRepository;
use App\Domain\Shared\Color;
use App\Models\Category as CategoryModel;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function find(int $id): ?Category
    {
        $model = CategoryModel::query()->find($id);

        return $model === null ? null : self::toDomain($model);
    }

    public function nameTaken(string $name, ?int $exceptId = null): bool
    {
        return CategoryModel::query()->where('name', $name)->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))->exists();
    }

    public function activeOrder(): array
    {
        return array_values(CategoryModel::query()->where('active', true)->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get(['id'])->map(fn (CategoryModel $category): int => $category->id)->all());
    }

    public function nextSortOrder(): int
    {
        return (CategoryModel::query()->orderByDesc('sort_order')->first()->sort_order ?? 0) + 1;
    }

    public function save(Category $category): int
    {
        $model = $category->id === null ? new CategoryModel : CategoryModel::query()->findOrFail($category->id);

        $model->forceFill([
            'name' => $category->name,
            'color' => $category->color->value,
            'billable' => $category->billable,
            'active' => $category->active,
            'sort_order' => $category->sortOrder,
        ])->save();

        return $model->id;
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            CategoryModel::query()->whereKey($id)->update(['sort_order' => $index + 1]);
        }
    }

    public function delete(int $id): void
    {
        CategoryModel::query()->whereKey($id)->delete();
    }

    public static function toDomain(CategoryModel $model): Category
    {
        return new Category($model->id, $model->name, Color::from($model->color), $model->billable, $model->active, $model->sort_order);
    }
}
