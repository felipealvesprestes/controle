<?php

namespace App\Domain\Access;

/**
 * Each module declares its own permissions; the catalog only collects them (ADR-0007).
 */
interface PermissionProvider
{
    /**
     * Module name shown as the first level of the permission tree.
     */
    public function module(): string;

    /**
     * @return list<Permission>
     */
    public function permissions(): array;
}
