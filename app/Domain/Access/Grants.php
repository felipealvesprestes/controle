<?php

namespace App\Domain\Access;

/**
 * What has been granted to one user: the input of the resolver (RN-16).
 */
final readonly class Grants
{
    /**
     * @param  array<string, list<string>>  $rolePermissions  permission key => names of the roles granting it
     * @param  array<string, Effect>  $overrides  permission key => per-user exception
     */
    public function __construct(
        public bool $isAdmin,
        public bool $active,
        public array $rolePermissions = [],
        public array $overrides = [],
    ) {}
}
