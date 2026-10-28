<?php

namespace App\Domain\Access;

/**
 * Persistence port for accounts, roles and per-user exceptions.
 */
interface AccessRepository
{
    public function account(int $userId): ?Account;

    public function grantsFor(int $userId): Grants;

    public function activeAdministratorCount(): int;

    public function emailTaken(string $email): bool;

    public function createInvitedAccount(string $name, string $email): int;

    public function setActive(int $userId, bool $active): void;

    public function setAdministrator(int $userId, bool $isAdmin): void;

    public function role(int $roleId): ?Role;

    public function roleNameTaken(string $name, ?int $ignoreRoleId): bool;

    /**
     * @param  list<string>  $permissions
     */
    public function saveRole(?int $roleId, string $name, string $description, array $permissions): int;

    public function deleteRole(int $roleId): void;

    public function roleMemberCount(int $roleId): int;

    /**
     * @return list<int>
     */
    public function roleIdsOf(int $userId): array;

    /**
     * @param  list<int>  $roleIds
     */
    public function setRoles(int $userId, array $roleIds): void;

    public function setOverride(int $userId, string $permission, ?Effect $effect): void;
}
