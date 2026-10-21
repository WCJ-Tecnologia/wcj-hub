<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use Illuminate\Database\Eloquent\Collection;

/**
 * As tarefas em pausa do projeto (P15; F7 §03 "Situação: em pausa"): o card em etapa de papel
 * pausa, fora do arquivo, com o motivo, desde quando e quem pausou, lidos do último movimento
 * que o levou à etapa. Pausa mais antiga primeiro.
 */
class PausedTasksQuery
{
    /** @return Collection<int, Task> com o atributo `pause` = o movimento de entrada na pausa */
    public function forProject(Project $project): Collection
    {
        $tasks = $project->tasks()
            ->whereNull('archived_at')
            ->whereHas('boardStage', fn ($stage) => $stage->where('stage_role_id', StageRole::PAUSED))
            ->with(['developer:id,name', 'boardStage:id,name,stage_role_id'])
            ->get();

        if ($tasks->isEmpty()) {
            return $tasks;
        }

        $entries = TaskStageMovement::query()
            ->whereIn('task_id', $tasks->pluck('id'))
            ->with('movedBy:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('task_id');

        foreach ($tasks as $task) {
            $task->setAttribute('pause', $entries->get($task->id)?->first(fn (TaskStageMovement $m) => $m->to_board_stage_id === $task->board_stage_id));
        }

        return $tasks
            ->sortBy(fn (Task $task) => [$task->getAttribute('pause')?->created_at?->getTimestamp() ?? 0, $task->number])
            ->values();
    }
}
