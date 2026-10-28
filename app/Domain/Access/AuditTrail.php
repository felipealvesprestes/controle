<?php

namespace App\Domain\Access;

/**
 * Append-only record of access changes (RN-20).
 */
interface AuditTrail
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(int $actorId, string $event, string $subjectType, int $subjectId, ?array $before = null, ?array $after = null): void;
}
