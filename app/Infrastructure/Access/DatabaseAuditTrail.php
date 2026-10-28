<?php

namespace App\Infrastructure\Access;

use App\Domain\Access\AuditTrail;
use App\Models\AuditLog;

final class DatabaseAuditTrail implements AuditTrail
{
    public function record(int $actorId, string $event, string $subjectType, int $subjectId, ?array $before = null, ?array $after = null): void
    {
        $log = new AuditLog;
        $log->forceFill([
            'actor_id' => $actorId,
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before' => $before,
            'after' => $after,
        ])->save();
    }
}
