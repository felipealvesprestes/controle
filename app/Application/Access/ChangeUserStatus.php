<?php

namespace App\Application\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\AdministratorRules;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\SessionTerminator;

/**
 * Deactivates or reactivates a user; deactivation ends their sessions (RN-17, RN-19).
 */
final class ChangeUserStatus
{
    public function __construct(
        private readonly AdministratorRules $rules,
        private readonly AccessRepository $accounts,
        private readonly AuditTrail $audit,
        private readonly SessionTerminator $sessions,
    ) {}

    public function handle(int $actorId, int $userId, bool $active): void
    {
        $actor = $this->rules->actor($actorId);
        $target = $this->rules->target($userId);

        if ($target->active === $active) {
            return;
        }

        if (! $active) {
            if ($target->id === $actor->id) {
                throw new AccessRuleViolation('user', 'You cannot deactivate your own account.');
            }

            $this->rules->guardAdministratorLoss($actor, $target);
        }

        $this->accounts->setActive($target->id, $active);

        if (! $active) {
            $this->sessions->endAll($target->id);
        }

        $this->audit->record($actor->id, $active ? 'user.reactivated' : 'user.deactivated', 'user', $target->id, ['active' => ! $active], ['active' => $active]);
    }
}
