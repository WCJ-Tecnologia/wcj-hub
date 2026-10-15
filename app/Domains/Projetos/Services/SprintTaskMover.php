<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TaskType;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use DomainException;

/**
 * A troca de sprint e de etapa de uma tarefa, um jeito só para pôr, tirar e encerrar (P45, P50,
 * P52). Quem chama já travou o projeto e a tarefa. Trocar de etapa grava o movimento (P14) sem
 * retrabalho (tirar da sprint e encerrar não contam, banco.md task_stage_movements) e põe o card
 * no fim da coluna (P13); toda troca audita na mesma transação (G3).
 */
class SprintTaskMover
{
    public function __construct(private readonly AuditService $audit) {}

    /** A sprint e a etapa são do projeto da tarefa (R15). */
    public function relink(Task $task, ?Sprint $sprint, ?BoardStage $stage, User $actor): void
    {
        foreach ([$sprint?->project_id, $stage?->project_id] as $projectId) {
            if ($projectId !== null && $projectId !== $task->project_id) {
                throw new DomainException('A sprint e a etapa precisam ser do projeto da tarefa.');
            }
        }

        $before = $task->only(['sprint_id', 'board_stage_id']);
        $from = $task->board_stage_id;
        $task->sprint_id = $sprint?->id;

        if ($stage !== null && $stage->id !== $from) {
            $task->board_stage_id = $stage->id;
            $task->position = ((int) Task::query()->where('board_stage_id', $stage->id)->max('position')) + 1;
        }
        $task->save();

        if ($task->board_stage_id !== $from) {
            TaskStageMovement::query()->create([
                'task_id' => $task->id,
                'moved_by' => $actor->id,
                'from_board_stage_id' => $from,
                'to_board_stage_id' => $task->board_stage_id,
            ]);
        }
        $this->audit->log(AuditActionEnum::UPDATED, $task, $before, $task->only(['sprint_id', 'board_stage_id']), $actor->id);
    }

    /** A etapa ativa de papel único do projeto (Fila, Sprint, Suporte; P11). */
    public function roleStage(int $projectId, int $role): BoardStage
    {
        return BoardStage::query()
            ->where('project_id', $projectId)
            ->where('stage_role_id', $role)
            ->whereNull('archived_at')
            ->firstOrFail();
    }

    /** Card de suporte (P52): só liga e desliga da sprint, sem trocar de coluna. */
    public function isSupport(Task $task): bool
    {
        return $task->taskType?->slug === TaskType::SUPPORT;
    }
}
