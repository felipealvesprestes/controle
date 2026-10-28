<?php

namespace App\Domain\Access;

use InvalidArgumentException;

/**
 * Every permission the system knows, grouped by module (ADR-0007).
 */
final class PermissionCatalog
{
    /** @var array<string, Permission> */
    private array $permissions = [];

    /** @var array<string, list<Permission>> */
    private array $modules = [];

    /**
     * @param  iterable<PermissionProvider>  $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            foreach ($provider->permissions() as $permission) {
                if (isset($this->permissions[$permission->key])) {
                    throw new InvalidArgumentException("Duplicate permission [{$permission->key}].");
                }

                $this->permissions[$permission->key] = $permission;
                $this->modules[$provider->module()][] = $permission;
            }
        }

        foreach ($this->permissions as $permission) {
            foreach ($permission->dependsOn as $dependency) {
                if (! isset($this->permissions[$dependency])) {
                    throw new InvalidArgumentException("Permission [{$permission->key}] depends on unknown [{$dependency}].");
                }
            }
        }
    }

    public function has(string $key): bool
    {
        return isset($this->permissions[$key]);
    }

    public function get(string $key): Permission
    {
        return $this->permissions[$key] ?? throw new InvalidArgumentException("Unknown permission [{$key}].");
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->permissions);
    }

    /**
     * @return array<string, list<Permission>>
     */
    public function byModule(): array
    {
        return $this->modules;
    }

    /**
     * The given keys plus everything they depend on, transitively.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public function withDependencies(array $keys): array
    {
        $result = [];
        $pending = $keys;

        while ($pending !== []) {
            $key = array_pop($pending);

            if (isset($result[$key])) {
                continue;
            }

            $result[$key] = true;
            array_push($pending, ...$this->get($key)->dependsOn);
        }

        return array_keys($result);
    }
}
