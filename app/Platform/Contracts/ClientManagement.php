<?php

namespace App\Platform\Contracts;

use App\Platform\Models\Client;
use App\Platform\Models\User;

/**
 * "Gestor dos projetos do cliente" (F1 §09) é dado de Projetos, e o Platform não lê `projects`
 * (A9, ADR-001). As Policies perguntam a este contrato. O B1 liga a NoClientManagement
 * (ninguém gere cliente algum); o B2 entrega a real em Projetos e troca a ligação no
 * AppServiceProvider.
 */
interface ClientManagement
{
    /** O gestor responde pelo projeto do cliente, ou está alocado nele como gestor (P1, P5). */
    public function manages(User $user, Client $client): bool;

    /** @return list<int> ids dos clientes que a pessoa gere, sem repetição */
    public function managedClientIds(User $user): array;

    /**
     * Projetos ainda não encerrados de cada cliente, para a confirmação de desativar (Clientes
     * AC 9, N11). Em lote, numa consulta só para a página inteira da lista (A7 da review de
     * 07/10; método aprovado por Lucas Scholz na D7).
     *
     * @param  list<int>  $clientIds
     * @return array<int, int> id do cliente → projetos abertos, com todos os ids pedidos
     */
    public function openProjectsCountByClient(array $clientIds): array;
}
