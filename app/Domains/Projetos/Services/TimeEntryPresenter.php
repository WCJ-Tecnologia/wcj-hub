<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TimeEntry;
use App\Platform\Models\User;
use Illuminate\Support\Collection;

/**
 * A camada de exibição de *Minhas horas* (F5 §03): a linha do lançamento com o `can` da
 * TimeEntryPolicy, os totais por dia e do período, e as tarefas da busca ao lançar.
 */
class TimeEntryPresenter
{
    public function __construct(private readonly SprintPresenter $tasks) {}

    /**
     * @return array<string, mixed>
     */
    public function row(TimeEntry $entry, User $viewer): array
    {
        $project = $entry->task->project;

        return [
            'id' => $entry->id,
            'worked_on' => $entry->worked_on->toDateString(),
            'hours' => (float) $entry->hours,
            'project' => ['id' => $project->id, 'key_prefix' => $project->key_prefix, 'name' => $project->name],
            'task' => $this->tasks->taskRef($entry->task, $project),
            'can' => ['update' => $viewer->can('update', $entry), 'delete' => $viewer->can('delete', $entry)],
        ];
    }

    /**
     * Total por dia (do mais recente para o mais antigo) e do período.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @return array{days: list<array{date: string, hours: float}>, total: float}
     */
    public function totals(Collection $entries): array
    {
        $days = $entries->groupBy(fn (TimeEntry $entry) => $entry->worked_on->toDateString())
            ->map(fn (Collection $day, string $date) => ['date' => $date, 'hours' => round($day->sum(fn (TimeEntry $entry) => (float) $entry->hours), 2)])
            ->sortKeysDesc()
            ->values()
            ->all();

        return ['days' => $days, 'total' => round($entries->sum(fn (TimeEntry $entry) => (float) $entry->hours), 2)];
    }

    /**
     * A tarefa como opção da busca (o `TaskPickRef` do kit, com o projeto e se é minha).
     *
     * @return array<string, mixed>
     */
    public function taskOption(Task $task, User $viewer): array
    {
        return [
            ...$this->tasks->taskRef($task, $task->project),
            'project_id' => $task->project_id,
            'parent_key' => null,
            'has_children' => false,
            'mine' => $task->developer_id === $viewer->id || $task->qa_id === $viewer->id,
        ];
    }
}
