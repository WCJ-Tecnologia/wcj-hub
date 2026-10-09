<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * A resposta de Projetos ao contrato do Núcleo (ADR-001; escopo B2): quais clientes um gestor
 * gere. "Gere" é o mesmo da ProjectPolicy::manages: Gestor de projetos que é o responsável
 * (projects.manager_id) ou está alocado (project_members) em algum projeto do cliente, encerrado
 * ou não (P1, P5). Sem o papel, não gere nenhum. Ligada atrás do MemoizedClientManagement: a lista
 * de quem gere o quê sai uma vez por requisição.
 */
class ProjectClientManagement implements ClientManagement
{
    public function manages(User $user, Client $client): bool
    {
        return in_array($client->getKey(), $this->managedClientIds($user), true);
    }

    public function managedClientIds(User $user): array
    {
        if (! $user->isProjectManager()) {
            return [];
        }

        return Project::query()
            ->where(fn (Builder $query) => $query
                ->where('manager_id', $user->id)
                ->orWhereHas('members', fn (Builder $members) => $members->where('user_id', $user->id)))
            ->distinct()
            ->orderBy('client_id')
            ->pluck('client_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function openProjectsCountByClient(array $clientIds): array
    {
        if ($clientIds === []) {
            return [];
        }

        $counts = Project::query()
            ->whereIn('client_id', $clientIds)
            ->whereNull('finished_at')
            ->groupBy('client_id')
            ->selectRaw('client_id, count(*) as total')
            ->pluck('total', 'client_id');

        return array_combine($clientIds, array_map(fn (int $id) => (int) ($counts[$id] ?? 0), $clientIds));
    }
}
