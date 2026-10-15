<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ProjectArea;
use App\Domains\Projetos\Requests\StoreProjectAreaRequest;
use App\Domains\Projetos\Services\ProjectAreaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Áreas do projeto (F2 §04; P18, G2). A regra mora no ProjectAreaService. */
class ProjectAreaController extends Controller
{
    public function __construct(private readonly ProjectAreaService $areas) {}

    public function store(StoreProjectAreaRequest $request, Project $project): RedirectResponse
    {
        $area = $this->areas->create($project, $request->name());

        return back()->with('success', "Área {$area->name} criada.");
    }

    public function update(StoreProjectAreaRequest $request, Project $project, ProjectArea $area): RedirectResponse
    {
        $this->areas->update($area, $request->name());

        return back()->with('success', 'Área atualizada. O nome novo aparece em todos os cards da área.');
    }

    public function deactivate(Request $request, Project $project, ProjectArea $area): RedirectResponse
    {
        $this->authorize('manage', $project);

        return $this->attempt(fn () => $this->areas->deactivate($area, $request->user()), "Área {$area->name} desativada.");
    }

    public function reactivate(Request $request, Project $project, ProjectArea $area): RedirectResponse
    {
        $this->authorize('manage', $project);

        return $this->attempt(fn () => $this->areas->reactivate($area, $request->user()), "Área {$area->name} reativada.");
    }

    // Já usada (G2): toast de erro com a mensagem e nada muda, não 403 (D6 do Lucas, 07/10).
    public function destroy(Request $request, Project $project, ProjectArea $area): RedirectResponse
    {
        $this->authorize('manage', $project);

        return $this->attempt(fn () => $this->areas->delete($area, $request->user()), "Área {$area->name} excluída.");
    }
}
