<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Requests\CloseSprintRequest;
use App\Domains\Projetos\Requests\StoreSprintRequest;
use App\Domains\Projetos\Services\SprintScreen;
use App\Domains\Projetos\Services\SprintService;
use App\Http\Controllers\Controller;
use App\Support\Busca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela de sprints do projeto, `/projetos/{id}/sprints` (F5 §01 a §05; escopo B5): a lista com a
 * aberta em destaque, as tarefas da sprint escolhida, o painel *Capacidade* (só para quem gere,
 * F5 §09) e as ações de criar, editar, encerrar, reabrir e excluir. A Policy decide quem; o
 * SprintService, as regras de estado (uma aberta, reabrir sem outra aberta, excluir só a vazia).
 */
class SprintController extends Controller
{
    public function __construct(
        private readonly SprintService $sprints,
        private readonly SprintScreen $screen,
    ) {}

    public function index(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [Sprint::class, $project]);

        return Inertia::render('projects/sprints', $this->screen->props($project, $request->user(), $this->filters($request)));
    }

    public function store(StoreSprintRequest $request, Project $project): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->sprints->create($project, $request->sprint(), $request->user()),
            fn (Sprint $sprint) => to_route('projects.sprints.index', [$project, 'sprint' => $sprint->id])->with('success', "{$sprint->name} criada."),
        );
    }

    public function update(StoreSprintRequest $request, Project $project, Sprint $sprint): RedirectResponse
    {
        return $this->attempt(fn () => $this->sprints->update($sprint, $request->sprint(), $request->user()), 'Sprint atualizada.');
    }

    public function close(CloseSprintRequest $request, Project $project, Sprint $sprint): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->sprints->close($sprint, $request->destinations(), $request->next(), $request->user()),
            function (array $result) use ($project, $sprint) {
                $message = "{$sprint->name} encerrada.";
                if ($result['next'] !== null) {
                    $message .= " {$result['next']->name} aberta com {$result['next_count']} tarefas.";
                }
                if ($result['backlog_count'] > 0) {
                    $message .= " {$result['backlog_count']} voltaram ao Backlog.";
                }

                return to_route('projects.sprints.index', [$project, 'sprint' => $result['next']?->id ?? $sprint->id])->with('success', $message);
            },
        );
    }

    public function reopen(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorize('reopen', $sprint);

        return $this->attempt(fn () => $this->sprints->reopen($sprint, $request->user()), "{$sprint->name} reaberta.");
    }

    public function destroy(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorize('delete', $sprint);

        return $this->attempt(
            fn () => $this->sprints->delete($sprint, $request->user()),
            fn () => to_route('projects.sprints.index', $project)->with('success', "{$sprint->name} excluída."),
        );
    }

    /**
     * @return array{sprint: ?int, search: string, status: ?string, developer_id: ?int, stage_id: ?int, quick: ?string}
     */
    private function filters(Request $request): array
    {
        $int = fn (string $key) => $request->integer($key) > 0 ? $request->integer($key) : null;
        $pick = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? (string) $request->query($key) : null;

        return [
            'sprint' => $int('sprint'),
            'search' => Busca::termo($request),
            'status' => $pick('status', ['open', 'closed']),
            'developer_id' => $int('developer_id'),
            'stage_id' => $int('stage_id'),
            'quick' => $pick('quick', ['no_developer', 'no_hours']),
        ];
    }
}
