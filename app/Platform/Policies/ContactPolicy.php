<?php

namespace App\Platform\Policies;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Contatos (F1 §09; N8, N11, N12). O gestor vê, cria e edita só os contatos dos clientes de
 * que o ClientManagement diz que ele gere (ADR-001; vazio até o B2); desativar, reativar, excluir e anonimizar são do
 * Administrador. O dev não usa a tela: lê "quem pediu" pelo card (B3). Anonimizado é estado
 * final (D5): não se edita, não se reativa, não se anonimiza de novo.
 */
class ContactPolicy
{
    public const ANONYMIZED = 'Contato anonimizado: não pode ser alterado';

    public function __construct(private readonly ClientManagement $management) {}

    /**
     * Sem cliente: a lista geral, que o gestor vê filtrada nos clientes dele. Com cliente: os
     * contatos daquele cliente, na ficha.
     */
    public function viewAny(User $user, ?Client $client = null): bool
    {
        if ($client === null) {
            return $user->isAdmin() || $user->isProjectManager();
        }

        return $this->managesClient($user, $client);
    }

    public function create(User $user, Client $client): bool
    {
        return $this->managesClient($user, $client);
    }

    public function update(User $user, Contact $contact): Response
    {
        if (! $this->managesClient($user, $contact->client)) {
            return Response::deny();
        }

        return $contact->isAnonymized() ? Response::deny(self::ANONYMIZED) : Response::allow();
    }

    public function deactivate(User $user, Contact $contact): bool
    {
        return $user->isAdmin();
    }

    public function reactivate(User $user, Contact $contact): Response
    {
        return $this->adminUnlessAnonymized($user, $contact);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->isAdmin();
    }

    public function anonymize(User $user, Contact $contact): Response
    {
        return $this->adminUnlessAnonymized($user, $contact);
    }

    private function managesClient(User $user, ?Client $client): bool
    {
        return $user->isAdmin() || ($user->isProjectManager() && $client !== null && $this->management->manages($user, $client));
    }

    private function adminUnlessAnonymized(User $user, Contact $contact): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny();
        }

        return $contact->isAnonymized() ? Response::deny(self::ANONYMIZED) : Response::allow();
    }
}
