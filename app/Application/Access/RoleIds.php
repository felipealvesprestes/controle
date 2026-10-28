<?php

namespace App\Application\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AccessRuleViolation;

/**
 * Validates role ids coming from a form.
 */
final class RoleIds
{
    public function __construct(private readonly AccessRepository $accounts) {}

    /**
     * @param  list<int>  $roleIds
     * @return list<int>
     */
    public function existing(array $roleIds): array
    {
        $roleIds = array_values(array_unique($roleIds));
        sort($roleIds);

        foreach ($roleIds as $roleId) {
            if ($this->accounts->role($roleId) === null) {
                throw new AccessRuleViolation('roles', 'One of the selected roles no longer exists.');
            }
        }

        return $roleIds;
    }
}
