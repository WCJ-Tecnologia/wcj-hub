<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TimeEntry;
use App\Platform\Models\User;
use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Leitura de *Minhas horas* (F5 §03, §05): só os lançamentos de quem está logado, no período
 * (padrão: a semana atual, de segunda a domingo), com busca por chave ou título e filtro por
 * projeto; e as tarefas que a pessoa pode escolher ao lançar.
 */
class TimeEntryQuery
{
    /**
     * @param  array{from: string, to: string, project_id: ?int, search: string}  $filters
     * @return Collection<int, TimeEntry>
     */
    public function mine(User $user, array $filters): Collection
    {
        return TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('worked_on', [$filters['from'], $filters['to']])
            ->when($filters['project_id'], fn (Builder $query, int $id) => $query->whereHas('task', fn (Builder $task) => $task->where('project_id', $id)))
            ->when($filters['search'] !== '', fn (Builder $query) => $query->whereHas('task', fn (Builder $task) => $this->search($task, $filters['search'])))
            // O vínculo da pessoa vem em lote: a Policy (can por linha) lê a relação carregada.
            ->with(['task:id,project_id,number,title', 'task.project:id,key_prefix,name,manager_id', 'task.project.members' => fn ($members) => $members->where('user_id', $user->id)])
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Os projetos que a pessoa vê, para o filtro.
     *
     * @return list<array{id: int, key_prefix: string, name: string}>
     */
    public function projects(User $user): array
    {
        return Project::query()->visibleTo($user)->orderBy('name')->get(['id', 'key_prefix', 'name'])
            ->map(fn (Project $project) => ['id' => $project->id, 'key_prefix' => $project->key_prefix, 'name' => $project->name])
            ->all();
    }

    /**
     * As tarefas em que a pessoa pode lançar (F5 §02): dos projetos que ela vê, sem as arquivadas;
     * primeiro as em que ela é dev ou QA.
     *
     * @return Collection<int, Task>
     */
    public function tasksToLog(User $user): Collection
    {
        return Task::query()
            ->whereNull('archived_at')
            ->whereIn('project_id', Project::query()->visibleTo($user)->select('id'))
            ->with('project:id,key_prefix')
            ->orderByRaw('(developer_id = ? or qa_id = ?) desc', [$user->id, $user->id])
            ->orderBy('project_id')
            ->orderByDesc('number')
            ->get(['id', 'project_id', 'number', 'title', 'developer_id', 'qa_id']);
    }

    /** Chave ("SGM-17") ou trecho do título. */
    private function search(Builder $task, string $term): void
    {
        $key = preg_match('/^([A-Za-z0-9]+)-(\d+)$/', trim($term), $match) === 1 ? $match : null;

        $task->where(fn (Builder $inner) => $inner
            ->where('title', 'like', Like::contains($term))
            ->when($key !== null, fn (Builder $byKey) => $byKey->orWhere(fn (Builder $exact) => $exact
                ->where('number', (int) $key[2])
                ->whereHas('project', fn (Builder $project) => $project->where('key_prefix', $key[1])))));
    }
}
