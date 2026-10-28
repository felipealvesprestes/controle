<?php

namespace App\Infrastructure\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\Account;
use App\Domain\Access\Effect;
use App\Domain\Access\Grants;
use App\Domain\Access\Role as RoleData;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentAccessRepository implements AccessRepository
{
    public function account(int $userId): ?Account
    {
        $user = User::query()->find($userId);

        return $user === null ? null : new Account($user->id, $user->name, $user->email, $user->is_admin, $user->active);
    }

    public function grantsFor(int $userId): Grants
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            return new Grants(isAdmin: false, active: false);
        }

        $rolePermissions = [];

        foreach (DB::table('role_permission')
            ->join('role_user', 'role_user.role_id', '=', 'role_permission.role_id')
            ->join('roles', 'roles.id', '=', 'role_permission.role_id')
            ->where('role_user.user_id', $userId)
            ->orderBy('roles.name')
            ->get(['role_permission.permission_key', 'roles.name']) as $row) {
            $rolePermissions[self::text($row->permission_key)][] = self::text($row->name);
        }

        $overrides = [];

        foreach (DB::table('user_permission_overrides')->where('user_id', $userId)->get(['permission_key', 'effect']) as $row) {
            $overrides[self::text($row->permission_key)] = Effect::from(self::text($row->effect));
        }

        return new Grants($user->is_admin, $user->active, $rolePermissions, $overrides);
    }

    public function activeAdministratorCount(): int
    {
        return User::query()->where('is_admin', true)->where('active', true)->count();
    }

    public function emailTaken(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    public function createInvitedAccount(string $name, string $email): int
    {
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            // Nobody knows this password; the invitation sets the real one.
            'password' => Str::password(64),
            'invited_at' => now(),
        ])->save();

        return $user->id;
    }

    public function setActive(int $userId, bool $active): void
    {
        User::query()->whereKey($userId)->update(['active' => $active]);
    }

    public function setAdministrator(int $userId, bool $isAdmin): void
    {
        User::query()->whereKey($userId)->update(['is_admin' => $isAdmin]);
    }

    public function role(int $roleId): ?RoleData
    {
        $role = Role::query()->find($roleId);

        if ($role === null) {
            return null;
        }

        $permissions = array_map(self::text(...), DB::table('role_permission')->where('role_id', $roleId)->orderBy('permission_key')->pluck('permission_key')->all());

        return new RoleData($role->id, $role->name, $role->description, array_values($permissions));
    }

    public function roleNameTaken(string $name, ?int $ignoreRoleId): bool
    {
        return Role::query()
            ->where('name', $name)
            ->when($ignoreRoleId !== null, fn ($query) => $query->whereKeyNot($ignoreRoleId))
            ->exists();
    }

    public function saveRole(?int $roleId, string $name, string $description, array $permissions): int
    {
        return DB::transaction(function () use ($roleId, $name, $description, $permissions): int {
            $role = $roleId === null ? new Role : Role::query()->findOrFail($roleId);
            $role->forceFill(['name' => $name, 'description' => $description])->save();

            DB::table('role_permission')->where('role_id', $role->id)->delete();
            DB::table('role_permission')->insert(array_map(
                fn (string $key): array => ['role_id' => $role->id, 'permission_key' => $key],
                $permissions,
            ));

            return $role->id;
        });
    }

    public function deleteRole(int $roleId): void
    {
        Role::query()->whereKey($roleId)->delete();
    }

    public function roleMemberCount(int $roleId): int
    {
        return DB::table('role_user')->where('role_id', $roleId)->count();
    }

    public function roleIdsOf(int $userId): array
    {
        return array_values(array_map(fn (mixed $id): int => (int) self::text($id), DB::table('role_user')->where('user_id', $userId)->orderBy('role_id')->pluck('role_id')->all()));
    }

    public function setRoles(int $userId, array $roleIds): void
    {
        DB::transaction(function () use ($userId, $roleIds): void {
            DB::table('role_user')->where('user_id', $userId)->delete();
            DB::table('role_user')->insert(array_map(
                fn (int $roleId): array => ['role_id' => $roleId, 'user_id' => $userId],
                $roleIds,
            ));
        });
    }

    public function setOverride(int $userId, string $permission, ?Effect $effect): void
    {
        $where = ['user_id' => $userId, 'permission_key' => $permission];

        if ($effect === null) {
            DB::table('user_permission_overrides')->where($where)->delete();

            return;
        }

        DB::table('user_permission_overrides')->updateOrInsert($where, ['effect' => $effect->value]);
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
