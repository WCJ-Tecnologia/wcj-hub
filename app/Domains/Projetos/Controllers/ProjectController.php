<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Requests\StoreProjectRequest;
use App\Domains\Projetos\Services\ProjectQuery;
use App\Domains\Projetos\Services\ProjectService;
use App\Http\Controllers\Controller;
use App\Platform\Models\User;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Lista e cadastro do projeto (F2 §02 a §04; escopo B2). A regra mora no ProjectService. */
class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projects,
        private readonly ProjectQuery $query,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $user = $request->user();
        $filters = [
            'search' => trim((string) $request->string('search')),
            'client_id' => $request->integer('client_id') ?: null,
            'manager_id' => $request->integer('manager_id') ?: null,
            'type' => in_array($request->query('type'), ['delivery', 'continuous'], true) ? (string) $request->query('type') : null,
            'finished' => $request->boolean('finished'),
        ];

        return Inertia::render('projects/index', [
            'projects' => $this->query->paginate($user, $filters)->withQueryString()->through(fn (Project $project) => $this->row($project, $user)),
            'filters' => $filters,
            'filterOptions' => $this->query->filterOptions($user),
            // O dev não cria nem edita projeto: não recebe a lista de clientes e gestores (F1 §09).
            'formOptions' => $user->can('create', Project::class) ? $this->query->formOptions() : null,
            'today' => now(LocalTime::zone())->toDateString(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projects->create($request->projectData());

        return back()->with('success', "Projeto {$project->key_prefix} criado com o quadro padrão.");
    }

    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('manage', $project);

        $this->projects->update($project, $request->projectData());

        return back()->with('success', 'Projeto atualizado.');
    }

    public function finish(Project $project): RedirectResponse
    {
        $this->authorize('manage', $project);

        $this->projects->finish($project);

        return back()->with('success', "Projeto {$project->key_prefix} encerrado. Ele não recebe tarefa nova até ser reaberto.");
    }

    public function reopen(Project $project): RedirectResponse
    {
        $this->authorize('manage', $project);

        $this->projects->reopen($project);

        return back()->with('success', "Projeto {$project->key_prefix} reaberto.");
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        if (! $this->projects->delete($project)) {
            return back()->with('error', 'Projeto já usado: encerre em vez de excluir.');
        }

        return back()->with('success', "Projeto {$project->key_prefix} excluído.");
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Project $project, User $user): array
    {
        return [
            'id' => $project->id,
            'key_prefix' => $project->key_prefix,
            'name' => $project->name,
            'client' => ['id' => $project->client->id, 'name' => $project->client->name],
            'manager' => ['id' => $project->manager->id, 'name' => $project->manager->name],
            'starts_on' => $project->starts_on->toDateString(),
            'due_on' => $project->due_on?->toDateString(),
            'finished_at' => LocalTime::format($project->finished_at, 'd/m/Y'),
            // O prefixo congela depois da 1ª tarefa (P3): a tela o mostra só leitura.
            'prefix_locked' => $project->last_task_number > 0,
            'open_cards' => (int) $project->open_cards_count,
            'can' => [
                'manage' => $user->can('manage', $project),
                'delete' => $user->can('delete', $project),
            ],
        ];
    }
}
