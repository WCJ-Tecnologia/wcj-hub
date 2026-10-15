<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ProjectArea;
use App\Domains\Projetos\Models\ProjectMember;
use App\Domains\Projetos\Requests\UpdateProjectDefaultsRequest;
use App\Domains\Projetos\Services\ProjectMemberService;
use App\Domains\Projetos\Services\ProjectService;
use App\Domains\Projetos\Services\ProjectSettingsPresenter;
use App\Domains\Projetos\Services\ProjectSettingsQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A tela de configuração do projeto, `/projetos/{id}/configuracao` (F2 §01 a §05; escopo B2):
 * as abas Pessoas, Etapas, Áreas e Padrões. Quem vê o projeto abre a tela; o dev vê pessoas e
 * etapas só leitura; áreas, padrões e toda escrita são de quem gere (F2 §09).
 */
class ProjectSettingsController extends Controller
{
    public const TABS = ['pessoas', 'etapas', 'areas', 'padroes'];

    public function __construct(
        private readonly ProjectSettingsQuery $query,
        private readonly ProjectSettingsPresenter $presenter,
        private readonly ProjectMemberService $members,
        private readonly ProjectService $projects,
    ) {}

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $user = $request->user();
        $manages = $user->can('manage', $project);
        $tab = in_array($request->query('aba'), self::TABS, true) ? (string) $request->query('aba') : 'pessoas';
        $showInactive = $request->boolean('show_inactive');
        $project->load(['client:id,name', 'manager:id,name']);

        return Inertia::render('projects/settings', [
            'project' => $this->presenter->project($project, $user),
            'members' => $this->query->members($project)->map(fn (ProjectMember $member) => $this->presenter->member($member))->all(),
            'stages' => $this->query->stages($project)->map(fn (BoardStage $stage) => $this->presenter->stage($stage))->all(),
            // Áreas e padrões são de quem gere (F2 §09); o dev não recebe nem as listas de escolha.
            'areas' => $manages ? $this->query->areas($project, $showInactive)->map(fn (ProjectArea $area) => $this->presenter->area($area))->all() : null,
            'options' => $manages ? ['people' => $this->members->eligible($project), 'stage_roles' => $this->query->repeatableRoles()] : null,
            'filters' => ['tab' => $tab, 'show_inactive' => $showInactive],
        ]);
    }

    public function updateDefaults(UpdateProjectDefaultsRequest $request, Project $project): RedirectResponse
    {
        $this->projects->updateDefaults($project, $request->defaults());

        return back()->with('success', 'Padrões salvos. Valem dali para a frente.');
    }
}
