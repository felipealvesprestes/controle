<?php

namespace App\Application\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AdministratorRules;
use App\Domain\Access\AuditTrail;

/**
 * Replaces the roles of a user.
 */
final class AssignRoles
{
    public function __construct(
        private readonly AdministratorRules $rules,
        private readonly AccessRepository $accounts,
        private readonly AuditTrail $audit,
        private readonly RoleIds $roleIds,
    ) {}

    /**
     * @param  list<int>  $roleIds
     */
    public function handle(int $actorId, int $userId, array $roleIds): void
    {
        $actor = $this->rules->actor($actorId);
        $target = $this->rules->target($userId);
        $roleIds = $this->roleIds->existing($roleIds);
        $before = $this->accounts->roleIdsOf($target->id);

        if ($before === $roleIds) {
            return;
        }

        $this->accounts->setRoles($target->id, $roleIds);
        $this->audit->record($actor->id, 'user.roles_changed', 'user', $target->id, ['roles' => $before], ['roles' => $roleIds]);
    }
}
