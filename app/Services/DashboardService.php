<?php

namespace App\Services;

use App\Enums\AuditActionEnum;
use App\Models\AuditLog;
use App\Models\Enterprise;
use App\Models\User;
use App\Support\LocalTime;

class DashboardService
{
    public function __construct(private readonly Scoping $scoping) {}

    /**
     * Real counts only. Enterprise numbers respect the user's scope.
     *
     * @return array{users_active: int, users_total: int, enterprises_active: int, enterprises_total: int}
     */
    public function summary(User $user): array
    {
        $enterprises = $this->scoping->enterprises(Enterprise::query(), $user);

        return [
            'users_active' => User::query()->active()->count(),
            'users_total' => User::query()->count(),
            'enterprises_active' => (clone $enterprises)->active()->count(),
            'enterprises_total' => (clone $enterprises)->count(),
        ];
    }

    /**
     * @return list<array{id: int, user: string, action_label: string, entity: string, entity_id: int, created_at: string|null}>
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
                'action_label' => AuditActionEnum::tryFrom($log->action)?->label() ?? $log->action,
                'entity' => class_basename($log->auditable_type),
                'entity_id' => (int) $log->auditable_id,
                'created_at' => LocalTime::format($log->created_at),
            ])
            ->all();
    }
}
