<?php

namespace App\Services;

use App\Enums\AuditActionEnum;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(
        AuditActionEnum $action,
        Model $auditable,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action->value,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'before_data' => $before ? $this->sanitize($before) : null,
            'after_data' => $after ? $this->sanitize($after) : null,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 512),
        ]);
    }

    /**
     * Secrets must never reach the audit trail, even indirectly.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        foreach (['password', 'remember_token'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
