<?php

namespace App\Platform\Services;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;

/**
 * Enquanto o B2 não entrega a resposta de Projetos, ninguém gere cliente algum e nenhum
 * cliente tem projeto aberto: o gestor não edita cliente nem contato, e a confirmação de
 * desativar sai sem a contagem.
 */
class NoClientManagement implements ClientManagement
{
    public function manages(User $user, Client $client): bool
    {
        return false;
    }

    public function managedClientIds(User $user): array
    {
        return [];
    }

    public function openProjectsCount(Client $client): int
    {
        return 0;
    }
}
