<?php

namespace App\Livewire\Admin;

use App\Application\Access\AssignRoles;
use App\Application\Access\ChangeUserStatus;
use App\Application\Access\SetAdministrator;
use App\Application\Access\SetPermissionOverride;
use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\Decision;
use App\Domain\Access\Effect;
use App\Domain\Access\PermissionCatalog;
use App\Domain\Access\PermissionResolver;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Closure;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * T-12: one user's details, effective permissions with exceptions, and history.
 */
class UserShow extends Component
{
    #[Locked]
    public int $userId;

    public string $tab = 'details';

    /** @var list<int> */
    public array $roleIds = [];

    public function mount(User $user): void
    {
        $this->userId = $user->id;
        $this->roleIds = array_values(array_map(intval(...), $user->roles->modelKeys()));
    }

    #[Computed]
    public function user(): User
    {
        return User::query()->with('roles')->findOrFail($this->userId);
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    /**
     * @return array<string, Decision>
     */
    #[Computed]
    public function decisions(): array
    {
        return app(PermissionResolver::class)->explain(app(AccessRepository::class)->grantsFor($this->userId));
    }

    /**
     * @return array<string, Effect>
     */
    #[Computed]
    public function overrides(): array
    {
        return app(AccessRepository::class)->grantsFor($this->userId)->overrides;
    }

    /**
     * @return Collection<int, AuditLog>
     */
    #[Computed]
    public function history(): Collection
    {
        return AuditLog::query()->with('actor')->where('subject_type', 'user')->where('subject_id', $this->userId)->latest('id')->limit(30)->get();
    }

    public function catalog(): PermissionCatalog
    {
        return app(PermissionCatalog::class);
    }

    public function saveRoles(AssignRoles $assignRoles): void
    {
        $this->attempt(fn () => $assignRoles->handle((int) Auth::id(), $this->userId, array_map(intval(...), $this->roleIds)), __('Roles saved.'));
    }

    public function setActive(ChangeUserStatus $changeUserStatus, bool $active): void
    {
        $this->attempt(fn () => $changeUserStatus->handle((int) Auth::id(), $this->userId, $active), $active ? __('User reactivated.') : __('User deactivated.'));
    }

    public function setAdministrator(SetAdministrator $setAdministrator, bool $isAdmin): void
    {
        $this->attempt(fn () => $setAdministrator->handle((int) Auth::id(), $this->userId, $isAdmin), __('Administrator access updated.'));
    }

    public function setOverride(SetPermissionOverride $setPermissionOverride, string $permission, string $effect): void
    {
        $this->attempt(fn () => $setPermissionOverride->handle((int) Auth::id(), $this->userId, $permission, Effect::tryFrom($effect)), __('Permission updated.'));
    }

    private function attempt(Closure $action, string $success): void
    {
        try {
            $action();
        } catch (AccessRuleViolation $violation) {
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        unset($this->user, $this->decisions, $this->overrides, $this->history);
        Flux::toast(variant: 'success', text: $success);
    }

    public function render(): View
    {
        return view('livewire.admin.user-show')->title($this->user()->name);
    }
}
