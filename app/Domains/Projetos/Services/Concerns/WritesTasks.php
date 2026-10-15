<?php

namespace App\Domains\Projetos\Services\Concerns;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Platform\Models\User;
use DomainException;

/**
 * O que toda escrita de card faz igual (B3): reler a linha travada (arquivado não muda, P26), o
 * fim da ordem manual da coluna, a etapa de papel único do projeto, o movimento (P14) e o retrato
 * da auditoria. Um jeito só para o TaskService, o TaskMoveService e o TaskTagService.
 */
trait WritesTasks
{
    public const ARCHIVED = 'Card arquivado não muda: desarquive antes.';

    /** A linha do card, travada e relida, com a etapa atual; arquivado não muda (P26). */
    protected function lockTask(Task $task): Task
    {
        $locked = Task::query()->with('boardStage')->lockForUpdate()->findOrFail($task->id);
        if ($locked->archived_at !== null) {
            throw new DomainException(self::ARCHIVED);
        }

        return $locked;
    }

    /** As etapas de fila, sprint e suporte existem sempre, uma ativa de cada (P11). */
    protected function stageOfRole(Project $project, int $role): BoardStage
    {
        return $project->boardStages()->where('stage_role_id', $role)->whereNull('archived_at')->firstOrFail();
    }

    /** Card que chega à coluna entra no fim da ordem manual (banco.md, tasks.position). */
    protected function nextPosition(BoardStage $stage): int
    {
        return (int) Task::query()->where('board_stage_id', $stage->id)->max('position') + 1;
    }

    /** @param  array{rework_origin_id?: ?int, rework_reason_id?: ?int, reason_text?: ?string}  $fields */
    protected function recordMovement(Task $task, User $actor, ?BoardStage $from, BoardStage $to, array $fields = [], ?int $aiAuthorizationId = null): void
    {
        TaskStageMovement::query()->create([
            'task_id' => $task->id,
            'moved_by' => $actor->id,
            'ai_authorization_id' => $aiAuthorizationId,
            'from_board_stage_id' => $from?->id,
            'to_board_stage_id' => $to->id,
            ...$fields,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshot(Task $task): array
    {
        return [
            'project_id' => $task->project_id,
            'number' => $task->number,
            'title' => $task->title,
            'board_stage_id' => $task->board_stage_id,
            'position' => $task->position,
            'task_type_id' => $task->task_type_id,
            'project_area_id' => $task->project_area_id,
            'task_priority_id' => $task->task_priority_id,
            'sprint_id' => $task->sprint_id,
            'cross_tested_by' => $task->cross_tested_by,
            'due_on' => $task->due_on?->toDateString(),
        ];
    }
}
