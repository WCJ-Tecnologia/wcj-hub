<?php

namespace App\Platform\Policies;

use App\Platform\Models\User;

/**
 * Usuários: só o Administrador (F1 §09; N1, N2, N4, N11). O gestor só vê a lista de escolha
 * (nomes ativos), que o B2 entrega nas telas de alocar e atribuir.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    // Desativar a si mesmo pode: a trava do último Administrador ativo fica no UserService.
    public function deactivate(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function reactivate(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }

    /** Reenviar convite (F1 §04): a rota reset-password do template (D4). */
    public function resendInvite(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    /** Lista de escolha de usuários ativos (NUC-13): id, nome e papel, para montar projeto. */
    public function viewChoiceList(User $user): bool
    {
        return $user->isAdmin() || $user->isProjectManager();
    }
}
