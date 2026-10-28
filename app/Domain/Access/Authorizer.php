<?php

namespace App\Domain\Access;

/**
 * Answers whether a user holds a catalog permission (ADR-0007, RN-18).
 */
interface Authorizer
{
    public function allows(int $userId, string $permission): bool;
}
