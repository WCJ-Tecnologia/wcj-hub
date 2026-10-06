<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Models\AuditLog;
use App\Platform\Services\AuditLogQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Auditoria (G3; F1 §03, §05): só ver e filtrar. Não há rota de editar nem de apagar. */
class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogQuery $logs) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $this->logs->filters($request->query());

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $this->logs->paginate($filters)->withQueryString(),
            'filters' => $filters,
            'options' => $this->logs->options(),
            // Aberta pelo Histórico de uma ficha (F1 §01): a tela diz de qual registro é.
            'record' => $filters['id'] === null ? null : $this->logs->recordLabel((string) $filters['type'], $filters['id']),
        ]);
    }
}
