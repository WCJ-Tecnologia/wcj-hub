<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Services\Concerns\PresentsTaskRefs;
use App\Support\LocalTime;

/**
 * As abas do projeto inteiro e do quadro (F7 §03, §06): *Situação* (tarefas por etapa, atrasadas,
 * próximas datas, em pausa, sem data prevista), *Tempo por etapa* (por papel, na ordem do
 * quadro) e *Qualidade* (bugs de produção). As contas são do StageTime e do DelayQuery.
 */
class SituationIndicators
{
    use PresentsTaskRefs;

    public function __construct(
        private readonly IndicatorsQuery $query,
        private readonly StageTimeQuery $stageTimes,
        private readonly DelayQuery $delays,
        private readonly PausedTasksQuery $paused,
        private readonly ProductionBugsQuery $bugs,
        private readonly DueDateService $dueDates,
    ) {}

    /** @return array<string, mixed> */
    public function situation(Project $project, string $today): array
    {
        $tasks = $this->query->open($project);
        $times = $this->stageTimes->forTasks($tasks->pluck('id')->all());
        $stages = $project->boardStages()->whereNull('archived_at')->orderBy('position')->get(['id', 'name', 'stage_role_id']);
        $stages->load('stageRole:id,slug');
        $byStage = $tasks->countBy('board_stage_id');

        $late = [];
        $upcoming = [];
        $withoutDue = [];
        foreach ($tasks as $task) {
            $delay = $this->delays->delay($task, $times[$task->id], $today);
            $done = $task->boardStage->stage_role_id === StageRole::DONE;
            if ($delay !== null && $delay > 0) {
                $late[] = [
                    'task' => $this->ref($task),
                    'developer' => $task->developer?->name,
                    'due_on' => $task->due_on->toDateString(),
                    'paused_days' => StageTime::round($times[$task->id]->pausedSinceDevelopment()),
                    'delay_days' => $delay,
                ];
            } elseif ($task->due_on !== null && ! $done && $task->due_on->toDateString() >= $today) {
                $upcoming[] = ['task' => $this->ref($task), 'developer' => $task->developer?->name, 'due_on' => $task->due_on->toDateString()];
            }
            if ($task->due_on === null && ! $done && $times[$task->id]->firstDevelopmentAt() !== null) {
                $withoutDue[] = ['task' => $this->ref($task), 'missing' => $this->dueDates->missing($task)];
            }
        }
        usort($late, fn (array $a, array $b) => [$b['delay_days'], $a['task']['id']] <=> [$a['delay_days'], $b['task']['id']]);
        usort($upcoming, fn (array $a, array $b) => [$a['due_on'], $a['task']['id']] <=> [$b['due_on'], $b['task']['id']]);

        return [
            'by_stage' => $stages->map(fn (BoardStage $stage) => [
                'id' => $stage->id, 'name' => $stage->name, 'role' => $stage->stageRole->slug, 'count' => $byStage->get($stage->id, 0),
            ])->values()->all(),
            'late' => $late,
            'upcoming' => $upcoming,
            'paused' => $this->paused->forProject($project)->map(function (Task $task) use ($project) {
                /** @var TaskStageMovement|null $pause */
                $pause = $task->getAttribute('pause');
                $task->setRelation('project', $project);

                return [
                    'task' => $this->ref($task),
                    'developer' => $task->developer?->name,
                    'reason' => $pause?->reason_text,
                    'since' => LocalTime::format($pause?->created_at),
                    'by' => $pause?->movedBy?->name,
                ];
            })->values()->all(),
            'without_due' => $withoutDue,
        ];
    }

    /**
     * Tempo por etapa (P11, P53; F7 §03, §06): só os cards que saíram de onde nasceram, fora do
     * arquivo; soma por papel, na ordem do quadro, com as etapas ativas de cada papel.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function stageTime(Project $project, array $filters): array
    {
        $tasks = $this->query->filtered($project, $filters)->whereNull('archived_at');
        $times = $this->stageTimes->forTasks($tasks->pluck('id')->all());
        $roles = $this->roles();
        $stages = $project->boardStages()->whereNull('archived_at')->orderBy('position')->get(['id', 'name', 'stage_role_id', 'position']);

        $totals = [];
        foreach ($tasks as $task) {
            if (! $times[$task->id]->leftOrigin()) {
                continue;
            }
            foreach ($times[$task->id]->byRole() as $role => $days) {
                $totals[$role] ??= ['tasks' => 0, 'days' => 0.0];
                $totals[$role]['tasks']++;
                $totals[$role]['days'] += $days;
            }
        }

        $order = [];
        foreach ($stages as $stage) {
            $order[$stage->stage_role_id] ??= $stage->position;
        }
        uksort($totals, fn (int $a, int $b) => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);

        $rows = [];
        foreach ($totals as $role => $total) {
            $rows[] = [
                'role' => $roles[$role]['slug'],
                'label' => $roles[$role]['label'],
                'stages' => $stages->where('stage_role_id', $role)->pluck('name')->values()->all(),
                'tasks' => $total['tasks'],
                'total_days' => StageTime::round($total['days']),
                'average_days' => StageTime::round($total['days'] / $total['tasks']),
            ];
        }

        return ['rows' => $rows];
    }

    /** @return array<string, mixed> */
    public function quality(Project $project): array
    {
        $bugs = $this->bugs->forProject($project);
        $bugs->each(fn (Task $task) => $task->setRelation('project', $project));

        return [
            'total' => $bugs->count(),
            'rows' => $bugs->map(fn (Task $bug) => [
                'task' => $this->ref($bug),
                'origin' => $this->ref($bug->originTask),
                'created_on' => LocalTime::format($bug->created_at, 'Y-m-d'),
                'stage' => $this->stage($bug),
                'archived' => $bug->archived_at !== null,
            ])->values()->all(),
        ];
    }

    /** @return array<int, array{slug: string, label: string}> */
    private function roles(): array
    {
        return StageRole::query()->orderBy('id')->get(['id', 'slug', 'label'])
            ->mapWithKeys(fn (StageRole $role) => [$role->id => ['slug' => $role->slug, 'label' => $role->label]])->all();
    }
}
