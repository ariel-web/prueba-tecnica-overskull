<?php

namespace App\Traits;

use App\Jobs\LogAuditEntry;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        if (!config('audit.enabled')) {
            return;
        }

        static::created(function ($model) {
            $model->dispatchAudit('create', null, $model->toArray());
        });

        static::updated(function ($model) {
            $model->dispatchAudit(
                'update',
                $model->getOriginal(),
                $model->fresh()->toArray()
            );
        });

        static::deleted(function ($model) {
            $model->dispatchAudit('delete', $model->toArray(), null);
        });
    }

    protected function dispatchAudit(string $action, ?array $old, ?array $new): void
    {
        LogAuditEntry::dispatch(
            Auth::id(),
            $action,
            static::class,
            $this->getKey(),
            $old,
            $new
        );
    }
}
