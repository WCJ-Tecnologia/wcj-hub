<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Platform\Models\User;
use App\Support\LocalTime;

/**
 * A camada de exibição do projeto (stacks/ui.md §2), como os Presenters do Núcleo: a linha da
 * lista de Projetos, com os botões decididos pela ProjectPolicy. Mora no módulo porque é dado
 * dele (A9, ADR-001). Espera o projeto como a ProjectQuery o carrega (cliente, gestor, contagem de
 * cards abertos e o vínculo da pessoa, em lote).
 */
class ProjectPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function row(Project $project, User $viewer): array
    {
        return [
            'id' => $project->id,
            'key_prefix' => $project->key_prefix,
            'name' => $project->name,
            'client' => ['id' => $project->client->id, 'name' => $project->client->name],
            'manager' => ['id' => $project->manager->id, 'name' => $project->manager->name],
            'starts_on' => $project->starts_on->toDateString(),
            'due_on' => $project->due_on?->toDateString(),
            'finished_at' => LocalTime::format($project->finished_at, 'd/m/Y'),
            // O prefixo congela depois da 1ª tarefa (P3): a tela o mostra só leitura.
            'prefix_locked' => $project->last_task_number > 0,
            'open_cards' => (int) $project->open_cards_count,
            'can' => [
                'manage' => $viewer->can('manage', $project),
                'delete' => $viewer->can('delete', $project),
            ],
        ];
    }
}
