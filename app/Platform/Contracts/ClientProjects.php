<?php

namespace App\Platform\Contracts;

use App\Platform\Models\User;

/**
 * O que o Núcleo precisa saber dos projetos de um cliente sem ler tabela de módulo (A9,
 * ADR-003). Quem responde é Projetos; a ligação fica no AppServiceProvider.
 */
interface ClientProjects
{
    /**
     * Clientes de algum projeto que a pessoa gere: responsável pelo projeto, ou gestor de
     * projetos alocado nele (P1, P5).
     *
     * @return list<int> ids dos clientes, sem repetição
     */
    public function managedBy(User $manager): array;

    public function manages(User $manager, int $clientId): bool;

    /** Projetos ainda não encerrados do cliente, para a confirmação de desativar (N11). */
    public function openProjectsCount(int $clientId): int;
}
