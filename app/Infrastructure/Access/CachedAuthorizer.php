<?php

namespace App\Infrastructure\Access;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\Authorizer;
use App\Domain\Access\PermissionResolver;

/**
 * Resolves a user's permissions once per request (scoped binding).
 */
final class CachedAuthorizer implements Authorizer
{
    /** @var array<int, array<string, true>> */
    private array $allowed = [];

    public function __construct(
        private readonly AccessRepository $accounts,
        private readonly PermissionResolver $resolver,
    ) {}

    public function allows(int $userId, string $permission): bool
    {
        $this->allowed[$userId] ??= array_fill_keys($this->resolver->allowedKeys($this->accounts->grantsFor($userId)), true);

        return isset($this->allowed[$userId][$permission]);
    }
}
