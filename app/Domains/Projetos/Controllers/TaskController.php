<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Requests\ArchiveTaskRequest;
use App\Domains\Projetos\Requests\MoveTaskRequest;
use App\Domains\Projetos\Requests\StoreSupportTaskRequest;
use App\Domains\Projetos\Requests\StoreTaskRequest;
use App\Domains\Projetos\Requests\TaskParticipantRequest;
use App\Domains\Projetos\Requests\UpdateTaskRequest;
use App\Domains\Projetos\Services\TaskArchiveService;
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
 * O card (F3 §02 e §04; escopo B3, PR 1 a 3): a ficha, criar rápido, card de suporte, mover
 * (arrastar e *Mover para…*), editar (ficha e etiquetas), participante, arquivar, desarquivar e
 * excluir. Autorização nos FormRequests (TaskPolicy, antes da validação) ou no authorize() da ação
 * sem corpo; o card da rota é procurado pela chave só entre os do projeto da rota. Sucesso volta ao
 * quadro sem aviso: o card já está onde a pessoa o deixou. Tirar do quadro avisa, porque o card some.
 */
class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly TaskMoveService $moves,
        private readonly TaskTagService $tags,
        private readonly TaskEditService $edit,
        private readonly TaskDetailPresenter $detail,
        private readonly TaskArchiveService $archive,
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

    /** P26: só quem gere; a ficha continua abrindo, e o card volta pelo filtro *Arquivados*. */
    public function archive(ArchiveTaskRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->archive->archive($card, $request->user(), $request->reason()),
            "{$card->key()} arquivado. Ele volta pelo filtro Arquivados do quadro.",
        );
    }

    /** P26: a mesma ability de arquivar (F3 §09); volta ao Backlog, fora de sprint. */
    public function unarchive(Request $request, Project $project, Task $card): RedirectResponse
    {
        $this->authorize('archive', $card);

        return $this->attempt(fn () => $this->archive->unarchive($card, $request->user()), "{$card->key()} desarquivado: está no fim do Backlog.");
    }

    /** P26: só o card nunca usado (TaskUsageQuery); a ficha não existe mais, então volta ao quadro. */
    public function destroy(Request $request, Project $project, Task $card): RedirectResponse
    {
        $this->authorize('delete', $card);
        $key = $card->key();

        return $this->attempt(
            fn () => $this->archive->delete($card, $request->user()),
            fn () => redirect()->route('projects.board', $project)->with('success', "{$key} excluído."),
        );
    }
}
