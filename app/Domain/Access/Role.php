<?php

namespace App\Domain\Access;

/**
 * A named set of permissions (F-17).
 */
final readonly class Role
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public array $permissions,
    ) {}
}
