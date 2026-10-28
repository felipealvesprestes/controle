<?php

namespace App\Livewire\Admin;

use App\Application\Access\SaveRole;
use App\Domain\Access\AccessRuleViolation;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * T-13: list of roles and the creation panel.
 */
#[Title('Roles')]
class RolesIndex extends Component
{
    public bool $creating = false;

    public string $name = '';

    public string $description = '';

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->withCount('users')->orderBy('name')->get();
    }

    public function create(SaveRole $saveRole): void
    {
        try {
            $roleId = $saveRole->handle((int) Auth::id(), null, $this->name, $this->description, []);
        } catch (AccessRuleViolation $violation) {
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        $this->redirectRoute('admin.roles.show', ['role' => $roleId], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.roles-index');
    }
}
