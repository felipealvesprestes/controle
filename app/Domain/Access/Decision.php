<?php

namespace App\Domain\Access;

/**
 * The effective state of one permission for one user.
 */
final readonly class Decision
{
    /**
     * @param  list<string>  $roles  roles that grant it, when the reason is Role
     */
    public function __construct(
        public bool $allowed,
        public Reason $reason,
        public array $roles = [],
    ) {}
}
