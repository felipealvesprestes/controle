<?php

namespace App\Application\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\AdministratorRules;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Effect;
use App\Domain\Access\PermissionCatalog;

/**
 * Allows or denies one permission for one user, or goes back to the roles (RN-16).
 */
final class SetPermissionOverride
{
    public function __construct(
        private readonly AdministratorRules $rules,
        private readonly AccessRepository $accounts,
        private readonly AuditTrail $audit,
        private readonly PermissionCatalog $catalog,
    ) {}

    public function handle(int $actorId, int $userId, string $permission, ?Effect $effect): void
    {
        $actor = $this->rules->actor($actorId);
        $target = $this->rules->target($userId);

        if (! $this->catalog->has($permission)) {
            throw new AccessRuleViolation('permission', 'Unknown permission.');
        }

        $before = $this->accounts->grantsFor($target->id)->overrides[$permission] ?? null;

        if ($before === $effect) {
            return;
        }

        $this->accounts->setOverride($target->id, $permission, $effect);
        $this->audit->record($actor->id, 'user.permission_override', 'user', $target->id, ['permission' => $permission, 'effect' => $before?->value], ['permission' => $permission, 'effect' => $effect?->value]);
    }
}
