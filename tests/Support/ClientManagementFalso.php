<?php

namespace Tests\Support;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Services\MemoizedClientManagement;

/**
 * Dublê do contrato para os testes do Núcleo provarem "gere" e "não gere" sem projeto: a
 * resposta real é do B2. Entra no lugar da implementação, atrás do MemoizedClientManagement,
 * como a real. Ligue antes da primeira requisição do teste (o controller guarda o Service que
 * recebeu) e configure depois, que a mesma instância responde a partir da requisição seguinte.
 */
class ClientManagementFalso implements ClientManagement
{
    /** @var array<int, list<int>> id do cliente → ids de quem o gere */
    private array $gestores = [];

    /** @var array<int, int> id do cliente → projetos abertos */
    private array $abertos = [];

    /** @var array<string, int> método do contrato → quantas vezes foi chamado (T42: sem N+1) */
    public array $chamadas = ['manages' => 0, 'managedClientIds' => 0, 'openProjectsCountByClient' => 0];

    public static function ligar(): self
    {
        $falso = new self;
        app()->when(MemoizedClientManagement::class)->needs(ClientManagement::class)->give(fn () => $falso);
        app()->forgetInstance(ClientManagement::class);

        return $falso;
    }

    public function gere(User $user, Client ...$clients): self
    {
        foreach ($clients as $client) {
            $this->gestores[$client->id][] = $user->id;
        }

        return $this;
    }

    public function projetosAbertos(Client $client, int $quantos): self
    {
        $this->abertos[$client->id] = $quantos;

        return $this;
    }

    public function manages(User $user, Client $client): bool
    {
        $this->chamadas['manages']++;

        return in_array($user->id, $this->gestores[$client->id] ?? [], true);
    }

    public function managedClientIds(User $user): array
    {
        $this->chamadas['managedClientIds']++;
        $ids = array_keys(array_filter($this->gestores, fn (array $gestores) => in_array($user->id, $gestores, true)));
        sort($ids);

        return $ids;
    }

    public function openProjectsCountByClient(array $clientIds): array
    {
        $this->chamadas['openProjectsCountByClient']++;

        return array_combine($clientIds, array_map(fn (int $id) => $this->abertos[$id] ?? 0, $clientIds));
    }
}
