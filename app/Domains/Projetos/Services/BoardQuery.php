<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TaskType;
use App\Platform\Models\User;
use App\Support\DiasUteis;
use App\Support\Like;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Os cards do quadro (F3 §03 e §05; P13, P15, P39, P53, P57, P58), com os filtros da URL. O
 * número de consultas não cresce com o número de cards (benchmark do quadro, escopo §10): tudo o
 * que o card mostra vem em lote (relações, contagens, motivo da pausa, atraso, progresso dos
 * filhos). Os cards voltam com os atributos `late`, `pause_reason` e `child_progress` preenchidos
 * para o TaskPresenter.
 */
class BoardQuery
{
    public const MARKS = ['late', 'pause', 'rework', 'support', 'proposed'];

    public function __construct(private readonly ChildProgressQuery $children) {}

    /**
     * @param  array{search: string, mine: bool, developer_id: ?int, qa_id: ?int, type_id: ?int, area_id: ?int, priority: ?string, marks: list<string>, old_done: bool, archived: bool}  $filters
     * @return Collection<int, Task>
     */
    public function cards(Project $project, User $viewer, array $filters): Collection
    {
        $query = Task::query()
            ->where('tasks.project_id', $project->id)
            ->whereHas('boardStage', fn (Builder $stage) => $stage->whereNull('archived_at'))
            ->with([
                'project:id,key_prefix',
                'boardStage:id,stage_role_id,name',
                'taskType:id,slug,label,color',
                'projectArea:id,name,color',
                'taskPriority:id,slug',
                'developer:id,name',
                'qa:id,name',
                'participants:id,name',
                'parent:id,project_id,number,title',
                'parent.project:id,key_prefix',
                'requesterContact:id,name',
            ])
            ->withCount([
                'comments',
                'attachments',
                'movements as rework_count' => fn (Builder $movement) => $movement->whereNotNull('rework_origin_id'),
            ]);

        $filters['archived']
            ? $query->whereNotNull('tasks.archived_at')->orderByDesc('tasks.archived_at')
            : $query->whereNull('tasks.archived_at');

        if (! $filters['archived'] && ! $filters['old_done']) {
            $this->onlyRecentDone($query, $project);
        }

        $this->applyFilters($query, $project, $viewer, $filters);

        $cards = $query->orderBy('tasks.position')->orderBy('tasks.number')->get();

        $this->fillPauseReasons($cards);
        $this->fillLate($cards);
        $progress = $this->children->forParents($cards->pluck('id')->all());
        $cards->each(fn (Task $card) => $card->setAttribute('child_progress', $progress[$card->id] ?? null));

        return $this->withMarks($cards, $filters['marks']);
    }

    /**
     * P13: na coluna de pronto, só os cards da sprint aberta e da última encerrada; os mais antigos
     * vêm com *Mostrar prontos antigos*. Card de pronto fora de sprint (suporte, P52) aparece.
     */
    private function onlyRecentDone(Builder $query, Project $project): void
    {
        $open = $project->sprints()->whereNull('closed_at')->value('id');
        $lastClosed = $project->sprints()->whereNotNull('closed_at')->orderByDesc('closed_at')->orderByDesc('id')->value('id');
        $recent = array_values(array_filter([$open, $lastClosed]));

        $query->where(fn (Builder $q) => $q
            ->whereHas('boardStage', fn (Builder $stage) => $stage->where('stage_role_id', '!=', StageRole::DONE))
            ->orWhereNull('tasks.sprint_id')
            ->orWhereIn('tasks.sprint_id', $recent));
    }

    /** @param  array<string, mixed>  $filters */
    private function applyFilters(Builder $query, Project $project, User $viewer, array $filters): void
    {
        $search = trim($filters['search']);
        if ($search !== '') {
            $number = preg_match('/^'.preg_quote($project->key_prefix, '/').'-?(\d+)$/i', $search, $match) ? (int) $match[1] : null;
            $query->where(fn (Builder $q) => $q
                ->where('tasks.title', 'like', Like::contains($search))
                ->when($number !== null, fn (Builder $q) => $q->orWhere('tasks.number', $number)));
        }

        if ($filters['mine']) {
            $query->where(fn (Builder $q) => $q
                ->where('tasks.developer_id', $viewer->id)
                ->orWhere('tasks.qa_id', $viewer->id)
                ->orWhereHas('participants', fn (Builder $p) => $p->whereKey($viewer->id)));
        }

        foreach (['developer_id' => 'developer_id', 'qa_id' => 'qa_id', 'type_id' => 'task_type_id', 'area_id' => 'project_area_id'] as $filter => $column) {
            if ($filters[$filter] !== null) {
                $query->where("tasks.{$column}", $filters[$filter]);
            }
        }

        if ($filters['priority'] !== null) {
            $query->whereHas('taskPriority', fn (Builder $p) => $p->where('slug', $filters['priority']));
        }
    }

    /**
     * Marcas (F3 §05): o card fica se tem qualquer uma das escolhidas. O atraso é conta, não
     * coluna: por isso o filtro das marcas é sobre os cards já carregados, sem consulta a mais.
     *
     * @param  Collection<int, Task>  $cards
     * @param  list<string>  $marks
     * @return Collection<int, Task>
     */
    private function withMarks(Collection $cards, array $marks): Collection
    {
        if ($marks === []) {
            return $cards;
        }

        return $cards->filter(fn (Task $card) => collect($marks)->contains(fn (string $mark) => match ($mark) {
            'late' => $card->getAttribute('late'),
            'pause' => $card->boardStage->stage_role_id === StageRole::PAUSED,
            'rework' => $card->rework_count > 0,
            'support' => $card->taskType?->slug === TaskType::SUPPORT,
            'proposed' => $card->proposed_hours !== null,
            default => false,
        }))->values();
    }

    /**
     * P15: o motivo do movimento que levou o card à pausa em que ele está; uma consulta para todos.
     *
     * @param  Collection<int, Task>  $cards
     */
    private function fillPauseReasons(Collection $cards): void
    {
        $paused = $cards->filter(fn (Task $card) => $card->boardStage->stage_role_id === StageRole::PAUSED);
        $reasons = $paused->isEmpty() ? collect() : TaskStageMovement::query()
            ->whereIn('task_id', $paused->pluck('id'))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['task_id', 'to_board_stage_id', 'reason_text'])
            ->groupBy('task_id');

        $cards->each(function (Task $card) use ($reasons) {
            $into = $reasons->get($card->id)?->where('to_board_stage_id', $card->board_stage_id)->last();
            $card->setAttribute('pause_reason', $into?->reason_text);
        });
    }

    /**
     * Atraso (P53, R16): passou da data prevista sem chegar ao pronto, descontados os dias úteis
     * em pausa (cada pausa conta os dias úteis inteiros entre entrar e sair; a conta fina dos
     * indicadores é do F7). Uma consulta para todos os cards com data prevista.
     *
     * @param  Collection<int, Task>  $cards
     */
    private function fillLate(Collection $cards): void
    {
        $today = DiasUteis::today();
        $candidates = $cards->filter(fn (Task $card) => $card->due_on !== null
            && $card->boardStage->stage_role_id !== StageRole::DONE
            && $card->due_on->toDateString() < $today);

        $movements = $candidates->isEmpty() ? collect() : TaskStageMovement::query()
            ->join('board_stages', 'board_stages.id', '=', 'task_stage_movements.to_board_stage_id')
            ->whereIn('task_stage_movements.task_id', $candidates->pluck('id'))
            ->orderBy('task_stage_movements.created_at')
            ->orderBy('task_stage_movements.id')
            ->get(['task_stage_movements.task_id', 'task_stage_movements.created_at', 'board_stages.stage_role_id'])
            ->groupBy('task_id');

        $candidateIds = $candidates->pluck('id')->flip();
        $cards->each(function (Task $card) use ($candidateIds, $movements, $today) {
            if (! $candidateIds->has($card->id)) {
                $card->setAttribute('late', false);

                return;
            }
            $paused = $this->pausedBusinessDays($movements->get($card->id) ?? collect(), $today);
            $limit = $paused === 0 ? $card->due_on->toDateString() : DiasUteis::add(DiasUteis::next($card->due_on->toDateString()), $paused);
            $card->setAttribute('late', $today > $limit);
        });
    }

    /** @param  BaseCollection<int, TaskStageMovement>  $movements */
    private function pausedBusinessDays(BaseCollection $movements, string $today): int
    {
        $days = 0;
        $since = null;
        foreach ($movements as $movement) {
            $day = LocalTime::format(CarbonImmutable::parse($movement->created_at, 'UTC'), 'Y-m-d');
            if ($since !== null) {
                $days += max(0, DiasUteis::between($since, $day) - 1);
                $since = null;
            }
            if ((int) $movement->stage_role_id === StageRole::PAUSED) {
                $since = $day;
            }
        }

        return $since === null ? $days : $days + max(0, DiasUteis::between($since, $today) - 1);
    }
}
