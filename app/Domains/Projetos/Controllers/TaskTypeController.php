<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Requests\StoreTaskTypeRequest;
use App\Domains\Projetos\Services\TaskTypeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Tipos do projeto pela etiqueta do quadro (F3 §11 item 9; P18): criar e editar nome e cor, só
 * quem gere (ProjectPolicy::manage, no StoreTaskTypeRequest). Tipo de outro projeto é 404.
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
}
