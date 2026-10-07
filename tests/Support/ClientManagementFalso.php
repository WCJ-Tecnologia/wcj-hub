<?php

namespace Tests\Support;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;

/**
 * Dublê do contrato para os testes do Núcleo provarem "gere" e "não gere" sem projeto: a
 * resposta real é do B2. Ligue antes da primeira requisição do teste (o controller guarda o
 * Service que recebeu) e configure depois, que a mesma instância responde.
 */
class ClientManagementFalso implements ClientManagement
{
    /** @var array<int, list<int>> id do cliente → ids de quem o gere */
    private array $gestores = [];

    /** @var array<int, int> id do cliente → projetos abertos */
    private array $abertos = [];

    public static function ligar(): self
    {
        $falso = new self;
        app()->instance(ClientManagement::class, $falso);

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
        return in_array($user->id, $this->gestores[$client->id] ?? [], true);
    }

    public function managedClientIds(User $user): array
    {
        $ids = array_keys(array_filter($this->gestores, fn (array $gestores) => in_array($user->id, $gestores, true)));
        sort($ids);

        return $ids;
    }

    public function openProjectsCount(Client $client): int
    {
        return $this->abertos[$client->id] ?? 0;
    }
}
