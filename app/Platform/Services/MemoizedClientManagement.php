<?php

namespace App\Platform\Services;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;

/**
 * Fica na frente da implementação do contrato (a vazia do B1, a de Projetos no B2). As Policies
 * perguntam "gere este cliente?" linha a linha; aqui a lista de quem gere o quê sai uma vez por
 * pessoa e responde as outras perguntas da mesma requisição (A7 da review de 07/10). Ligada como
 * scoped e esquecida no fim de cada requisição (AppServiceProvider): a memória não passa de uma.
 */
class MemoizedClientManagement implements ClientManagement
{
    /** @var array<int, list<int>> id da pessoa → ids dos clientes que ela gere */
    private array $managed = [];

    public function __construct(private readonly ClientManagement $source) {}

    public function manages(User $user, Client $client): bool
    {
        return in_array($client->getKey(), $this->managedClientIds($user), true);
    }

    public function managedClientIds(User $user): array
    {
        return $this->managed[$user->getKey()] ??= $this->source->managedClientIds($user);
    }

    public function openProjectsCountByClient(array $clientIds): array
    {
        return $this->source->openProjectsCountByClient($clientIds);
    }
}
