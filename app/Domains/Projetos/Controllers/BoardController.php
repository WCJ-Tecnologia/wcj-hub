<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Services\BoardQuery;
use App\Domains\Projetos\Services\TaskPresenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * O quadro do projeto (F3 §01, §03 e §05; escopo B3): colunas = etapas ativas na ordem de F2, os
 * cards filtrados pelo servidor (`BoardFilters` do kit). Vê quem vê o projeto (P5).
 */
class BoardController extends Controller
{
    public function __construct(
        private readonly BoardQuery $query,
        private readonly TaskPresenter $presenter,
    ) {}

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);
        $filters = $this->filters($request);

        return Inertia::render('projects/board', $this->presenter->board(
            $project,
            $request->user(),
            $this->query->cards($project, $request->user(), $filters),
            $filters,
        ));
    }

    /**
     * Os filtros da URL, como o kit manda (`toQuery`): o que não vale vira "sem filtro".
     *
     * @return array{search: string, mine: bool, developer_id: ?int, qa_id: ?int, type_id: ?int, area_id: ?int, priority: ?string, marks: list<string>, old_done: bool, archived: bool}
     */
    private function filters(Request $request): array
    {
        $valid = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'developer_id' => ['nullable', 'integer'],
            'qa_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'area_id' => ['nullable', 'integer'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'marks' => ['nullable', 'array'],
            'marks.*' => [Rule::in(BoardQuery::MARKS)],
        ]);
        $id = fn (string $key) => isset($valid[$key]) ? (int) $valid[$key] : null;

        return [
            'search' => (string) ($valid['search'] ?? ''),
            'mine' => $request->boolean('mine'),
            'developer_id' => $id('developer_id'),
            'qa_id' => $id('qa_id'),
            'type_id' => $id('type_id'),
            'area_id' => $id('area_id'),
            'priority' => $valid['priority'] ?? null,
            'marks' => array_values(array_unique($valid['marks'] ?? [])),
            'old_done' => $request->boolean('old_done'),
            'archived' => $request->boolean('archived'),
        ];
    }
}
