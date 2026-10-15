<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Platform\Models\User;
use App\Support\DiasUteis;
use App\Support\LocalTime;

/**
 * A camada de exibição da tela de sprints (F5 §03), como os outros Presenters: cada linha como a
 * tela espera, com o `can` decidido pela SprintPolicy. Datas de negócio vão em `AAAA-MM-DD` (a
 * tela formata com `dateBr`); o momento do encerramento, em DD/MM/AAAA no fuso de quem usa.
 */
class SprintPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function sprint(Sprint $sprint, User $viewer): array
    {
        $starts = $sprint->starts_on->toDateString();
        $ends = $sprint->ends_on->toDateString();

        return [
            'id' => $sprint->id,
            'name' => $sprint->name,
            'goal' => $sprint->goal,
            'starts_on' => $starts,
            'ends_on' => $ends,
            'business_days' => DiasUteis::between($starts, $ends),
            'open' => $sprint->closed_at === null,
            'closed_at' => LocalTime::format($sprint->closed_at, 'd/m/Y'),
            'tasks_done' => (int) $sprint->getAttribute('tasks_done'),
            'tasks_total' => (int) $sprint->getAttribute('tasks_total'),
            'can' => [
                'update' => $viewer->can('update', $sprint),
                'close' => $sprint->closed_at === null && $viewer->can('close', $sprint),
                'reopen' => $sprint->closed_at !== null && $viewer->can('reopen', $sprint),
                // Só a aberta sem tarefa (G2); a recusa de verdade é do SprintService.
                'delete' => $sprint->closed_at === null && (int) $sprint->getAttribute('tasks_total') === 0 && $viewer->can('delete', $sprint),
            ],
        ];
    }

    /**
     * Linha da lista de tarefas da sprint (F5 §03).
     *
     * @return array<string, mixed>
     */
    public function task(Task $task, Project $project): array
    {
        return [
            ...$this->taskRef($task, $project),
            'stage' => ['id' => $task->boardStage->id, 'name' => $task->boardStage->name, 'role' => $this->roleSlug($task->boardStage->stage_role_id)],
            'developer' => $task->developer === null ? null : ['id' => $task->developer->id, 'name' => $task->developer->name],
            'qa' => $task->qa === null ? null : ['id' => $task->qa->id, 'name' => $task->qa->name],
            'estimated_hours' => $this->hours($task->estimated_hours),
            'proposed_hours' => $this->hours($task->proposed_hours),
            'logged_hours' => (float) ($task->getAttribute('logged_hours') ?? 0),
            'due_on' => $task->due_on?->toDateString(),
        ];
    }

    /**
     * Linha do diálogo de encerrar (F5 §02): chave, título, etapa e dev.
     *
     * @return array<string, mixed>
     */
    public function pending(Task $task, Project $project): array
    {
        return [
            ...$this->taskRef($task, $project),
            'stage' => ['id' => $task->boardStage->id, 'name' => $task->boardStage->name, 'role' => $this->roleSlug($task->boardStage->stage_role_id)],
            'developer' => $task->developer?->name,
        ];
    }

    /**
     * @return array{id: int, key: string, title: string, url: string}
     */
    public function taskRef(Task $task, Project $project): array
    {
        $key = "{$project->key_prefix}-{$task->number}";

        return ['id' => $task->id, 'key' => $key, 'title' => $task->title, 'url' => "/projetos/{$project->id}/cards/{$key}"];
    }

    private function hours(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function roleSlug(int $role): string
    {
        return match ($role) {
            StageRole::QUEUE => 'queue',
            StageRole::SPRINT => 'sprint',
            StageRole::DEVELOPMENT => 'development',
            StageRole::CODE_REVIEW => 'code_review',
            StageRole::TEST => 'test',
            StageRole::DONE => 'done',
            StageRole::PAUSED => 'paused',
            default => 'support',
        };
    }
}
