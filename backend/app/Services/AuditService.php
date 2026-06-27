<?php

namespace App\Services;

use App\Jobs\LogAuditEntry;

class AuditService
{
    public function log(
        ?int $userId,
        string $action,
        string $entity,
        int $entityId,
        ?array $old = null,
        ?array $new = null
    ): void {
        if (!config('audit.enabled')) {
            return;
        }

        LogAuditEntry::dispatch($userId, $action, $entity, $entityId, $old, $new);
    }
}
