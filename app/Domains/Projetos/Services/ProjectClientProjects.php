<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Platform\Contracts\ClientProjects;
use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Resposta de Projetos ao Núcleo (ADR-001): os clientes de cada gestor e os projetos abertos. */
class ProjectClientProjects implements ClientProjects
{
    public function managedBy(User $manager): array
    {
        return $this->managedProjects($manager)
            ->distinct()
            ->orderBy('client_id')
            ->pluck('client_id')
            ->map(fn (mixed $id) => (int) $id)
            ->all();
    }

    public function manages(User $manager, int $clientId): bool
    {
        return $this->managedProjects($manager)->where('client_id', $clientId)->exists();
    }

    public function openProjectsCount(int $clientId): int
    {
        return Project::query()->where('client_id', $clientId)->whereNull('finished_at')->count();
    }

    /**
     * Responsável, ou gestor alocado (P5). O dev alocado trabalha no projeto, mas não o gere:
     * a alocação só conta para quem tem o papel de gestor.
     *
     * @return Builder<Project>
     */
    private function managedProjects(User $manager): Builder
    {
        return Project::query()->where(function (Builder $query) use ($manager) {
            $query->where('manager_id', $manager->id);

            if ($manager->isProjectManager()) {
                $query->orWhereHas('members', fn (Builder $members) => $members->where('user_id', $manager->id));
            }
        });
    }
}
