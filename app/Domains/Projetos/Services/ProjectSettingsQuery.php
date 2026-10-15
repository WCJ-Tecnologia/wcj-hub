<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ProjectArea;
use App\Domains\Projetos\Models\ProjectMember;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Leitura da tela de configuração do projeto (F2 §03): pessoas, etapas e áreas, cada lista em
 * lote (contagens por consulta agrupada, nunca por linha), e os papéis de etapa do formulário.
 */
class ProjectSettingsQuery
{
    /**
     * Pessoas alocadas com o usuário e quantas tarefas do projeto têm cada uma como dev e como
     * QA (para a confirmação de tirar, F2 §04). Usuário desativado continua na lista (N11).
     *
     * @return Collection<int, ProjectMember>
     */
    public function members(Project $project): Collection
    {
        $members = $project->members()
            ->with(['user:id,name,role_id,daily_hours,deactivated_at,deactivated_by', 'user.deactivatedBy:id,name'])
            ->get()
            ->sortBy(fn (ProjectMember $member) => mb_strtolower($member->user->name))
            ->values();

        $asDev = $this->countByUser($project, 'developer_id');
        $asQa = $this->countByUser($project, 'qa_id');
        foreach ($members as $member) {
            $member->setAttribute('tasks_as_dev', $asDev[$member->user_id] ?? 0);
            $member->setAttribute('tasks_as_qa', $asQa[$member->user_id] ?? 0);
        }

        return $members;
    }

    /**
     * Etapas ativas na ordem do quadro, com os cards de hoje em cada uma e se algum card já
     * passou por ela (o papel congela, P8, P11).
     *
     * @return Collection<int, BoardStage>
     */
    public function stages(Project $project): Collection
    {
        $stages = $project->boardStages()
            ->with('stageRole:id,slug,label')
            ->whereNull('archived_at')
            ->withCount(['tasks as cards_count' => fn (Builder $tasks) => $tasks->whereNull('archived_at')])
            ->orderBy('position')
            ->get();

        $ids = $stages->pluck('id')->all();
        $withHistory = TaskStageMovement::query()
            ->whereIn('from_board_stage_id', $ids)
            ->orWhereIn('to_board_stage_id', $ids)
            ->get(['from_board_stage_id', 'to_board_stage_id'])
            ->flatMap(fn (TaskStageMovement $movement) => [$movement->from_board_stage_id, $movement->to_board_stage_id])
            ->filter()
            ->unique()
            ->all();

        foreach ($stages as $stage) {
            $stage->setAttribute('has_history', in_array($stage->id, $withHistory, true));
        }

        return $stages;
    }

    /**
     * @return Collection<int, ProjectArea>
     */
    public function areas(Project $project, bool $showInactive): Collection
    {
        return $project->areas()
            ->with('deactivatedBy:id,name')
            ->when(! $showInactive, fn (Builder $query) => $query->active())
            ->orderBy('name')
            ->get();
    }

    /**
     * Os papéis que a etapa nova pode ter (F2 §02), com rótulo da semente.
     *
     * @return list<array{id: int, label: string}>
     */
    public function repeatableRoles(): array
    {
        return StageRole::query()
            ->whereIn('id', BoardStageService::REPEATABLE_ROLES)
            ->orderBy('id')
            ->get(['id', 'label'])
            ->map(fn (StageRole $role) => ['id' => $role->id, 'label' => $role->label])
            ->all();
    }

    /**
     * @return array<int, int> id do usuário → tarefas do projeto na coluna
     */
    private function countByUser(Project $project, string $column): array
    {
        return Task::query()
            ->where('project_id', $project->id)
            ->whereNotNull($column)
            ->groupBy($column)
            ->selectRaw("{$column} as user_id, count(*) as total")
            ->pluck('total', 'user_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
