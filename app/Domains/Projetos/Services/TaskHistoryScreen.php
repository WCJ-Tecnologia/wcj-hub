<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Support\DiasUteis;
use App\Support\LocalTime;

/**
 * O que a linha do tempo do card recebe (F7 §02, §03): o resumo no topo (etapa atual, data
 * prevista, atraso, concluída em, horas, tempo por etapa, bug de produção, arquivado) e um
 * movimento por linha, do mais antigo para o mais recente, com quem, o canal (IA11, R14), de onde
 * para onde, o tempo na etapa de destino, a origem e o motivo do retrabalho (P39) e o motivo da
 * pausa (P15). Etapa removida aparece pelo nome que tinha (P12).
 */
class TaskHistoryScreen
{
    public function __construct(
        private readonly StageTimeQuery $stageTimes,
        private readonly DelayQuery $delays,
        private readonly EffortQuery $effort,
        private readonly DueDateService $dueDates,
    ) {}

    /** @return array<string, mixed> */
    public function props(Task $task, User $viewer): array
    {
        $task->load([
            'project:id,key_prefix,name,client_id', 'project.client:id,name',
            'boardStage:id,name,stage_role_id', 'boardStage.stageRole:id,slug,label',
            'developer:id,name,daily_hours',
            'originTask:id,project_id,number,title', 'originTask.project:id,key_prefix',
            'productionBugs:id,project_id,origin_task_id,number,title', 'productionBugs.project:id,key_prefix',
            'archivedBy:id,name',
        ]);
        $today = DiasUteis::today();
        $times = $this->stageTimes->forTasks([$task->id])[$task->id];
        $movements = $this->stageTimes->movements($task);
        $roles = StageRole::query()->orderBy('id')->get(['id', 'slug', 'label'])->keyBy('id');
        $current = $times->current();
        $done = $task->boardStage->stage_role_id === StageRole::DONE;

        return [
            'project' => ['id' => $task->project->id, 'key_prefix' => $task->project->key_prefix, 'name' => $task->project->name, 'client' => $task->project->client->name],
            'task' => $this->ref($task),
            'summary' => [
                'stage' => ['name' => $task->boardStage->name, 'role' => $task->boardStage->stageRole->slug, 'since' => LocalTime::format($current['from'] ?? null)],
                'due_on' => $task->due_on?->toDateString(),
                'missing' => $task->due_on === null ? $this->dueDates->missing($task) : [],
                'delay_days' => $this->delays->delay($task, $times, $today),
                'done_on' => $done ? LocalTime::format($times->doneAt(), 'Y-m-d') : null,
                'estimated_hours' => $task->estimated_hours === null ? null : (float) $task->estimated_hours,
                'logged_hours' => EffortQuery::total($this->effort->loggedByTaskAndUser([$task->id])[$task->id] ?? []),
                'development_days' => StageTime::round($times->daysIn(StageRole::DEVELOPMENT)),
                'development_hours' => $this->effort->developmentHours($task, $times),
                'by_role' => collect($times->byRole())->map(fn (float $days, int $role) => [
                    'role' => $roles[$role]->slug, 'label' => $roles[$role]->label, 'days' => StageTime::round($days),
                ])->sortBy(fn (array $row, int $role) => $role)->values()->all(),
                'origin' => $task->originTask === null ? null : $this->ref($task->originTask),
                'production_bugs' => $task->productionBugs->sortBy('number')->map(fn (Task $bug) => $this->ref($bug))->values()->all(),
                'archived' => $task->archived_at === null ? null : [
                    'on' => LocalTime::format($task->archived_at, 'Y-m-d'), 'reason' => $task->archive_reason, 'by' => $task->archivedBy?->name,
                ],
            ],
            'movements' => $movements->values()->map(fn (TaskStageMovement $movement, int $index) => $this->row($movement, $times->intervals[$index] ?? null))->all(),
            'audit_url' => $viewer->can('viewAny', AuditLog::class)
                ? route('admin.audit-logs.index', ['type' => 'task', 'id' => $task->id])
                : null,
        ];
    }

    /**
     * @param  array{days: float, open: bool}|null  $interval
     * @return array<string, mixed>
     */
    private function row(TaskStageMovement $movement, ?array $interval): array
    {
        $assistant = $movement->aiAuthorization?->aiAssistant;
        $rework = $movement->rework_origin_id !== null;
        $toPaused = $movement->toBoardStage->stage_role_id === StageRole::PAUSED;

        return [
            'id' => $movement->id,
            'at' => LocalTime::format($movement->created_at),
            'by' => ['name' => $movement->movedBy?->name ?? '—', 'deactivated' => $movement->movedBy?->deactivated_at !== null],
            'channel' => $assistant === null ? 'Tela' : "IA: {$assistant->label}",
            'from' => $this->stage($movement->fromBoardStage),
            'to' => $this->stage($movement->toBoardStage),
            'days' => $interval === null ? null : StageTime::round($interval['days']),
            'open' => $interval['open'] ?? false,
            'rework' => $rework ? [
                'origin' => $movement->reworkOrigin?->label, 'reason' => $movement->reworkReason?->label, 'text' => $movement->reason_text,
            ] : null,
            'pause_reason' => $toPaused && ! $rework ? ($movement->reason_text ?? 'Sem motivo') : null,
        ];
    }

    /** @return array{name: string, role: string, removed: bool}|null */
    private function stage(?BoardStage $stage): ?array
    {
        return $stage === null ? null : ['name' => $stage->name, 'role' => $stage->stageRole->slug, 'removed' => $stage->archived_at !== null];
    }

    /** @return array{id: int, key: string, title: string, url: string} */
    private function ref(Task $task): array
    {
        return ['id' => $task->id, 'key' => $task->key(), 'title' => $task->title, 'url' => $task->url()];
    }
}
