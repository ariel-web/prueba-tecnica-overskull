<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogAuditEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?int $userId,
        public readonly string $action,
        public readonly string $entity,
        public readonly int $entityId,
        public readonly ?array $oldValues = null,
        public readonly ?array $newValues = null,
    ) {}

    public function handle(): void
    {
        AuditLog::create([
            'user_id' => $this->userId,
            'action' => $this->action,
            'entity' => $this->entity,
            'entity_id' => $this->entityId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
        ]);
    }
}
