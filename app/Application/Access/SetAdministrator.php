<?php

namespace App\Application\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AdministratorRules;
use App\Domain\Access\AuditTrail;

/**
 * Grants or revokes administrator access (RN-17).
 */
final class SetAdministrator
{
    public function __construct(
        private readonly AdministratorRules $rules,
        private readonly AccessRepository $accounts,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $userId, bool $isAdmin): void
    {
        $actor = $this->rules->actor($actorId);
        $target = $this->rules->target($userId);

        if ($target->isAdmin === $isAdmin) {
            return;
        }

        if (! $isAdmin) {
            $this->rules->guardAdministratorLoss($actor, $target);
        }

        $this->accounts->setAdministrator($target->id, $isAdmin);
        $this->audit->record($actor->id, $isAdmin ? 'user.admin_granted' : 'user.admin_revoked', 'user', $target->id, ['is_admin' => ! $isAdmin], ['is_admin' => $isAdmin]);
    }
}
