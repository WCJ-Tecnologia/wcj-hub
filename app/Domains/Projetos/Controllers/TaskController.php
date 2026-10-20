<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Requests\MoveTaskRequest;
use App\Domains\Projetos\Requests\StoreSupportTaskRequest;
use App\Domains\Projetos\Requests\StoreTaskRequest;
use App\Domains\Projetos\Requests\TaskParticipantRequest;
use App\Domains\Projetos\Requests\UpdateTaskRequest;
use App\Domains\Projetos\Services\TaskDetailPresenter;
use App\Domains\Projetos\Services\TaskEditService;
use App\Domains\Projetos\Services\TaskMoveService;
use App\Domains\Projetos\Services\TaskService;
use App\Domains\Projetos\Services\TaskTagService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * O card (F3 §02 e §04; escopo B3, PR 1 e 2): a ficha, criar rápido, card de suporte, mover
 * (arrastar e *Mover para…*), editar (ficha e etiquetas) e participante. Autorização nos FormRequests (TaskPolicy,
 * antes da validação); o card da rota é procurado pela chave só entre os do projeto da rota.
 * Sucesso volta ao quadro sem aviso: o card já está onde a pessoa o deixou.
 */
class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly TaskMoveService $moves,
        private readonly TaskTagService $tags,
        private readonly TaskEditService $edit,
        private readonly TaskDetailPresenter $detail,
    ) {}

    /** A ficha do card (F3 §02; Inertia `projects/task`): vê quem vê o projeto, inclusive arquivado. */
    public function show(Request $request, Project $project, Task $card): Response
    {
        $this->authorize('view', $card);

        return Inertia::render('projects/task', $this->detail->page($project, $card, $request->user()));
    }

    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        return $this->attempt(fn () => $this->tasks->create($project, $request->user(), $request->title()), fn () => back());
    }

    public function storeSupport(StoreSupportTaskRequest $request, Project $project): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->tasks->createSupport($project, $request->user(), $request->payload()),
            fn (Task $task) => back()->with('success', "Card de suporte {$task->key()} criado."),
        );
    }

    public function move(MoveTaskRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(fn () => $this->moves->move($card, $request->stage(), $request->user(), $request->payload()), fn () => back());
    }

    /** A ficha e as etiquetas do quadro: um PATCH, cada campo pelo seu Service (TaskEditService). */
    public function update(UpdateTaskRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(fn () => $this->edit->update($card, $request->user(), $request->changes()), fn () => back());
    }

    public function participant(TaskParticipantRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->tags->setParticipant($card, $request->user(), $request->integer('user_id'), $request->boolean('add')),
            fn () => back(),
        );
    }
}
