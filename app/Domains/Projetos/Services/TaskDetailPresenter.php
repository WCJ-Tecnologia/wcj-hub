<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Models\TaskAttachment;
use App\Domains\Projetos\Models\TaskComment;
use App\Domains\Projetos\Models\TaskPullRequest;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Platform\Models\User;
use App\Support\DiasUteis;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * A ficha do card (F3 §02 a §04, F5 §02): cumpre o `TaskDetail` e o `TaskDetailOptions` do kit
 * (bloco "Ficha do card" de resources/js/types/quadro.ts). As abilities saem das Policies e só
 * escondem botão. Arquivar e excluir vão `false` até as rotas do PR 3 existirem.
 */
class TaskDetailPresenter
{
    public const DISK = 'local';

    public function __construct(
        private readonly TaskPresenter $board,
        private readonly LateQuery $late,
        private readonly DueDateService $dueDates,
    ) {}

    /**
     * As props da página `projects/task` (`WTaskDetailPageProps`).
     *
     * @return array<string, mixed>
     */
    public function page(Project $project, Task $task, User $viewer): array
    {
        $boardProps = $this->board->board($project, $viewer, new Collection, $this->noFilters());

        return [
            'project' => $boardProps['project'],
            'task' => $this->detail($task, $viewer),
            'options' => [
                ...collect($boardProps['options'])->only(['areas', 'types', 'members', 'rework_origins', 'rework_reasons'])->all(),
                'stages' => $boardProps['stages'],
                'cards' => $this->pickable($task),
            ],
            'today' => DiasUteis::today(),
        ];
    }

    /** @return array<string, mixed> */
    public function detail(Task $task, User $viewer): array
    {
        $task->load([
            'project', 'boardStage.stageRole', 'taskType', 'projectArea', 'taskPriority', 'developer', 'qa',
            'participants', 'parent.project', 'originTask.project', 'productionBugs.project', 'children.boardStage.stageRole',
            'children.developer', 'children.project', 'proposedBy', 'archivedBy', 'crossTestedBy', 'sprint', 'package',
            'requesterContact', 'contactChannel', 'pullRequests.user', 'comments.user', 'attachments.user',
            'acceptanceCriteria.testResult',
        ]);
        $this->late->fill(new Collection([$task]));
        $creation = TaskStageMovement::query()->with('movedBy:id,name')->where('task_id', $task->id)
            ->whereNull('from_board_stage_id')->orderBy('id')->first();
        $paused = $task->boardStage->stage_role_id === StageRole::PAUSED
            ? TaskStageMovement::query()->where('task_id', $task->id)->where('to_board_stage_id', $task->board_stage_id)->latest('id')->value('reason_text')
            : null;
        $manages = $viewer->can('manageSprint', [Task::class, $task->project]);

        return [
            'id' => $task->id,
            'key' => $task->key(),
            'title' => $task->title,
            'type' => $task->taskType === null ? null : ['id' => $task->taskType->id, 'slug' => $task->taskType->slug, 'label' => $task->taskType->label, 'color' => $task->taskType->color],
            'area' => $task->projectArea === null ? null : ['id' => $task->projectArea->id, 'name' => $task->projectArea->name, 'color' => $task->projectArea->color],
            'priority' => $task->taskPriority?->slug,
            'stage' => $this->stage($task->boardStage),
            'developer' => $this->person($task->developer),
            'qa' => $this->person($task->qa),
            'participants' => $task->participants->map(fn (User $user) => $this->person($user))->values()->all(),
            'requisition' => [
                'story_as' => $task->story_as,
                'story_want' => $task->story_want,
                'story_so_that' => $task->story_so_that,
                'description' => $task->description,
            ],
            'criteria' => $this->criteria($task->acceptanceCriteria),
            'pull_requests' => $task->pullRequests->sortBy('id')->map(fn (TaskPullRequest $pr) => [
                'id' => $pr->id, 'url' => $pr->url, 'added_by' => $this->person($pr->user), 'added_at' => LocalTime::format($pr->created_at, 'Y-m-d H:i'),
            ])->values()->all(),
            'comments' => $task->comments->sortBy('id')->map(fn (TaskComment $comment) => [
                'id' => $comment->id,
                'author' => $this->person($comment->user),
                'body' => $comment->body,
                'created_at' => LocalTime::format($comment->created_at, 'Y-m-d H:i'),
                'edited_at' => $comment->updated_at->gt($comment->created_at) ? LocalTime::format($comment->updated_at, 'Y-m-d H:i') : null,
                'can' => ['update' => $viewer->can('update', $comment), 'delete' => $viewer->can('delete', $comment)],
            ])->values()->all(),
            'attachments' => $task->attachments->sortBy('id')->map(fn (TaskAttachment $file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'size' => Storage::disk(self::DISK)->exists($file->path) ? Storage::disk(self::DISK)->size($file->path) : 0,
                'is_image' => $file->isImage(),
                'url' => $this->attachmentUrl($task, $file),
                'uploaded_by' => $this->person($file->user),
                'uploaded_at' => LocalTime::format($file->created_at, 'Y-m-d H:i'),
                'criterion_id' => $file->task_acceptance_criterion_id,
            ])->values()->all(),
            'cover_attachment_id' => $task->cover_attachment_id,
            'origin_task' => $this->ref($task->originTask),
            'production_bugs' => $task->productionBugs->whereNull('archived_at')->sortBy('number')->map(fn (Task $bug) => $this->ref($bug))->values()->all(),
            'parent' => $task->parent === null ? null : $this->ref($task->parent),
            'children' => $task->children->whereNull('archived_at')->sortBy('number')->map(fn (Task $child) => [
                ...$this->ref($child), 'stage' => $this->stage($child->boardStage), 'developer' => $this->person($child->developer),
            ])->values()->all(),
            'estimated_hours' => $task->estimated_hours === null ? null : (float) $task->estimated_hours,
            'proposed_hours' => $task->proposed_hours === null ? null : (float) $task->proposed_hours,
            'proposed_by' => $this->person($task->proposedBy),
            'logged_hours' => (float) $task->timeEntries()->sum('hours'),
            'developer_daily_hours' => $task->developer?->daily_hours === null ? null : (float) $task->developer->daily_hours,
            'development_started_on' => $this->dueDates->firstDevelopmentDay($task),
            'due_on' => $task->due_on?->toDateString(),
            'late' => (bool) $task->getAttribute('late'),
            'sprint' => $task->sprint === null ? null : ['id' => $task->sprint->id, 'name' => $task->sprint->name],
            'package' => $task->package === null ? null : ['id' => $task->package->id, 'name' => $task->package->name],
            'cross_tested' => $task->crossTestedBy === null ? null : ['by' => $this->person($task->crossTestedBy), 'at' => LocalTime::format($task->cross_tested_at, 'Y-m-d')],
            'archived' => $task->archived_at === null ? null : [
                'at' => LocalTime::format($task->archived_at, 'Y-m-d'), 'reason' => $task->archive_reason, 'by' => $this->person($task->archivedBy),
            ],
            'pause_reason' => $paused,
            'rework_count' => $task->movements()->whereNotNull('rework_origin_id')->count(),
            'support' => $task->requesterContact === null ? null : [
                'requester' => $task->requesterContact->name,
                'channel' => $task->contactChannel?->label ?? '—',
                'requested_at' => LocalTime::format($task->requested_at, 'Y-m-d H:i'),
            ],
            'created_by' => $this->person($creation?->movedBy),
            'created_at' => LocalTime::format($task->created_at, 'Y-m-d H:i'),
            'url' => $task->url(),
            // Linha do tempo: B7.
            'history_url' => null,
            'abilities' => [
                'update' => $viewer->can('update', $task),
                'comment' => $viewer->can('comment', $task),
                'attach' => $viewer->can('attach', $task),
                'set_hours' => $manages,
                'propose_hours' => $viewer->can('proposeHours', $task),
                'decide_hours' => $manages,
                'move' => $viewer->can('move', $task),
                'manage_sprint' => $manages,
                // Arquivar e excluir: PR 3 do B3.
                'archive' => false,
                'delete' => false,
                'manage_types' => $viewer->can('manage', $task->project),
                'manage_areas' => $viewer->can('manage', $task->project),
            ],
        ];
    }

    public function attachmentUrl(Task $task, TaskAttachment $file): string
    {
        return url("/projetos/{$task->project_id}/cards/{$task->key()}/anexos/{$file->id}");
    }

    /**
     * Critérios na ordem CA_1, CA_2…, subitens dentro do pai (T9).
     *
     * @param  Collection<int, TaskAcceptanceCriterion>  $all
     * @return list<array<string, mixed>>
     */
    private function criteria(Collection $all): array
    {
        $children = $all->whereNotNull('parent_id')->groupBy('parent_id');

        return $all->whereNull('parent_id')->sortBy('number')->map(fn (TaskAcceptanceCriterion $criterion) => [
            'id' => $criterion->id,
            'number' => "CA_{$criterion->number}",
            'description' => $criterion->description,
            'result' => $criterion->testResult->slug === 'passed' ? 'approved' : $criterion->testResult->slug,
            'children' => ($children->get($criterion->id) ?? collect())->sortBy('number')->map(fn (TaskAcceptanceCriterion $item) => [
                'id' => $item->id,
                'number' => "CA_{$criterion->number}.{$item->number}",
                'description' => $item->description,
                'result' => $item->testResult->slug === 'passed' ? 'approved' : $item->testResult->slug,
                'children' => [],
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * Os cards do projeto para as buscas de card pai e tarefa de origem (F3 §05): sem arquivados e
     * sem este card; com quem já é filho e quem já tem filhos, para a tela recusar (P57).
     *
     * @return list<array<string, mixed>>
     */
    private function pickable(Task $task): array
    {
        $withChildren = Task::query()->where('project_id', $task->project_id)->whereNotNull('parent_task_id')
            ->distinct()->pluck('parent_task_id')->flip();
        $keys = Task::query()->where('project_id', $task->project_id)->pluck('number', 'id');

        return Task::query()->where('project_id', $task->project_id)->whereNull('archived_at')->whereKeyNot($task->id)
            ->orderBy('number')->get(['id', 'project_id', 'number', 'title', 'parent_task_id'])
            ->map(fn (Task $card) => [
                'id' => $card->id,
                'key' => "{$task->project->key_prefix}-{$card->number}",
                'title' => $card->title,
                'url' => url("/projetos/{$task->project_id}/cards/{$task->project->key_prefix}-{$card->number}"),
                'parent_key' => $card->parent_task_id === null ? null : "{$task->project->key_prefix}-{$keys[$card->parent_task_id]}",
                'has_children' => $withChildren->has($card->id),
            ])->all();
    }

    /** @return array{id: int, name: string, role: string, position: int} */
    private function stage(BoardStage $stage): array
    {
        return ['id' => $stage->id, 'name' => $stage->name, 'role' => $stage->stageRole->slug, 'position' => $stage->position];
    }

    /** @return array{id: int, key: string, title: string, url: string}|null */
    private function ref(?Task $task): ?array
    {
        return $task === null ? null : ['id' => $task->id, 'key' => $task->key(), 'title' => $task->title, 'url' => $task->url()];
    }

    /** @return array{id: int, name: string}|null */
    private function person(?User $user): ?array
    {
        return $user === null ? null : ['id' => $user->id, 'name' => $user->name];
    }

    /** @return array<string, mixed> */
    private function noFilters(): array
    {
        return ['search' => '', 'mine' => false, 'developer_id' => null, 'qa_id' => null, 'type_id' => null, 'area_id' => null, 'priority' => null, 'marks' => [], 'old_done' => false, 'archived' => false];
    }
}
