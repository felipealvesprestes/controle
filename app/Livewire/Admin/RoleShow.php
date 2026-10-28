<?php

namespace App\Livewire\Admin;

use App\Application\Access\DeleteRole;
use App\Application\Access\SaveRole;
use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\PermissionCatalog;
use App\Models\AuditLog;
use App\Models\Role;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * T-13: a role's permission tree, members and history.
 */
class RoleShow extends Component
{
    #[Locked]
    public int $roleId;

    public string $tab = 'permissions';

    public string $name = '';

    public string $description = '';

    /** @var list<string> */
    public array $permissions = [];

    public function mount(Role $role, AccessRepository $accounts): void
    {
        $data = $accounts->role($role->id);
        abort_if($data === null, 404);

        $this->roleId = $data->id;
        $this->name = $data->name;
        $this->description = $data->description;
        $this->permissions = $data->permissions;
    }

    #[Computed]
    public function role(): Role
    {
        return Role::query()->with('users')->findOrFail($this->roleId);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    #[Computed]
    public function history(): Collection
    {
        return AuditLog::query()->with('actor')->where('subject_type', 'role')->where('subject_id', $this->roleId)->latest('id')->limit(30)->get();
    }

    public function catalog(): PermissionCatalog
    {
        return app(PermissionCatalog::class);
    }

    /**
     * Ticking a permission also ticks what it depends on, as the server will (F-17).
     */
    public function updatedPermissions(): void
    {
        $keys = array_map(strval(...), array_filter($this->permissions, is_string(...)));
        $this->permissions = array_values(array_filter($keys, fn (string $key): bool => $this->catalog()->has($key)));
        $this->permissions = $this->catalog()->withDependencies($this->permissions);
        sort($this->permissions);
    }

    public function save(SaveRole $saveRole): void
    {
        try {
            $saveRole->handle((int) Auth::id(), $this->roleId, $this->name, $this->description, $this->permissions);
        } catch (AccessRuleViolation $violation) {
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        unset($this->role, $this->history);
        Flux::toast(variant: 'success', text: __('Role saved.'));
    }

    public function delete(DeleteRole $deleteRole): void
    {
        try {
            $deleteRole->handle((int) Auth::id(), $this->roleId);
        } catch (AccessRuleViolation $violation) {
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        $this->redirectRoute('admin.roles.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.role-show')->title($this->role()->name);
    }
}
