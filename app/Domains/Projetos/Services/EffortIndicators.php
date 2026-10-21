<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Services\Concerns\PresentsTaskRefs;
use App\Platform\Models\User;

/**
 * As abas de horas (F7 §03, §06; P27, P30, P53, R17): *Horas* (previstas × lançadas por card),
 * *Por pessoa* (as mesmas contas por dev) e *Minhas tarefas e horas* (as abertas da pessoa). As
 * contas são do EffortQuery, do DelayQuery e do StageTime.
 */
class EffortIndicators
{
    use PresentsTaskRefs;

    public function __construct(
        private readonly IndicatorsQuery $query,
        private readonly StageTimeQuery $stageTimes,
        private readonly DelayQuery $delays,
        private readonly EffortQuery $effort,
        private readonly DueDateService $dueDates,
    ) {}

    /**
     * Horas (P27, P30, R17; F7 §03, §06): previstas × lançadas por card, com a diferença e o tempo
     * em desenvolvimento ao lado. A arquivada aparece com a marca e as lançadas; as previstas dela
     * ficam fora do total (F7 §10.5).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function hours(Project $project, array $filters): array
    {
        $tasks = $this->query->filtered($project, $filters);
        $ids = $tasks->pluck('id')->all();
        $times = $this->stageTimes->forTasks($ids);
        $logged = $this->effort->loggedByTaskAndUser($ids);

        $rows = [];
        $totals = ['estimated' => 0.0, 'logged' => 0.0, 'development_hours' => 0.0];
        foreach ($tasks as $task) {
            $estimated = $task->estimated_hours === null ? null : (float) $task->estimated_hours;
            $hours = EffortQuery::total($logged[$task->id] ?? []);
            if ($estimated === null && $hours <= 0) {
                continue;
            }
            $archived = $task->archived_at !== null;
            $development = $this->effort->developmentHours($task, $times[$task->id]);
            $rows[] = [
                'task' => $this->ref($task),
                'developer' => $task->developer?->name,
                'stage' => $this->stage($task),
                'archived' => $archived,
                'estimated' => $estimated,
                'logged' => $hours,
                'difference' => $estimated === null ? null : round($hours - $estimated, 2),
                'development_hours' => $development,
            ];
            $totals['estimated'] += $archived ? 0.0 : ($estimated ?? 0.0);
            $totals['logged'] += $hours;
            $totals['development_hours'] += $development ?? 0.0;
        }

        return [
            'rows' => $rows,
            'totals' => [
                'estimated' => round($totals['estimated'], 2),
                'logged' => round($totals['logged'], 2),
                'difference' => round($totals['logged'] - $totals['estimated'], 2),
                'development_hours' => round($totals['development_hours'], 2),
            ],
        ];
    }

    /**
     * Por pessoa (P53; F7 §03, §06, §10.4): cada card conta para o dev dele; as horas lançadas são
     * as da pessoa em qualquer card do conjunto, inclusive como QA. *Sem dev* junta os cards sem
     * dev, por último. A arquivada só entra pelas horas lançadas.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function people(Project $project, array $filters, string $today): array
    {
        $tasks = $this->query->filtered($project, $filters);
        $ids = $tasks->pluck('id')->all();
        $times = $this->stageTimes->forTasks($ids);
        $logged = $this->effort->loggedByTaskAndUser($ids);

        $rows = [];
        $blank = fn () => ['tasks' => 0, 'estimated' => 0.0, 'logged' => 0.0, 'development_hours' => 0.0, 'late' => 0, 'paused' => 0];
        $withoutDeveloper = $blank();

        foreach ($tasks as $task) {
            foreach ($logged[$task->id] ?? [] as $userId => $hours) {
                $rows[$userId] ??= ['person' => null, 'daily_hours' => null, ...$blank()];
                $rows[$userId]['logged'] += $hours;
            }
            if ($task->archived_at !== null) {
                continue;
            }
            if ($task->developer === null) {
                $withoutDeveloper['tasks']++;
                $withoutDeveloper['estimated'] += (float) ($task->estimated_hours ?? 0);
                $withoutDeveloper['late'] += $this->delays->isLate($task, $times[$task->id], $today) ? 1 : 0;
                $withoutDeveloper['paused'] += $task->boardStage->stage_role_id === StageRole::PAUSED ? 1 : 0;

                continue;
            }
            $dev = $task->developer;
            $rows[$dev->id] ??= ['person' => null, 'daily_hours' => null, ...$blank()];
            $rows[$dev->id]['person'] = ['id' => $dev->id, 'name' => $dev->name];
            $rows[$dev->id]['daily_hours'] = $dev->daily_hours === null ? null : (float) $dev->daily_hours;
            $rows[$dev->id]['tasks']++;
            $rows[$dev->id]['estimated'] += (float) ($task->estimated_hours ?? 0);
            $rows[$dev->id]['development_hours'] += $this->effort->developmentHours($task, $times[$task->id]) ?? 0.0;
            $rows[$dev->id]['late'] += $this->delays->isLate($task, $times[$task->id], $today) ? 1 : 0;
            $rows[$dev->id]['paused'] += $task->boardStage->stage_role_id === StageRole::PAUSED ? 1 : 0;
        }

        // Quem só lançou horas (o QA, por exemplo) ainda não tem nome: uma consulta para todos.
        $nameless = array_keys(array_filter($rows, fn (array $row) => $row['person'] === null));
        if ($nameless !== []) {
            foreach (User::query()->whereKey($nameless)->get(['id', 'name', 'daily_hours']) as $user) {
                $rows[$user->id]['person'] = ['id' => $user->id, 'name' => $user->name];
                $rows[$user->id]['daily_hours'] = $user->daily_hours === null ? null : (float) $user->daily_hours;
            }
        }

        $list = array_map(fn (array $row) => [
            ...$row,
            'estimated' => round($row['estimated'], 2),
            'logged' => round($row['logged'], 2),
            'development_hours' => round($row['development_hours'], 2),
        ], array_values($rows));
        usort($list, fn (array $a, array $b) => strcasecmp($a['person']['name'] ?? '', $b['person']['name'] ?? ''));

        if ($withoutDeveloper['tasks'] > 0) {
            $list[] = ['person' => null, 'daily_hours' => null, ...$withoutDeveloper, 'estimated' => round($withoutDeveloper['estimated'], 2)];
        }

        return ['rows' => $list];
    }

    /**
     * Minhas tarefas e horas (P53; F7 §03, §05): as abertas em que sou dev ou QA, com o atraso e as
     * horas que eu lancei; atrasadas primeiro, depois a data prevista mais próxima.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function mine(Project $project, User $user, array $filters, string $today): array
    {
        $tasks = $this->query->mine($project, $user, $filters['search']);
        $ids = $tasks->pluck('id')->all();
        $times = $this->stageTimes->forTasks($ids);
        $logged = $this->effort->loggedByTaskAndUser($ids);

        $rows = [];
        foreach ($tasks as $task) {
            $delay = $this->delays->delay($task, $times[$task->id], $today);
            $keep = match ($filters['due']) {
                'late' => $delay !== null && $delay > 0,
                'until' => $task->due_on !== null && ($filters['until'] === null || $task->due_on->toDateString() <= $filters['until']),
                'none' => $task->due_on === null,
                default => true,
            };
            if (! $keep) {
                continue;
            }
            $isDev = $task->developer_id === $user->id;
            $rows[] = [
                'task' => $this->ref($task),
                'role' => $isDev && $task->qa_id === $user->id ? 'both' : ($isDev ? 'developer' : 'qa'),
                'stage' => $this->stage($task),
                'due_on' => $task->due_on?->toDateString(),
                'missing' => $task->due_on === null ? $this->dueDates->missing($task) : [],
                'delay_days' => $delay,
                'estimated' => $task->estimated_hours === null ? null : (float) $task->estimated_hours,
                'logged_by_me' => round($logged[$task->id][$user->id] ?? 0.0, 2),
                'development_hours' => $isDev ? $this->effort->developmentHours($task, $times[$task->id]) : null,
            ];
        }
        usort($rows, fn (array $a, array $b) => [
            -($a['delay_days'] ?? 0), $a['due_on'] ?? '9999-99-99', $a['task']['id'],
        ] <=> [
            -($b['delay_days'] ?? 0), $b['due_on'] ?? '9999-99-99', $b['task']['id'],
        ]);

        return ['rows' => $rows];
    }
}
