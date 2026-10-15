<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Platform\Models\User;
use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Leitura da tela de sprints do projeto (F5 §03, §05): a lista, a aberta no topo e as encerradas
 * da mais recente para a mais antiga, com "prontas de total" em lote; e as tarefas de uma sprint,
 * sem as arquivadas (P26), pela ordem das etapas do quadro e, dentro da etapa, pela chave.
 */
class SprintQuery
{
    /**
     * @param  array{search: string, status: ?string}  $filters
     * @return Collection<int, Sprint>
     */
    public function sprints(Project $project, array $filters): Collection
    {
        $done = fn (Builder $tasks) => $tasks->whereHas('boardStage', fn (Builder $stage) => $stage->where('stage_role_id', StageRole::DONE));

        return $project->sprints()
            ->withCount([
                'tasks as tasks_total' => fn (Builder $tasks) => $tasks->whereNull('archived_at'),
                'tasks as tasks_done' => fn (Builder $tasks) => $done($tasks->whereNull('archived_at')),
            ])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->where('name', 'like', Like::contains($filters['search'])))
            ->when($filters['status'] === 'open', fn (Builder $query) => $query->whereNull('closed_at'))
            ->when($filters['status'] === 'closed', fn (Builder $query) => $query->whereNotNull('closed_at'))
            ->orderByRaw('closed_at is not null')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{search: string, developer_id: ?int, stage_id: ?int, quick: ?string}  $filters
     * @return Collection<int, Task>
     */
    public function tasks(Sprint $sprint, array $filters, string $keyPrefix): Collection
    {
        return Task::query()
            ->where('sprint_id', $sprint->id)
            ->whereNull('archived_at')
            ->with(['boardStage:id,name,position,stage_role_id', 'developer:id,name', 'qa:id,name'])
            ->withSum('timeEntries as logged_hours', 'hours')
            ->when($filters['search'] !== '', fn (Builder $query) => $this->search($query, $filters['search'], $keyPrefix))
            ->when($filters['developer_id'], fn (Builder $query, int $id) => $query->where('developer_id', $id))
            ->when($filters['stage_id'], fn (Builder $query, int $id) => $query->where('board_stage_id', $id))
            ->when($filters['quick'] === 'no_developer', fn (Builder $query) => $query->whereNull('developer_id'))
            ->when($filters['quick'] === 'no_hours', fn (Builder $query) => $query->whereNull('estimated_hours'))
            ->orderBy(BoardStage::query()->select('position')->whereColumn('board_stages.id', 'tasks.board_stage_id'))
            ->orderBy('number')
            ->get();
    }

    /**
     * As tarefas que o diálogo de encerrar lista (F5 §02): não chegaram a etapa de papel done e
     * não estão arquivadas, sem filtro.
     *
     * @return Collection<int, Task>
     */
    public function pending(Sprint $sprint): Collection
    {
        return Task::query()
            ->where('sprint_id', $sprint->id)
            ->whereNull('archived_at')
            ->whereHas('boardStage', fn (Builder $stage) => $stage->where('stage_role_id', '!=', StageRole::DONE))
            ->with(['boardStage:id,name,stage_role_id', 'developer:id,name'])
            ->orderBy('number')
            ->get();
    }

    /**
     * Devs e etapas para os filtros das tarefas (F5 §05): os devs com tarefa na sprint e as etapas
     * ativas do quadro.
     *
     * @return array{developers: list<array{id: int, name: string}>, stages: list<array{id: int, name: string}>}
     */
    public function taskOptions(Sprint $sprint): array
    {
        $developers = User::query()
            ->whereIn('id', Task::query()->where('sprint_id', $sprint->id)->whereNull('archived_at')->select('developer_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'developers' => $developers->map(fn (User $dev) => ['id' => $dev->id, 'name' => $dev->name])->all(),
            'stages' => BoardStage::query()->where('project_id', $sprint->project_id)->whereNull('archived_at')->orderBy('position')->get(['id', 'name'])
                ->map(fn (BoardStage $stage) => ['id' => $stage->id, 'name' => $stage->name])->all(),
        ];
    }

    /** Chave ("SGM-17" ou "17") ou trecho do título (F5 §05). */
    private function search(Builder $query, string $term, string $keyPrefix): void
    {
        $number = preg_match('/^(?:'.preg_quote($keyPrefix, '/').'-)?(\d+)$/i', trim($term), $match) === 1 ? (int) $match[1] : null;

        $query->where(fn (Builder $inner) => $inner
            ->where('tasks.title', 'like', Like::contains($term))
            ->when($number !== null, fn (Builder $byKey) => $byKey->orWhere('tasks.number', $number)));
    }
}
