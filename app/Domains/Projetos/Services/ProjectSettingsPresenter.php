<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ProjectArea;
use App\Domains\Projetos\Models\ProjectMember;
use App\Domains\Projetos\Models\ReworkReason;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\User;
use App\Support\LocalTime;

/**
 * A camada de exibição da configuração do projeto e das listas gerais (F2 §03), como os
 * Presenters do Núcleo: cada linha como a tela espera, com o que a Policy decidiu. Espera as
 * coleções como a ProjectSettingsQuery e o ListService as carregam.
 */
class ProjectSettingsPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function project(Project $project, User $viewer): array
    {
        return [
            'id' => $project->id,
            'key_prefix' => $project->key_prefix,
            'name' => $project->name,
            'client' => $project->client->name,
            'manager' => $project->manager->name,
            'finished_at' => LocalTime::format($project->finished_at, 'd/m/Y'),
            'defaults' => ['require_pause_reason' => $project->require_pause_reason, 'sprint_days' => $project->sprint_days],
            'can' => ['manage' => $viewer->can('manage', $project)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function member(ProjectMember $member): array
    {
        $user = $member->user;

        return [
            'id' => $member->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'role_label' => RoleEnum::from($user->role_id)->label(),
            'daily_hours' => $user->daily_hours,
            'active' => $user->isActive(),
            'deactivated_at' => LocalTime::format($user->deactivated_at),
            'deactivated_by' => $user->deactivatedBy?->name,
            'tasks_as_dev' => (int) $member->getAttribute('tasks_as_dev'),
            'tasks_as_qa' => (int) $member->getAttribute('tasks_as_qa'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function stage(BoardStage $stage): array
    {
        $single = in_array($stage->stage_role_id, BoardStageService::SINGLE_ROLES, true);

        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'position' => $stage->position,
            'role_id' => $stage->stage_role_id,
            'role_slug' => $stage->stageRole->slug,
            'role_label' => $stage->stageRole->label,
            'cards' => (int) $stage->getAttribute('cards_count'),
            // Fila, Sprint e Suporte: papel só leitura e sem Remover (P11); nas demais, o papel
            // congela depois que algum card passou pela etapa (P8).
            'role_locked' => $single || (bool) $stage->getAttribute('has_history'),
            'removable' => ! $single,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function area(ProjectArea $area): array
    {
        return [
            'id' => $area->id,
            'name' => $area->name,
            'active' => $area->isActive(),
            'deactivated_at' => LocalTime::format($area->deactivated_at),
            'deactivated_by' => $area->deactivatedBy?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reworkReason(ReworkReason $reason, User $viewer): array
    {
        $other = $reason->slug === ReworkReason::OTHER;

        return [
            'id' => $reason->id,
            'label' => $reason->label,
            'requires_text' => $reason->requires_text,
            // O "Outro" renomeia, mas não desliga o texto, não se desativa nem se exclui (P40).
            'fixed' => $other,
            'active' => $reason->isActive(),
            'deactivated_at' => LocalTime::format($reason->deactivated_at),
            'deactivated_by' => $reason->deactivatedBy?->name,
            'can' => [
                'update' => $viewer->can('update', $reason),
                'deactivate' => ! $other && $reason->isActive() && $viewer->can('deactivate', $reason),
                'reactivate' => ! $reason->isActive() && $viewer->can('reactivate', $reason),
                'delete' => ! $other && $viewer->can('delete', $reason),
            ],
        ];
    }
}
