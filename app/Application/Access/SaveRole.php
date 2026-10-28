<?php

namespace App\Application\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\AdministratorRules;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\PermissionCatalog;

/**
 * Creates or updates a role; dependencies of every chosen permission are included (F-17).
 */
final class SaveRole
{
    public function __construct(
        private readonly AdministratorRules $rules,
        private readonly AccessRepository $accounts,
        private readonly AuditTrail $audit,
        private readonly PermissionCatalog $catalog,
    ) {}

    /**
     * @param  list<string>  $permissions
     */
    public function handle(int $actorId, ?int $roleId, string $name, string $description, array $permissions): int
    {
        $actor = $this->rules->actor($actorId);
        $name = trim($name);
        $description = trim($description);
        $before = $roleId === null ? null : $this->accounts->role($roleId);

        if ($roleId !== null && $before === null) {
            throw new AccessRuleViolation('role', 'This role does not exist.');
        }

        if ($name === '' || mb_strlen($name) > 60) {
            throw new AccessRuleViolation('name', 'Enter a name of up to 60 characters.');
        }

        if (mb_strlen($description) > 255) {
            throw new AccessRuleViolation('description', 'Use up to 255 characters.');
        }

        if ($this->accounts->roleNameTaken($name, $roleId)) {
            throw new AccessRuleViolation('name', 'Another role already uses this name.');
        }

        foreach ($permissions as $permission) {
            if (! $this->catalog->has($permission)) {
                throw new AccessRuleViolation('permissions', 'Unknown permission.');
            }
        }

        $permissions = $this->catalog->withDependencies($permissions);
        sort($permissions);

        $savedId = $this->accounts->saveRole($roleId, $name, $description, $permissions);

        $this->audit->record(
            $actor->id,
            $before === null ? 'role.created' : 'role.updated',
            'role',
            $savedId,
            $before === null ? null : ['name' => $before->name, 'description' => $before->description, 'permissions' => $before->permissions],
            ['name' => $name, 'description' => $description, 'permissions' => $permissions],
        );

        return $savedId;
    }
}
