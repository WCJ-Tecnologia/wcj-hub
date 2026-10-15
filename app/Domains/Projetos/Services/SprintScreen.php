<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\Task;
use App\Platform\Models\User;
use App\Support\DiasUteis;

/**
 * O que a tela `/projetos/{id}/sprints` recebe (F5 §01 a §05): a lista, a sprint escolhida (a do
 * `?sprint=`, senão a aberta, senão a mais recente) com as tarefas e, para quem gere, o painel
 * *Capacidade* (F5 §09); o diálogo de encerrar com as tarefas não prontas e a próxima proposta
 * (P50); e a *Nova sprint* proposta, que só aparece sem sprint aberta (P45). O `can` de cada
 * coisa vem da SprintPolicy.
 */
class SprintScreen
{
    public function __construct(
        private readonly SprintQuery $query,
        private readonly SprintPresenter $presenter,
        private readonly CapacityQuery $capacity,
        private readonly SprintService $sprints,
    ) {}

    /**
     * @param  array{sprint: ?int, search: string, status: ?string, developer_id: ?int, stage_id: ?int, quick: ?string}  $filters
     * @return array<string, mixed>
     */
    public function props(Project $project, User $user, array $filters): array
    {
        $project->load('client:id,name');
        $all = $this->query->sprints($project, ['search' => '', 'status' => null]);
        $listed = $filters['search'] === '' && $filters['status'] === null ? $all : $this->query->sprints($project, $filters);
        $open = $all->first(fn (Sprint $sprint) => $sprint->closed_at === null);
        $selected = $all->firstWhere('id', $filters['sprint']) ?? $open ?? $all->first();

        return [
            'project' => [
                'id' => $project->id,
                'key_prefix' => $project->key_prefix,
                'name' => $project->name,
                'client' => $project->client->name,
                'finished' => $project->isFinished(),
                'sprint_days' => $project->sprint_days,
            ],
            'sprints' => $listed->map(fn (Sprint $sprint) => $this->presenter->sprint($sprint, $user))->values()->all(),
            'selected' => $selected === null ? null : $this->selected($selected, $project, $user, $filters),
            'closing' => $open !== null && $user->can('close', $open) ? [
                'sprint_id' => $open->id,
                'tasks' => $this->query->pending($open)->map(fn (Task $task) => $this->presenter->pending($task, $project))->values()->all(),
                'next' => $this->sprints->nextProposal($open),
            ] : null,
            'proposal' => $open === null && $user->can('create', [Sprint::class, $project]) ? $this->sprints->proposal($project) : null,
            'can' => ['manage_tasks' => $user->can('manageTasks', [Sprint::class, $project])],
            'filters' => $filters,
            'today' => DiasUteis::today(),
        ];
    }

    /**
     * @param  array{sprint: ?int, search: string, status: ?string, developer_id: ?int, stage_id: ?int, quick: ?string}  $filters
     * @return array<string, mixed>
     */
    private function selected(Sprint $sprint, Project $project, User $user, array $filters): array
    {
        return [
            ...$this->presenter->sprint($sprint, $user),
            'tasks' => $this->query->tasks($sprint, $filters, $project->key_prefix)->map(fn (Task $task) => $this->presenter->task($task, $project))->values()->all(),
            'capacity' => $user->can('viewCapacity', [Sprint::class, $project]) ? $this->capacity->forSprint($sprint) : null,
            'options' => $this->query->taskOptions($sprint),
        ];
    }
}
