<?php

namespace App\Domain\Access;

/**
 * RN-17: only active administrators manage access, and one active administrator always remains.
 */
final class AdministratorRules
{
    public function __construct(private readonly AccessRepository $accounts) {}

    public function actor(int $actorId): Account
    {
        $actor = $this->accounts->account($actorId);

        if ($actor === null || ! $actor->isAdmin || ! $actor->active) {
            throw new AccessDenied('Only active administrators manage access.');
        }

        return $actor;
    }

    public function target(int $userId): Account
    {
        return $this->accounts->account($userId)
            ?? throw new AccessRuleViolation('user', 'This user does not exist.');
    }

    /**
     * Throws when the change would leave no active administrator or demote the actor.
     */
    public function guardAdministratorLoss(Account $actor, Account $target): void
    {
        if ($target->id === $actor->id) {
            throw new AccessRuleViolation('user', 'You cannot remove your own administrator access.');
        }

        if ($target->isAdmin && $target->active && $this->accounts->activeAdministratorCount() <= 1) {
            throw new AccessRuleViolation('user', 'The system must keep at least one active administrator.');
        }
    }
}
