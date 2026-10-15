<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Requests\EstimateTaskRequest;
use App\Domains\Projetos\Requests\SprintTaskRequest;
use App\Domains\Projetos\Requests\SprintTasksBatchRequest;
use App\Domains\Projetos\Services\SprintTaskService;
use App\Domains\Projetos\Services\TaskHoursService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * As tarefas da sprint, pelo gestor e pelo Administrador (F5 §04; P27, P45, P52): pôr na sprint
 * aberta (com a sprint proposta, quando não há aberta, R5), tirar, e as horas previstas na linha
 * da tela da sprint, com a decisão sobre a reestimativa proposta. Todas pela ability
 * `manageTasks` da SprintPolicy. O quadro do B3 reaproveita os mesmos Services no arrastar; o
 * *Montar sprint* do quadro põe várias de uma vez (D-GER-35).
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

    /**
     * *Montar sprint* do quadro (D-GER-35): várias tarefas do Backlog de uma vez, criando a sprint se
     * não há aberta. Volta para a tela de onde veio (o quadro), com o toast.
     */
    public function storeMany(SprintTasksBatchRequest $request, Project $project): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->sprints->addMany($project, $request->taskIds(), $request->user(), $request->newSprint()),
            function (array $result) {
                $name = $result['sprint']->name;
                $count = $result['count'];
                $tasks = $count === 1 ? '1 tarefa entrou' : "{$count} tarefas entraram";
                $message = match (true) {
                    // F5 §07: outra pessoa abriu a sprint no mesmo instante; as tarefas entraram nela.
                    $result['joined_existing'] => "{$tasks} na {$name}, aberta agora por outra pessoa.",
                    $result['created'] => "{$name} criada com ".($count === 1 ? '1 tarefa.' : "{$count} tarefas."),
                    default => "{$tasks} na {$name}.",
                };

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
