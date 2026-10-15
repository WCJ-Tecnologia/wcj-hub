<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Requests\EstimateTaskRequest;
use App\Domains\Projetos\Requests\SprintTaskRequest;
use App\Domains\Projetos\Services\SprintTaskService;
use App\Domains\Projetos\Services\TaskHoursService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * As tarefas da sprint, pelo gestor e pelo Administrador (F5 §04; P27, P45, P52): pôr na sprint
 * aberta (com a sprint proposta, quando não há aberta, R5), tirar, e as horas previstas na linha
 * da tela da sprint, com a decisão sobre a reestimativa proposta. Todas pela ability
 * `manageTasks` da SprintPolicy. O quadro do B3 reaproveita os mesmos Services no arrastar.
 */
class SprintTaskController extends Controller
{
    public function __construct(
        private readonly SprintTaskService $sprints,
        private readonly TaskHoursService $hours,
    ) {}

    public function store(SprintTaskRequest $request, Project $project): RedirectResponse
    {
        $task = Task::query()->where('project_id', $project->id)->findOrFail($request->integer('task_id'));

        return $this->attempt(
            fn () => $this->sprints->addTask($task, $request->user(), $request->newSprint()),
            function (array $result) use ($project, $task) {
                $key = "{$project->key_prefix}-{$task->number}";
                // F5 §07: outra pessoa abriu a sprint no mesmo instante; o card entrou nela.
                $message = $result['joined_existing']
                    ? "O card entrou na {$result['sprint']->name}, aberta agora por outra pessoa."
                    : "{$key} entrou na {$result['sprint']->name}.";

                return back()->with($result['joined_existing'] ? 'warning' : 'success', $message);
            },
        );
    }

    public function destroy(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('manageTasks', [Sprint::class, $project]);

        return $this->attempt(fn () => $this->sprints->removeTask($task, $request->user()), "{$project->key_prefix}-{$task->number} saiu da sprint.");
    }

    public function estimate(EstimateTaskRequest $request, Project $project, Task $task): RedirectResponse
    {
        return $this->attempt(fn () => $this->hours->estimate($task, $request->hours(), $request->user()), 'Horas previstas salvas.');
    }

    public function approve(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('manageTasks', [Sprint::class, $project]);

        return $this->attempt(fn () => $this->hours->approveProposal($task, $request->user()), 'Reestimativa aprovada.');
    }

    public function reject(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('manageTasks', [Sprint::class, $project]);

        return $this->attempt(fn () => $this->hours->rejectProposal($task, $request->user()), 'Reestimativa recusada.');
    }
}
