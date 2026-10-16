<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\ValidationType;
use App\Platform\Models\Contact;
use App\Platform\Models\ContactChannel;
use App\Platform\Models\User;
use App\Support\DiasUteis;
use Illuminate\Database\Eloquent\Collection;

/**
 * A camada de exibição do quadro (stacks/ui.md §2): cumpre o contrato do kit em
 * resources/js/types/quadro.ts (`BoardCard`, `BoardProject`, `BoardAbilities`, `BoardOptions`,
 * `BoardStage`). As abilities saem das Policies e só escondem botão; a recusa é do servidor.
 * Espera os cards como a BoardQuery os carrega.
 */
class TaskPresenter
{
    public function __construct(private readonly SprintPlanningQuery $planning) {}

    /**
     * As props da página `projects/board` (`WProjectBoardPageProps`).
     *
     * @param  Collection<int, Task>  $cards
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function board(Project $project, User $viewer, Collection $cards, array $filters): array
    {
        $abilities = $this->abilities($project, $viewer);

        return [
            'project' => $this->project($project),
            'stages' => $project->boardStages()->with('stageRole:id,slug')->whereNull('archived_at')->orderBy('position')->get()
                ->map(fn ($stage) => ['id' => $stage->id, 'name' => $stage->name, 'role' => $stage->stageRole->slug, 'position' => $stage->position])
                ->all(),
            'cards' => $cards->map(fn (Task $card) => $this->card($card))->values()->all(),
            'abilities' => $abilities,
            'options' => $this->options($project),
            'filters' => $filters,
            'today' => DiasUteis::today(),
            // *Montar sprint* (B5b): pessoas e capacidade, só para quem põe card na sprint.
            'planning' => $abilities['manage_sprint'] ? $this->planning->forProject($project) : null,
        ];
    }

    /** @return array<string, mixed> */
    public function card(Task $task): array
    {
        return [
            'id' => $task->id,
            'key' => $task->key(),
            'title' => $task->title,
            'stage_id' => $task->board_stage_id,
            'position' => $task->position,
            'priority' => $task->taskPriority?->slug,
            'type' => $task->taskType === null ? null : [
                'id' => $task->taskType->id, 'slug' => $task->taskType->slug, 'label' => $task->taskType->label, 'color' => $task->taskType->color,
            ],
            'area' => $task->projectArea === null ? null : [
                'id' => $task->projectArea->id, 'name' => $task->projectArea->name, 'color' => $task->projectArea->color,
            ],
            'parent' => $task->parent === null ? null : [
                'id' => $task->parent->id, 'key' => $task->parent->key(), 'title' => $task->parent->title, 'url' => $task->parent->url(),
            ],
            // A capa (P59): o anexo de imagem escolhido na ficha, pela rota de download.
            'cover_url' => $task->cover_attachment_id === null ? null : url("/projetos/{$task->project_id}/cards/{$task->key()}/anexos/{$task->cover_attachment_id}"),
            'estimated_hours' => $task->estimated_hours === null ? null : (float) $task->estimated_hours,
            'proposed_hours' => $task->proposed_hours === null ? null : (float) $task->proposed_hours,
            'developer' => $task->developer === null ? null : ['id' => $task->developer->id, 'name' => $task->developer->name],
            'qa' => $task->qa === null ? null : ['id' => $task->qa->id, 'name' => $task->qa->name],
            'participants' => $task->participants->map(fn (User $person) => ['id' => $person->id, 'name' => $person->name])->values()->all(),
            'due_on' => $task->due_on?->toDateString(),
            'late' => (bool) $task->getAttribute('late'),
            'attachments_count' => (int) $task->attachments_count,
            'comments_count' => (int) $task->comments_count,
            'children' => $task->getAttribute('child_progress'),
            'pause_reason' => $task->getAttribute('pause_reason'),
            'rework_count' => (int) $task->rework_count,
            'support_requester' => $task->requesterContact?->name,
            // Roteiro de QA: B4.
            'qa_summary' => null,
            'url' => $task->url(),
        ];
    }

    /** @return array<string, mixed> */
    private function project(Project $project): array
    {
        $open = Sprint::query()->where('project_id', $project->id)->whereNull('closed_at')->first(['id', 'name']);

        return [
            'id' => $project->id,
            'key_prefix' => $project->key_prefix,
            'name' => $project->name,
            'finished' => $project->isFinished(),
            'pause_requires_reason' => (bool) $project->require_pause_reason,
            'open_sprint' => $open === null ? null : ['id' => $open->id, 'name' => $open->name],
            'sprint_days' => $project->sprint_days,
            'sprints_count' => $project->sprints()->count(),
        ];
    }

    /** @return array<string, bool> */
    private function abilities(Project $project, User $viewer): array
    {
        $open = ! $project->isFinished();
        $manages = $viewer->can('manage', $project);

        return [
            'create_card' => $open && $viewer->can('create', [Task::class, $project]),
            'create_support' => $open && $viewer->can('createSupport', [Task::class, $project]),
            'manage_sprint' => $viewer->can('manageSprint', [Task::class, $project]),
            'move' => $viewer->can('view', $project),
            'archive' => $manages,
            'manage_types' => $manages,
            'manage_areas' => $manages,
        ];
    }

    /** @return array<string, mixed> */
    private function options(Project $project): array
    {
        return [
            'areas' => $project->areas()->active()->orderBy('name')->get(['id', 'name', 'color'])
                ->map(fn ($area) => ['id' => $area->id, 'name' => $area->name, 'color' => $area->color])->all(),
            'types' => $project->taskTypes()->active()->orderBy('id')->get(['id', 'slug', 'label', 'color'])
                ->map(fn ($type) => ['id' => $type->id, 'slug' => $type->slug, 'label' => $type->label, 'color' => $type->color])->all(),
            'members' => User::query()->active()
                ->whereIn('id', $project->members()->select('user_id'))
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])->all(),
            'rework_reasons' => ReworkReason::query()->active()->orderBy('id')->get(['id', 'slug', 'label'])
                ->map(fn ($reason) => ['id' => $reason->id, 'slug' => $reason->slug, 'label' => $reason->label])->all(),
            'rework_origins' => ValidationType::query()->orderBy('id')->get(['id', 'label'])
                ->map(fn ($origin) => ['id' => $origin->id, 'label' => $origin->label])->all(),
            'contacts' => Contact::query()->active()->where('client_id', $project->client_id)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Contact $contact) => ['id' => $contact->id, 'name' => $contact->name])->all(),
            'channels' => ContactChannel::query()->orderBy('id')->get(['id', 'label'])
                ->map(fn ($channel) => ['id' => $channel->id, 'label' => $channel->label])->all(),
        ];
    }
}
