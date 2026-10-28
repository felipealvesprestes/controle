<?php

namespace App\Application\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;
use App\Domain\Access\AdministratorRules;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\InvitationSender;

/**
 * Creates an account and sends its single-use invitation (F-01, F-17).
 */
final class InviteUser
{
    public function __construct(
        private readonly AdministratorRules $rules,
        private readonly AccessRepository $accounts,
        private readonly AuditTrail $audit,
        private readonly InvitationSender $invitations,
        private readonly RoleIds $roleIds,
    ) {}

    /**
     * @param  list<int>  $roleIds
     */
    public function handle(int $actorId, string $name, string $email, array $roleIds): int
    {
        $actor = $this->rules->actor($actorId);
        $name = trim($name);
        $email = mb_strtolower(trim($email));

        if ($name === '' || mb_strlen($name) > 255) {
            throw new AccessRuleViolation('name', 'Enter a name of up to 255 characters.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 255) {
            throw new AccessRuleViolation('email', 'Enter a valid email address.');
        }

        if ($this->accounts->emailTaken($email)) {
            throw new AccessRuleViolation('email', 'This email is already in use.');
        }

        $roleIds = $this->roleIds->existing($roleIds);
        $userId = $this->accounts->createInvitedAccount($name, $email);
        $this->accounts->setRoles($userId, $roleIds);

        $this->audit->record($actor->id, 'user.invited', 'user', $userId, null, ['name' => $name, 'email' => $email, 'roles' => $roleIds]);
        $this->invitations->send($userId);

        return $userId;
    }
}
