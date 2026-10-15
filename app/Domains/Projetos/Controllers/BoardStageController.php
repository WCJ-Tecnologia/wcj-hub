<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Requests\RemoveBoardStageRequest;
use App\Domains\Projetos\Requests\ReorderBoardStagesRequest;
use App\Domains\Projetos\Requests\StoreBoardStageRequest;
use App\Domains\Projetos\Services\BoardStageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Etapas do quadro (F2 §04; P10–P12). Cada FormRequest autoriza `manage` antes de validar; a
 * recusa de regra do Service (papel congelado, última de Desenvolvimento) vira toast de erro.
 */
class BoardStageController extends Controller
{
    public function __construct(private readonly BoardStageService $stages) {}

    public function store(StoreBoardStageRequest $request, Project $project): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->stages->create($project, $request->stageData()),
            fn (BoardStage $stage) => back()->with('success', "Etapa {$stage->name} criada no fim do quadro."),
        );
    }

    public function update(StoreBoardStageRequest $request, Project $project, BoardStage $boardStage): RedirectResponse
    {
        return $this->attempt(fn () => $this->stages->update($boardStage, $request->stageData()), 'Etapa atualizada.');
    }

    public function reorder(ReorderBoardStagesRequest $request, Project $project): RedirectResponse
    {
        return $this->attempt(fn () => $this->stages->reorder($project, $request->ids()), 'Ordem do quadro salva.');
    }

    public function destroy(RemoveBoardStageRequest $request, Project $project, BoardStage $boardStage): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->stages->archive($boardStage, $request->destinationId(), $request->user()),
            fn (int $moved) => back()->with('success', "Etapa {$boardStage->name} removida do quadro.".($moved > 0 ? " {$moved} cards foram movidos." : '')),
        );
    }
}
