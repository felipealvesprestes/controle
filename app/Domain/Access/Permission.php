<?php

namespace App\Domain\Access;

use InvalidArgumentException;

/**
 * One entry of the permission catalog, declared in code (ADR-0007).
 */
final readonly class Permission
{
    /**
     * @param  list<string>  $dependsOn
     */
    public function __construct(
        public string $key,
        public PermissionType $type,
        public string $label,
        public string $screen,
        public array $dependsOn = [],
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $key) !== 1) {
            throw new InvalidArgumentException("Invalid permission key [{$key}].");
        }

        if (in_array($key, $dependsOn, true)) {
            throw new InvalidArgumentException("Permission [{$key}] cannot depend on itself.");
        }
    }
}
