<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Requests\StoreTaskTypeRequest;
use App\Domains\Projetos\Services\TaskTypeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tipos do projeto pela etiqueta do quadro (F3 §11 item 9; P18, G2): criar, editar nome e cor,
 * desativar, reativar e excluir o nunca usado, só quem gere (ProjectPolicy::manage, no
 * StoreTaskTypeRequest e no authorize()). Tipo de outro projeto é 404. A regra mora no
 * TaskTypeService; as mesmas quatro portas da área (inegociável 11).
 */
class TaskTypeController extends Controller
{
    public function __construct(private readonly TaskTypeService $types) {}

    public function store(StoreTaskTypeRequest $request, Project $project): RedirectResponse
    {
        $type = $this->types->create($project, $request->name(), $request->color());

        return back()->with('success', "Tipo {$type->label} criado.");
    }

    public function update(StoreTaskTypeRequest $request, Project $project, TaskType $taskType): RedirectResponse
    {
        $this->types->update($taskType, $request->name(), $request->color());

        return back()->with('success', 'Tipo atualizado. O nome novo aparece em todos os cards do tipo.');
    }

    public function deactivate(Request $request, Project $project, TaskType $taskType): RedirectResponse
    {
        $this->authorize('manage', $project);

        return $this->attempt(fn () => $this->types->deactivate($taskType, $request->user()), "Tipo {$taskType->label} desativado.");
    }

    public function reactivate(Request $request, Project $project, TaskType $taskType): RedirectResponse
    {
        $this->authorize('manage', $project);

        return $this->attempt(fn () => $this->types->reactivate($taskType, $request->user()), "Tipo {$taskType->label} reativado.");
    }

    // Já usado ou de slug (G2): toast de erro com a mensagem e nada muda, não 403 (D6 do Lucas, 07/10).
    public function destroy(Request $request, Project $project, TaskType $taskType): RedirectResponse
    {
        $this->authorize('manage', $project);

        return $this->attempt(fn () => $this->types->delete($taskType, $request->user()), "Tipo {$taskType->label} excluído.");
    }
}
