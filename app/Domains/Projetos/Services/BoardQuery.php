<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Models\TestResult;
use App\Platform\Models\User;
use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

    public function __construct(
        private readonly ChildProgressQuery $children,
        private readonly LateQuery $late,
    ) {}

    /**
     * @param  array{search: string, mine: bool, developer_id: ?int, qa_id: ?int, type_id: ?int, area_id: ?int, priority: ?string, marks: list<string>, old_done: bool, archived: bool}  $filters
     * @return Collection<int, Task>
     */
    public function cards(Project $project, User $viewer, array $filters): Collection
    {
        $query = Task::query()
            ->where('tasks.project_id', $project->id)
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
                // O roteiro (B4) para o aviso do Done (P38): subconsultas no mesmo select, sem consulta por card.
                'acceptanceCriteria as criteria_count',
                'acceptanceCriteria as criteria_failed_count' => fn (Builder $criterion) => $criterion->where('test_result_id', TestResult::FAILED),
                'acceptanceCriteria as criteria_pending_count' => fn (Builder $criterion) => $criterion->where('test_result_id', TestResult::PENDING),
            ]);

        // A lista de arquivados (F3 §03) mostra também o card arquivado numa etapa removida depois;
        // o quadro, só as etapas ativas.
        $filters['archived']
            ? $query->whereNotNull('tasks.archived_at')->orderByDesc('tasks.archived_at')
            : $query->whereNull('tasks.archived_at')->whereHas('boardStage', fn (Builder $stage) => $stage->whereNull('archived_at'));

        if (! $filters['archived'] && ! $filters['old_done']) {
            $this->onlyRecentDone($query, $project);
        }

        $this->applyFilters($query, $project, $viewer, $filters);

        $cards = $query->orderBy('tasks.position')->orderBy('tasks.number')->get();

        $this->fillPauseReasons($cards);
        $this->late->fill($cards);
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
}
