<?php

namespace App\Platform\Policies;

use App\Platform\Contracts\ClientProjects;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Clientes (F1 §09; N5 a N7, N11). O gestor vê, cria e escolhe cliente para montar projeto;
 * edita só o cliente de projeto que gere (ClientProjects, ADR-003). Desativar, reativar e
 * excluir são do Administrador.
 */
class ClientPolicy
{
    public const WCJ_NOT_DEACTIVATABLE = 'A WCJ não pode ser desativada';

    public function __construct(private readonly ClientProjects $projects) {}

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isProjectManager();
    }

    public function view(User $user, Client $client): bool
    {
        return $user->isAdmin() || $user->isProjectManager();
    }

    /** O gestor cria pelo formulário do projeto (N6, F2); o B2 chama o mesmo Service. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isProjectManager();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->isAdmin() || ($user->isProjectManager() && $this->projects->manages($user, $client->id));
    }

    /** N7: a WCJ é dona dos projetos internos; a mensagem chega na resposta 403. */
    public function deactivate(User $user, Client $client): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny();
        }

        return $client->isWcj() ? Response::deny(self::WCJ_NOT_DEACTIVATABLE) : Response::allow();
    }

    public function reactivate(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }

    /** Lista de escolha de clientes ativos (NUC-13): id e nome, para montar projeto. */
    public function viewChoiceList(User $user): bool
    {
        return $user->isAdmin() || $user->isProjectManager();
    }
}
