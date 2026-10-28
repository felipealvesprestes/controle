<?php

use App\Application\Access\SaveRole;
use App\Livewire\Admin\RoleShow;
use App\Livewire\Admin\RolesIndex;
use App\Livewire\Admin\UserShow;
use App\Livewire\Admin\UsersIndex;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Felipe']);
    $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()]);
});

it('renders every admin page for an administrator', function () {
    $roleId = app(SaveRole::class)->handle($this->admin->id, null, 'Staff', 'Team', ['dashboard.page']);
    $this->admin->roles()->attach($roleId);

    $this->get(route('admin.users.index'))->assertOk()->assertSee('Felipe');
    $this->get(route('admin.users.show', $this->admin))->assertOk();
    $this->get(route('admin.roles.index'))->assertOk()->assertSee('Staff');
    $this->get(route('admin.roles.show', $roleId))->assertOk();
});

it('asks for the password again before managing access — SEC-34', function () {
    $this->withSession(['auth.password_confirmed_at' => 0])->get(route('admin.users.index'))->assertRedirect(route('password.confirm'));
});

it('invites from the side panel and shows errors inline', function () {
    Notification::fake();

    Livewire::test(UsersIndex::class)
        ->set('inviting', true)
        ->set('name', 'Ana')
        ->set('email', 'ana@example.test')
        ->call('invite')
        ->assertHasNoErrors()
        ->assertSet('inviting', false)
        ->assertSee('ana@example.test');

    Livewire::test(UsersIndex::class)
        ->set('name', 'Ana')
        ->set('email', 'ana@example.test')
        ->call('invite')
        ->assertHasErrors('email');
});

it('manages a user across the three tabs', function () {
    $user = User::factory()->create(['name' => 'Ana']);
    $roleId = app(SaveRole::class)->handle($this->admin->id, null, 'Staff', '', ['dashboard.page']);

    $component = Livewire::test(UserShow::class, ['user' => $user])
        ->set('roleIds', [$roleId])
        ->call('saveRoles')
        ->call('setAdministrator', true)
        ->call('setAdministrator', false)
        ->set('tab', 'permissions')
        ->call('setOverride', 'dashboard.setup.view', 'allow')
        ->assertSee(__('Allow for this user'))
        ->call('setOverride', 'dashboard.setup.view', '')
        ->call('setActive', false)
        ->call('setActive', true)
        ->set('tab', 'history')
        ->assertSee(__('audit.user.roles_changed'))
        ->assertHasNoErrors();

    expect($user->fresh()?->active)->toBeTrue()
        ->and(AuditLog::query()->where('subject_id', $user->id)->count())->toBe(7);

    $component->set('tab', 'permissions');
    Livewire::test(UserShow::class, ['user' => $this->admin])
        ->set('tab', 'permissions')
        ->assertSee(__('Administrators have every permission; exceptions do not apply to them.'))
        ->call('setActive', false)
        ->assertHasErrors('user');
});

it('shows an empty history and roles hint', function () {
    Livewire::test(UserShow::class, ['user' => User::factory()->create()])
        ->assertSee(__('Create a role'))
        ->set('tab', 'history')
        ->assertSee(__('No changes recorded yet.'));
});

it('creates a role and refuses a duplicate name', function () {
    Livewire::test(RolesIndex::class)
        ->assertSee(__('No roles yet.'))
        ->set('name', 'Accountant')
        ->call('create')
        ->assertRedirect(route('admin.roles.show', Role::query()->sole()->id));

    Livewire::test(RolesIndex::class)
        ->set('name', 'Accountant')
        ->call('create')
        ->assertHasErrors('name');
});

it('edits the permission tree, members and history of a role', function () {
    $roleId = app(SaveRole::class)->handle($this->admin->id, null, 'Staff', '', []);
    User::factory()->create(['name' => 'Ana'])->roles()->attach($roleId);

    Livewire::test(RoleShow::class, ['role' => $roleId])
        ->set('permissions', ['dashboard.billing_rule.view', 'nope.nope', 42])
        ->assertSet('permissions', ['dashboard.billing_rule.view', 'dashboard.menu', 'dashboard.page'])
        ->call('save')
        ->assertHasNoErrors()
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name')
        ->call('delete')
        ->assertHasErrors('role')
        ->set('tab', 'members')
        ->assertSee('Ana')
        ->set('tab', 'history')
        ->assertSee(__('audit.role.updated'));
});

it('deletes an empty role and lists a role without members', function () {
    $roleId = app(SaveRole::class)->handle($this->admin->id, null, 'Empty', '', []);

    Livewire::test(RoleShow::class, ['role' => $roleId])
        ->set('tab', 'members')
        ->assertSee(__('No user has this role.'))
        ->call('delete')
        ->assertRedirect(route('admin.roles.index'));
});

it('returns 404 for a role that disappears before mount', function () {
    $role = Role::query()->forceCreate(['name' => 'Ghost', 'description' => '']);
    $role->delete();

    Livewire::test(RoleShow::class, ['role' => $role])->assertStatus(404);
});
