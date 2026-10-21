<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Platform\Models\User;
use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Os cards que cada aba dos indicadores lê (F7 §03, §05): o conjunto filtrado por sprint, dev,
 * tipo, área e busca (*Tempo por etapa*, *Horas*, *Por pessoa*), o projeto inteiro (*Situação*) e
 * as tarefas abertas da pessoa (*Minhas tarefas e horas*). A tarefa conta na sprint em que está
 * agora (P50, P51). Toda consulta traz as relações que as abas usam; nada por linha.
 */
class IndicatorsQuery
{
    /** O filtro de sprint além do id: todas as sprints, ou os cards fora de sprint. */
    public const ALL_SPRINTS = 'todas';

    public const NO_SPRINT = 'fora';

    /**
     * @param  array{search: string, sprint: string, developer_id: ?int, type_id: ?int, area_id: ?int}  $filters
     * @return Collection<int, Task> inclusive as arquivadas: cada aba decide (P26, F7 §10.5)
     */
    public function filtered(Project $project, array $filters): Collection
    {
        $tasks = $this->base($project)
            ->when($filters['sprint'] === self::NO_SPRINT, fn (Builder $q) => $q->whereNull('sprint_id'))
            ->when(ctype_digit($filters['sprint']), fn (Builder $q) => $q->where('sprint_id', (int) $filters['sprint']))
            ->when($filters['developer_id'] !== null, fn (Builder $q) => $q->where('developer_id', $filters['developer_id']))
            ->when($filters['type_id'] !== null, fn (Builder $q) => $q->where('task_type_id', $filters['type_id']))
            ->when($filters['area_id'] !== null, fn (Builder $q) => $q->where('project_area_id', $filters['area_id']))
            ->when($filters['search'] !== '', fn (Builder $q) => $this->search($q, $project, $filters['search']))
            ->orderBy('number')
            ->get();

        return $this->withProject($tasks, $project);
    }

    /** @return Collection<int, Task> o projeto inteiro, fora do arquivo (*Situação*, F7 §05) */
    public function open(Project $project): Collection
    {
        return $this->withProject($this->base($project)->whereNull('archived_at')->orderBy('number')->get(), $project);
    }

    /**
     * As tarefas abertas em que a pessoa é dev ou QA (F7 §03 "Minhas tarefas e horas"): fora do
     * arquivo e fora do pronto. O filtro de prazo é aplicado depois, com o atraso calculado.
     *
     * @return Collection<int, Task>
     */
    public function mine(Project $project, User $user, string $search): Collection
    {
        $tasks = $this->base($project)
            ->whereNull('archived_at')
            ->whereHas('boardStage', fn (Builder $stage) => $stage->where('stage_role_id', '<>', StageRole::DONE))
            ->where(fn (Builder $q) => $q->where('developer_id', $user->id)->orWhere('qa_id', $user->id))
            ->when($search !== '', fn (Builder $q) => $this->search($q, $project, $search))
            ->orderBy('number')
            ->get();

        return $this->withProject($tasks, $project);
    }

    /**
     * As opções dos filtros (F7 §05): sprints (mais recente primeiro), pessoas alocadas e ativas,
     * tipos e áreas ativos do projeto.
     *
     * @return array{sprints: list<array{id: int, name: string, open: bool}>, developers: list<array{id: int, name: string}>, types: list<array{id: int, label: string}>, areas: list<array{id: int, name: string}>}
     */
    public function options(Project $project): array
    {
        return [
            'sprints' => $project->sprints()->orderByDesc('starts_on')->orderByDesc('id')->get(['id', 'name', 'closed_at'])
                ->map(fn (Sprint $sprint) => ['id' => $sprint->id, 'name' => $sprint->name, 'open' => $sprint->closed_at === null])->all(),
            'developers' => $project->users()->active()->orderBy('name')->get(['users.id', 'users.name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])->all(),
            'types' => $project->taskTypes()->active()->orderBy('label')->get(['id', 'label'])
                ->map(fn ($type) => ['id' => $type->id, 'label' => $type->label])->all(),
            'areas' => $project->areas()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($area) => ['id' => $area->id, 'name' => $area->name])->all(),
        ];
    }

    /** @return Builder<Task> */
    private function base(Project $project): Builder
    {
        return $project->tasks()->getQuery()->with([
            'boardStage:id,name,stage_role_id,position,archived_at',
            'boardStage.stageRole:id,slug,label',
            'developer:id,name,daily_hours',
            'qa:id,name',
            'sprint:id,name',
        ]);
    }

    /** Chave ("SGM-17") ou trecho do título, como as outras listas de cards. */
    private function search(Builder $task, Project $project, string $term): void
    {
        $key = preg_match('/^([A-Za-z0-9]+)-(\d+)$/', trim($term), $match) === 1 ? $match : null;
        $byKey = $key !== null && strcasecmp($key[1], $project->key_prefix) === 0;

        $task->where(fn (Builder $inner) => $inner
            ->where('title', 'like', Like::contains($term))
            ->when($byKey, fn (Builder $q) => $q->orWhere('number', (int) $key[2])));
    }

    /**
     * A chave do card (`key()`) lê o prefixo do projeto: a relação vai preenchida para não
     * consultar por linha.
     *
     * @param  Collection<int, Task>  $tasks
     * @return Collection<int, Task>
     */
    private function withProject(Collection $tasks, Project $project): Collection
    {
        return $tasks->each(fn (Task $task) => $task->setRelation('project', $project));
    }
}
