<?php

namespace App\Application\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\AdministratorRules;
use App\Domain\Access\AuditTrail;

/**
 * Deletes a role that no user holds anymore.
 */
final class DeleteRole
{
    public function __construct(
        private readonly AdministratorRules $rules,
        private readonly AccessRepository $accounts,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $roleId): void
    {
        $actor = $this->rules->actor($actorId);
        $role = $this->accounts->role($roleId) ?? throw new AccessRuleViolation('role', 'This role does not exist.');

        if ($this->accounts->roleMemberCount($role->id) > 0) {
            throw new AccessRuleViolation('role', 'Move the users to another role before deleting it.');
        }

        $this->accounts->deleteRole($role->id);
        $this->audit->record($actor->id, 'role.deleted', 'role', $role->id, ['name' => $role->name, 'permissions' => $role->permissions]);
    }
}
