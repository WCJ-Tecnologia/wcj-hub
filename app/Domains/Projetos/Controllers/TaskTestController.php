<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Requests\CriterionResultRequest;
use App\Domains\Projetos\Services\CriterionService;
use App\Domains\Projetos\Services\TaskService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A seção *Teste* da ficha (F4 §02, §04; escopo B4): o teste cruzado (marcar e desfazer, P34) e o
 * resultado de cada item do roteiro (P36, P37). A evidência é anexo, pela rota de anexos com
 * `criterion_id`; devolver para desenvolvimento é o movimento do quadro (`…/mover`, P39).
 */
class TaskTestController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly CriterionService $criteria,
    ) {}

    public function markCrossTest(Request $request, Project $project, Task $card): RedirectResponse
    {
        $this->authorize('crossTest', $card);

        return $this->attempt(fn () => $this->tasks->markCrossTested($card, $request->user()), 'Teste cruzado marcado.');
    }

    public function undoCrossTest(Request $request, Project $project, Task $card): RedirectResponse
    {
        $this->authorize('undoCrossTest', $card);

        return $this->attempt(fn () => $this->tasks->undoCrossTested($card, $request->user()), 'Marca de teste cruzado desfeita.');
    }

    public function setResult(CriterionResultRequest $request, Project $project, Task $card, TaskAcceptanceCriterion $acceptanceCriterion): RedirectResponse
    {
        return $this->attempt(fn () => $this->criteria->setResult($acceptanceCriterion, $request->user(), $request->result()), fn () => back());
    }
}
