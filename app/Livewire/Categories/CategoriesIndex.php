<?php

namespace App\Livewire\Categories;

use App\Application\Category\ChangeCategoryStatus;
use App\Application\Category\DeleteCategory;
use App\Application\Category\MoveCategory;
use App\Application\Category\SaveCategory;
use App\Domain\Category\Category;
use App\Domain\Category\CategoryRepository;
use App\Domain\Category\CategoryRuleViolation;
use App\Infrastructure\Category\EloquentCategoryRepository;
use App\Models\Category as CategoryModel;
use Closure;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * T-09: categories in the order of the entry form, with the panel to create and edit (F-04).
 */
class CategoriesIndex extends Component
{
    public bool $editing = false;

    /** Null while creating. */
    public ?int $editingId = null;

    public string $name = '';

    public string $color = 'petrol';

    public bool $billable = true;

    /**
     * @return list<Category>
     */
    #[Computed]
    public function active(): array
    {
        return $this->categories(true);
    }

    /**
     * @return list<Category>
     */
    #[Computed]
    public function inactive(): array
    {
        return $this->categories(false);
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->reset('editingId', 'name', 'color', 'billable');
        $this->editing = true;
    }

    public function edit(int $categoryId): void
    {
        $category = app(CategoryRepository::class)->find($categoryId);

        if ($category === null) {
            return;
        }

        $this->resetErrorBag();
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->color = $category->color->value;
        $this->billable = $category->billable;
        $this->editing = true;
    }

    public function save(SaveCategory $saveCategory): void
    {
        $this->attempt(fn () => $saveCategory->handle((int) Auth::id(), $this->editingId, $this->name, $this->color, $this->billable), __('Category saved.'));
    }

    /**
     * Receives wire:sort drops and the move buttons; the position is zero-based.
     */
    public function move(MoveCategory $moveCategory, int $categoryId, int $position): void
    {
        $this->attempt(fn () => $moveCategory->handle((int) Auth::id(), $categoryId, $position), null);
    }

    public function setActive(ChangeCategoryStatus $changeCategoryStatus, int $categoryId, bool $active): void
    {
        $this->attempt(fn () => $changeCategoryStatus->handle((int) Auth::id(), $categoryId, $active), $active ? __('Category reactivated.') : __('Category deactivated.'));
    }

    public function delete(DeleteCategory $deleteCategory): void
    {
        $categoryId = $this->editingId;

        if ($categoryId !== null) {
            $this->attempt(fn () => $deleteCategory->handle((int) Auth::id(), $categoryId), __('Category deleted.'));
        }
    }

    private function attempt(Closure $action, ?string $success): void
    {
        try {
            $action();
        } catch (CategoryRuleViolation $violation) {
            $this->resetErrorBag();
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        $this->resetErrorBag();
        $this->editing = false;
        unset($this->active, $this->inactive);

        if ($success !== null) {
            Flux::toast(variant: 'success', text: $success);
        }
    }

    /**
     * @return list<Category>
     */
    private function categories(bool $active): array
    {
        return array_values(CategoryModel::query()->where('active', $active)->orderBy('sort_order')->orderBy('id')->get()
            ->map(EloquentCategoryRepository::toDomain(...))->all());
    }

    public function render(): View
    {
        return view('livewire.categories.categories-index')->title(__('Categories'));
    }
}
