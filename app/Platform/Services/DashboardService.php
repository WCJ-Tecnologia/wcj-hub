<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Support\LocalTime;

class DashboardService
{
    /**
     * Real counts only.
     *
     * @return array{users_active: int, users_total: int}
     */
    public function summary(): array
    {
        return [
            'users_active' => User::query()->active()->count(),
            'users_total' => User::query()->count(),
        ];
    }

    /**
     * @return list<array{id: int, user: string, action_label: string, entity: string, entity_id: int|null, created_at: string|null}>
     */
    public function recentActivity(int $limit = 8): array
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'user' => $log->user?->name ?? 'Sistema',
                'action_label' => AuditActionEnum::labelOf($log->action),
                'entity' => $log->auditable_type,
                'entity_id' => $log->auditable_id,
                'created_at' => LocalTime::format($log->created_at),
            ])
            ->all();
    }
}
