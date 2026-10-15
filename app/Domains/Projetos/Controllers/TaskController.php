<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Requests\MoveTaskRequest;
use App\Domains\Projetos\Requests\StoreSupportTaskRequest;
use App\Domains\Projetos\Requests\StoreTaskRequest;
use App\Domains\Projetos\Requests\TaskParticipantRequest;
use App\Domains\Projetos\Requests\UpdateTaskTagsRequest;
use App\Domains\Projetos\Services\TaskMoveService;
use App\Domains\Projetos\Services\TaskService;
use App\Domains\Projetos\Services\TaskTagService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * O card no quadro (F3 §04 "No quadro"; escopo B3, PR 1): criar rápido, card de suporte, mover
 * (arrastar e *Mover para…*), etiquetas e participante. Autorização nos FormRequests (TaskPolicy,
 * antes da validação); o card da rota é procurado pela chave só entre os do projeto da rota.
 * Sucesso volta ao quadro sem aviso: o card já está onde a pessoa o deixou.
 */
class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly TaskMoveService $moves,
        private readonly TaskTagService $tags,
    ) {}

    /**
     * A ficha do card é o PR 2 do B3. Até lá, o link do card (título, *Abrir ficha*, *Copiar link*)
     * volta ao quadro com o aviso, em vez de cair num 405.
     */
    public function show(Request $request, Project $project, Task $card): RedirectResponse
    {
        $this->authorize('view', $card);

        return to_route('projects.board', $project)->with('warning', "A ficha de {$card->key()} ainda não está pronta: chega no próximo PR do quadro.");
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

    public function update(UpdateTaskTagsRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(fn () => $this->tags->updateTags($card, $request->user(), $request->changes()), fn () => back());
    }

    public function participant(TaskParticipantRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->tags->setParticipant($card, $request->user(), $request->integer('user_id'), $request->boolean('add')),
            fn () => back(),
        );
    }
}
