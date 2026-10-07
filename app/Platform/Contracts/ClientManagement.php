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
     * Projetos ainda não encerrados do cliente, para a confirmação de desativar (Clientes AC 9,
     * N11). Acrescentado no B1 fora do contrato da revisão de 07/10, porque a confirmação também
     * precisa de dado de `projects`; pendente de aprovação do Lucas Scholz no PR do B1.
     */
    public function openProjectsCount(Client $client): int;
}
