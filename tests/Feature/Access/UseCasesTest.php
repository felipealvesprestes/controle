<?php

use App\Application\Access\AssignRoles;
use App\Application\Access\ChangeUserStatus;
use App\Application\Access\DeleteRole;
use App\Application\Access\InviteUser;
use App\Application\Access\SaveRole;
use App\Application\Access\SetAdministrator;
use App\Application\Access\SetPermissionOverride;
use App\Domain\Access\AccessDenied;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\Authorizer;
use App\Domain\Access\Effect;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function makeRole(string $name = 'Staff', array $permissions = []): Role
{
    $id = app(SaveRole::class)->handle(User::factory()->admin()->create()->id, null, $name, '', $permissions);

    return Role::query()->findOrFail($id);
}

describe('invite', function () {
    it('creates the account, its roles, the audit entry and the invitation — F-17', function () {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $role = makeRole();

        $id = app(InviteUser::class)->handle($admin->id, ' Ana ', ' Ana@Example.TEST ', [$role->id, $role->id]);

        $user = User::query()->findOrFail($id);
        expect($user->email)->toBe('ana@example.test')
            ->and($user->is_admin)->toBeFalse()
            ->and($user->invited_at)->not->toBeNull()
            ->and($user->roles->pluck('id')->all())->toBe([$role->id])
            ->and(AuditLog::query()->where('event', 'user.invited')->where('subject_id', $id)->exists())->toBeTrue();
        Notification::assertSentTo($user, InvitationNotification::class);
    });

    it('refuses invalid input', function (string $name, string $email, string $field) {
        User::factory()->create(['email' => 'taken@example.test']);

        try {
            app(InviteUser::class)->handle(User::factory()->admin()->create()->id, $name, $email, []);
        } catch (AccessRuleViolation $violation) {
            expect($violation->field)->toBe($field);

            return;
        }

        $this->fail('Expected a violation.');
    })->with([
        'empty name' => ['', 'a@example.test', 'name'],
        'bad email' => ['Ana', 'not-an-email', 'email'],
        'taken email' => ['Ana', 'taken@example.test', 'email'],
    ]);

    it('refuses an unknown role', function () {
        app(InviteUser::class)->handle(User::factory()->admin()->create()->id, 'Ana', 'ana@example.test', [999]);
    })->throws(AccessRuleViolation::class, 'One of the selected roles no longer exists.');

    it('refuses a regular user as the actor — RN-18', function () {
        app(InviteUser::class)->handle(User::factory()->create()->id, 'Ana', 'ana@example.test', []);
    })->throws(AccessDenied::class);
});

describe('status — RN-17, RN-19', function () {
    it('deactivates a user, ends their sessions and can reactivate', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $token = $user->remember_token;

        app(ChangeUserStatus::class)->handle($admin->id, $user->id, false);
        app(ChangeUserStatus::class)->handle($admin->id, $user->id, false);

        expect($user->fresh()?->active)->toBeFalse()
            ->and(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse()
            ->and($user->fresh()?->remember_token)->not->toBe($token)
            ->and(AuditLog::query()->where('event', 'user.deactivated')->count())->toBe(1);

        app(ChangeUserStatus::class)->handle($admin->id, $user->id, true);

        expect($user->fresh()?->active)->toBeTrue();
    });

    it('refuses to deactivate yourself', function () {
        $admin = User::factory()->admin()->create();

        app(ChangeUserStatus::class)->handle($admin->id, $admin->id, false);
    })->throws(AccessRuleViolation::class, 'You cannot deactivate your own account.');

    it('deactivates another administrator while one remains', function () {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        app(ChangeUserStatus::class)->handle($admin->id, $other->id, false);

        expect($other->fresh()?->active)->toBeFalse();
    });
});

describe('administrator access — RN-17', function () {
    it('grants and revokes administrator access, ignoring no-ops', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        app(SetAdministrator::class)->handle($admin->id, $user->id, true);
        app(SetAdministrator::class)->handle($admin->id, $user->id, true);
        expect($user->fresh()?->is_admin)->toBeTrue();

        app(SetAdministrator::class)->handle($admin->id, $user->id, false);
        expect($user->fresh()?->is_admin)->toBeFalse()
            ->and(AuditLog::query()->whereIn('event', ['user.admin_granted', 'user.admin_revoked'])->count())->toBe(2);
    });

    it('refuses to revoke your own access', function () {
        $admin = User::factory()->admin()->create();

        app(SetAdministrator::class)->handle($admin->id, $admin->id, false);
    })->throws(AccessRuleViolation::class);
});

describe('roles and exceptions', function () {
    it('assigns roles and records only real changes', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $role = makeRole();

        app(AssignRoles::class)->handle($admin->id, $user->id, [$role->id]);
        app(AssignRoles::class)->handle($admin->id, $user->id, [$role->id]);

        expect($user->roles()->pluck('roles.id')->all())->toBe([$role->id])
            ->and(AuditLog::query()->where('event', 'user.roles_changed')->count())->toBe(1);
    });

    it('sets, keeps and clears an exception', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $setOverride = app(SetPermissionOverride::class);

        $setOverride->handle($admin->id, $user->id, 'dashboard.menu', Effect::Allow);
        $setOverride->handle($admin->id, $user->id, 'dashboard.menu', Effect::Allow);
        expect(app(Authorizer::class)->allows($user->id, 'dashboard.menu'))->toBeTrue();

        $setOverride->handle($admin->id, $user->id, 'dashboard.menu', null);
        expect(DB::table('user_permission_overrides')->count())->toBe(0)
            ->and(AuditLog::query()->where('event', 'user.permission_override')->count())->toBe(2);
    });

    it('refuses an unknown permission', function () {
        app(SetPermissionOverride::class)->handle(User::factory()->admin()->create()->id, User::factory()->create()->id, 'nope.nope', Effect::Allow);
    })->throws(AccessRuleViolation::class, 'Unknown permission.');

    it('saves a role with its dependencies and records before and after', function () {
        $admin = User::factory()->admin()->create();
        $saveRole = app(SaveRole::class);

        $id = $saveRole->handle($admin->id, null, ' Accountant ', ' Reads billing ', ['dashboard.billing_rule.view']);
        $saveRole->handle($admin->id, $id, 'Accountant', 'Reads billing', ['dashboard.menu']);

        $permissions = DB::table('role_permission')->where('role_id', $id)->pluck('permission_key')->all();
        $updated = AuditLog::query()->where('event', 'role.updated')->sole();

        expect($permissions)->toBe(['dashboard.menu'])
            ->and($updated->before['permissions'] ?? null)->toBe(['dashboard.billing_rule.view', 'dashboard.menu', 'dashboard.page']);
    });

    it('refuses invalid roles', function (?int $roleId, string $name, string $description, array $permissions, string $field) {
        makeRole('Taken');

        try {
            app(SaveRole::class)->handle(User::factory()->admin()->create()->id, $roleId, $name, $description, $permissions);
        } catch (AccessRuleViolation $violation) {
            expect($violation->field)->toBe($field);

            return;
        }

        $this->fail('Expected a violation.');
    })->with([
        'missing role' => [999, 'X', '', [], 'role'],
        'empty name' => [null, ' ', '', [], 'name'],
        'long description' => [null, 'X', str_repeat('a', 256), [], 'description'],
        'taken name' => [null, 'Taken', '', [], 'name'],
        'unknown permission' => [null, 'X', '', ['nope.nope'], 'permissions'],
    ]);

    it('deletes an unused role and refuses one in use or missing', function () {
        $admin = User::factory()->admin()->create();
        $role = makeRole();
        $used = makeRole('Used');
        User::factory()->create()->roles()->attach($used);

        app(DeleteRole::class)->handle($admin->id, $role->id);
        expect(Role::query()->find($role->id))->toBeNull();

        expect(fn () => app(DeleteRole::class)->handle($admin->id, $used->id))->toThrow(AccessRuleViolation::class)
            ->and(fn () => app(DeleteRole::class)->handle($admin->id, 999))->toThrow(AccessRuleViolation::class);
    });
});

it('denies every permission to a user that does not exist', function () {
    expect(app(Authorizer::class)->allows(999, 'dashboard.menu'))->toBeFalse();
});
