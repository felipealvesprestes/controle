<?php

namespace App\Livewire\Admin;

use App\Application\Access\InviteUser;
use App\Domain\Access\AccessRuleViolation;
use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * T-12: list of users and the invitation panel.
 */
#[Title('Users')]
class UsersIndex extends Component
{
    public bool $inviting = false;

    public string $name = '';

    public string $email = '';

    /** @var list<int> */
    public array $roleIds = [];

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->with('roles')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    public function invite(InviteUser $inviteUser): void
    {
        try {
            $inviteUser->handle((int) Auth::id(), $this->name, $this->email, array_map(intval(...), $this->roleIds));
        } catch (AccessRuleViolation $violation) {
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        $this->reset('inviting', 'name', 'email', 'roleIds');
        unset($this->users);
        Flux::toast(variant: 'success', text: __('Invitation sent.'));
    }

    public function render(): View
    {
        return view('livewire.admin.users-index');
    }
}
