<?php

use App\Application\Category\ChangeCategoryStatus;
use App\Application\Category\DeleteCategory;
use App\Application\Category\MoveCategory;
use App\Application\Category\SaveCategory;
use App\Domain\Access\AccessDenied;
use App\Domain\Category\Category as CategoryEntity;
use App\Domain\Category\CategoryRepository;
use App\Domain\Category\CategoryRuleViolation;
use App\Infrastructure\Category\EloquentCategoryRepository;
use App\Livewire\Categories\CategoriesIndex;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Livewire\Livewire;

/**
 * @return list<string>
 */
function categoryOrder(): array
{
    return Category::query()->where('active', true)->orderBy('sort_order')->pluck('name')->all();
}

describe('screen', function () {
    it('shows the menu and the categories in order to an administrator — F-04', function () {
        $this->actingAs(User::factory()->admin()->create());
        $this->seed(CategorySeeder::class);

        $this->get(route('dashboard'))->assertSee(route('categories.index'));
        $this->get(route('categories.index'))->assertOk()->assertSeeInOrder(['Desenvolvimento', 'Reunião', 'Bugfix', 'Planejamento', 'Outros']);
    });

    it('hides the menu and answers 403 without permission — RN-18', function () {
        $this->actingAs(User::factory()->create());

        $this->get(route('categories.index'))->assertForbidden();
        $this->get(route('profile.edit'))->assertDontSee(route('categories.index'));
    });

    it('lists the categories read-only without the edit permission', function () {
        Category::factory()->create(['name' => 'Reunião']);
        $this->actingAs(userWithPermissions(['category.view']));

        Livewire::test(CategoriesIndex::class)
            ->assertSee('Reunião')
            ->assertDontSeeHtml('wire:sort="move"')
            ->assertDontSeeHtml('data-test="new-category-button"');
    });

    it('marks the categories that are not billable and lists the inactive ones apart — RN-12, RN-13', function () {
        $this->actingAs(User::factory()->admin()->create());
        Category::factory()->notBillable()->create(['name' => 'Interno']);
        Category::factory()->inactive()->create(['name' => 'Antiga']);

        Livewire::test(CategoriesIndex::class)
            ->assertSeeInOrder(['Interno', __('Not billable'), __('Inactive'), 'Antiga']);
    });

    it('explains an empty list', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CategoriesIndex::class)->assertSee(__('No active categories. Time cannot be logged until there is one.'));
    });
});

describe('seeder', function () {
    it('creates the spreadsheet categories, all billable — F-04', function () {
        $this->seed(CategorySeeder::class);

        expect(categoryOrder())->toBe(['Desenvolvimento', 'Reunião', 'Bugfix', 'Planejamento', 'Outros'])
            ->and(Category::query()->where('billable', false)->count())->toBe(0);
    });

    it('does nothing once there are categories, so a deleted one does not come back', function () {
        Category::factory()->create(['name' => 'Dev']);

        $this->seed(CategorySeeder::class);

        expect(Category::query()->pluck('name')->all())->toBe(['Dev']);
    });
});

describe('creating and editing', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('creates a category at the end of the list — F-04', function () {
        Category::factory()->create(['name' => 'Dev']);

        Livewire::test(CategoriesIndex::class)
            ->call('create')
            ->assertSet('editing', true)
            ->set('name', '  Interno ')
            ->set('color', 'amber')
            ->set('billable', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false);

        $created = Category::query()->where('name', 'Interno')->sole();
        expect($created->billable)->toBeFalse()
            ->and($created->color)->toBe('amber')
            ->and(categoryOrder())->toBe(['Dev', 'Interno'])
            ->and(AuditLog::query()->where('event', 'category.created')->sole()->subject_id)->toBe($created->id);
    });

    it('turns a category into not billable with an audit entry — RN-12', function () {
        $category = Category::factory()->create(['name' => 'Reunião']);

        Livewire::test(CategoriesIndex::class)
            ->call('edit', $category->id)
            ->assertSet('name', 'Reunião')
            ->assertSet('billable', true)
            ->set('billable', false)
            ->call('save')
            ->assertHasNoErrors();

        expect($category->refresh()->billable)->toBeFalse();

        $log = AuditLog::query()->where('event', 'category.updated')->sole();
        expect($log->before['billable'])->toBeTrue()->and($log->after['billable'])->toBeFalse();
    });

    it('ignores editing a category that no longer exists', function () {
        Livewire::test(CategoriesIndex::class)->call('edit', 999)->assertSet('editing', false);
    });

    it('refuses an invalid name or colour', function (array $fields, string $error) {
        Category::factory()->create(['name' => 'Reunião']);

        Livewire::test(CategoriesIndex::class)
            ->call('create')
            ->set(['name' => 'Nova', ...$fields])
            ->call('save')
            ->assertHasErrors($error)
            ->assertSet('editing', true);

        expect(Category::query()->count())->toBe(1);
    })->with([
        'empty name' => [['name' => ' '], 'name'],
        'long name' => [['name' => str_repeat('a', 41)], 'name'],
        'name in use' => [['name' => 'Reunião'], 'name'],
        'unknown colour' => [['color' => 'gold'], 'color'],
    ]);

    it('refuses saving a category deleted in the meantime', function () {
        $category = Category::factory()->create();
        $component = Livewire::test(CategoriesIndex::class)->call('edit', $category->id);
        $category->delete();

        $component->call('save')->assertHasErrors('category');
    });

    it('refuses a name saved by someone else at the same moment instead of failing with a 500', function () {
        Category::factory()->create(['name' => 'Reunião']);
        $real = app(EloquentCategoryRepository::class);
        app()->instance(CategoryRepository::class, new class($real) implements CategoryRepository
        {
            public function __construct(private readonly EloquentCategoryRepository $real) {}

            public function nameTaken(string $name, ?int $exceptId = null): bool
            {
                return false;
            }

            public function find(int $id): ?CategoryEntity
            {
                return $this->real->find($id);
            }

            public function activeOrder(): array
            {
                return $this->real->activeOrder();
            }

            public function nextSortOrder(): int
            {
                return $this->real->nextSortOrder();
            }

            public function save(CategoryEntity $category): int
            {
                return $this->real->save($category);
            }

            public function reorder(array $ids): void
            {
                $this->real->reorder($ids);
            }

            public function delete(int $id): void
            {
                $this->real->delete($id);
            }
        });

        Livewire::test(CategoriesIndex::class)->call('create')->set('name', 'Reunião')->call('save')->assertHasErrors('name');
    });
});

describe('ordering', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->admin()->create());
        $this->seed(CategorySeeder::class);
    });

    it('moves a category to the dropped position — F-04', function () {
        $bugfix = Category::query()->where('name', 'Bugfix')->sole();

        Livewire::test(CategoriesIndex::class)->call('move', $bugfix->id, 0);

        expect(categoryOrder())->toBe(['Bugfix', 'Desenvolvimento', 'Reunião', 'Planejamento', 'Outros']);

        $log = AuditLog::query()->where('event', 'category.moved')->sole();
        expect($log->before)->toBe(['position' => 3])->and($log->after)->toBe(['position' => 1]);
    });

    it('moves down with the button and stops at the ends', function () {
        $outros = Category::query()->where('name', 'Outros')->sole();
        $dev = Category::query()->where('name', 'Desenvolvimento')->sole();

        Livewire::test(CategoriesIndex::class)
            ->call('move', $dev->id, 1)
            ->call('move', $outros->id, 99);

        expect(categoryOrder())->toBe(['Reunião', 'Desenvolvimento', 'Bugfix', 'Planejamento', 'Outros'])
            ->and(AuditLog::query()->where('event', 'category.moved')->count())->toBe(1);
    });

    it('does not move an inactive category', function () {
        $outros = Category::query()->where('name', 'Outros')->sole();
        $outros->forceFill(['active' => false])->save();

        Livewire::test(CategoriesIndex::class)->call('move', $outros->id, 0)->assertHasErrors('category');
    });
});

describe('status and deletion', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('deactivates a category and puts it back at the end when reactivated — RN-13', function () {
        $first = Category::factory()->create(['name' => 'Dev']);
        Category::factory()->create(['name' => 'Call']);

        Livewire::test(CategoriesIndex::class)
            ->call('edit', $first->id)
            ->call('setActive', $first->id, false)
            ->assertSet('editing', false)
            ->assertSeeInOrder(['Call', __('Inactive'), 'Dev'])
            ->call('setActive', $first->id, true)
            ->call('setActive', $first->id, true);

        expect(categoryOrder())->toBe(['Call', 'Dev'])
            ->and(AuditLog::query()->where('subject_type', 'category')->pluck('event')->all())->toBe(['category.deactivated', 'category.reactivated']);
    });

    it('deletes a category created by mistake — RN-13', function () {
        $category = Category::factory()->create(['name' => 'Errada']);

        Livewire::test(CategoriesIndex::class)
            ->call('delete')
            ->call('edit', $category->id)
            ->call('delete')
            ->assertSet('editing', false);

        expect(Category::query()->count())->toBe(0)
            ->and(AuditLog::query()->where('event', 'category.deleted')->sole()->before['name'])->toBe('Errada');
    });

    it('refuses status changes and deletion of a missing category', function (Closure $action) {
        expect(fn () => $action(User::factory()->admin()->create()->id))->toThrow(CategoryRuleViolation::class, 'This category no longer exists.');
    })->with([
        'status' => [fn (int $actorId) => app(ChangeCategoryStatus::class)->handle($actorId, 999, false)],
        'delete' => [fn (int $actorId) => app(DeleteCategory::class)->handle($actorId, 999)],
    ]);
});

describe('permissions', function () {
    it('denies each use case without its permission — SEC-02', function (Closure $action) {
        $category = Category::factory()->create();
        $user = userWithPermissions(['category.view']);

        expect(fn () => $action($user->id, $category->id))->toThrow(AccessDenied::class);
    })->with([
        'create' => [fn (int $actorId) => app(SaveCategory::class)->handle($actorId, null, 'Nova', 'petrol', true)],
        'edit' => [fn (int $actorId, int $categoryId) => app(SaveCategory::class)->handle($actorId, $categoryId, 'Nova', 'petrol', false)],
        'move' => [fn (int $actorId, int $categoryId) => app(MoveCategory::class)->handle($actorId, $categoryId, 0)],
        'status' => [fn (int $actorId, int $categoryId) => app(ChangeCategoryStatus::class)->handle($actorId, $categoryId, false)],
        'delete' => [fn (int $actorId, int $categoryId) => app(DeleteCategory::class)->handle($actorId, $categoryId)],
    ]);

    it('lets a creator create but not edit', function () {
        $category = Category::factory()->create();
        $user = userWithPermissions(['category.create']);

        app(SaveCategory::class)->handle($user->id, null, 'Nova', 'petrol', true);

        expect(fn () => app(SaveCategory::class)->handle($user->id, $category->id, 'Outra', 'petrol', true))->toThrow(AccessDenied::class);
    });
});
