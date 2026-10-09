<?php

namespace App\Platform\Services;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Support\LocalTime;
use App\Support\Paises;

/**
 * A camada de exibição do cliente (stacks/ui.md §2), como o ContactPresenter e o
 * AuditLogPresenter: a linha da lista de Clientes e a ficha. Os botões vêm da ClientPolicy; o
 * número de projetos abertos, do contrato ClientManagement (ADR-001), só para quem pode desativar.
 */
class ClientPresenter
{
    public function __construct(private readonly ClientManagement $management) {}

    /**
     * Para a confirmação de desativar (F1 §04): só quem pode desativar precisa do número, e a
     * página inteira vai numa chamada ao contrato (A7 da review de 07/10).
     *
     * @param  array<int, Client>  $clients
     * @return array<int, int> id do cliente → projetos abertos
     */
    public function openProjects(array $clients, User $viewer): array
    {
        $ids = array_values(array_map(
            fn (Client $client) => $client->id,
            array_filter($clients, fn (Client $client) => $this->canDeactivate($client, $viewer)),
        ));

        return $ids === [] ? [] : $this->management->openProjectsCountByClient($ids);
    }

    /**
     * @param  array<int, int>  $openProjects  o que openProjects() devolveu para a página
     * @return array<string, mixed>
     */
    public function row(Client $client, User $viewer, array $openProjects): array
    {
        $canDeactivate = $this->canDeactivate($client, $viewer);

        return [
            ...$client->only(['id', 'name', 'country', 'document']),
            'country_name' => Paises::nome($client->country),
            'active' => $client->isActive(),
            'deactivated_at' => LocalTime::format($client->deactivated_at),
            'deactivated_by' => $client->deactivatedBy?->name,
            'open_projects' => $canDeactivate ? ($openProjects[$client->id] ?? 0) : null,
            'can' => [
                'update' => $viewer->can('update', $client),
                'deactivate' => $canDeactivate,
                'reactivate' => ! $client->isActive() && $viewer->can('reactivate', $client),
                'delete' => $viewer->can('delete', $client),
            ],
        ];
    }

    private function canDeactivate(Client $client, User $viewer): bool
    {
        return $client->isActive() && $viewer->can('deactivate', $client);
    }
}
