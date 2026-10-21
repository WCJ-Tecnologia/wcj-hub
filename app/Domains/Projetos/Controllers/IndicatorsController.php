<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Services\IndicatorsScreen;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Indicadores do projeto, `/projetos/{id}/indicadores` (F7 §01 a §06; escopo B7): as cinco abas
 * do gestor e do Administrador (`viewIndicators`) e a aba *Minhas tarefas e horas* de quem vê o
 * projeto. O servidor só monta a aba pedida e nunca manda ao dev o que não é dele (P54).
 */
class IndicatorsController extends Controller
{
    public function __construct(private readonly IndicatorsScreen $screen) {}

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/indicators', $this->screen->props($project, $request->user(), $this->filters($request)));
    }

    /**
     * Os filtros da URL (F7 §05): o que não vale vira "sem filtro".
     *
     * @return array{tab: ?string, search: string, sprint: ?string, developer_id: ?int, type_id: ?int, area_id: ?int, due: ?string, until: ?string}
     */
    private function filters(Request $request): array
    {
        $valid = $request->validate([
            'aba' => ['nullable', Rule::in(IndicatorsScreen::TABS)],
            'search' => ['nullable', 'string', 'max:200'],
            'sprint' => ['nullable', 'string', 'max:20'],
            'developer_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'area_id' => ['nullable', 'integer'],
            'due' => ['nullable', Rule::in(['late', 'until', 'none'])],
            'until' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $id = fn (string $key) => isset($valid[$key]) ? (int) $valid[$key] : null;

        return [
            'tab' => $valid['aba'] ?? null,
            'search' => trim((string) ($valid['search'] ?? '')),
            'sprint' => $valid['sprint'] ?? null,
            'developer_id' => $id('developer_id'),
            'type_id' => $id('type_id'),
            'area_id' => $id('area_id'),
            'due' => $valid['due'] ?? null,
            'until' => $valid['until'] ?? null,
        ];
    }
}
