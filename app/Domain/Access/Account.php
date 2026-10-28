<?php

namespace App\Domain\Access;

/**
 * A user as the access rules see it.
 */
final readonly class Account
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $isAdmin,
        public bool $active,
    ) {}
}
