<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Support\LocalTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:60'],
            'user_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when(! empty($filters['action']), fn ($q) => $q->where('action', $filters['action']))
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['from']), fn ($q) => $q->where('created_at', '>=', LocalTime::startOfDay($filters['from'])))
            ->when(! empty($filters['to']), fn ($q) => $q->where('created_at', '<=', LocalTime::endOfDay($filters['to'])))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'user' => $log->user?->name ?? 'Sistema',
                'user_email' => $log->user?->email,
                'action' => $log->action,
                'action_label' => AuditActionEnum::tryFrom($log->action)?->label() ?? $log->action,
                'entity' => $log->auditable_type,
                'entity_id' => $log->auditable_id,
                'outcome' => $log->outcome,
                'changes' => $log->changes,
                'ip_address' => $log->ip_address,
                'created_at' => LocalTime::format($log->created_at, 'd/m/Y H:i:s'),
            ]);

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'actionOptions' => collect(AuditActionEnum::cases())->map(fn ($a) => [
                'value' => $a->value,
                'label' => $a->label(),
            ]),
        ]);
    }
}
