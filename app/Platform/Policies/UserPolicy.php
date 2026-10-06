<?php

namespace App\Platform\Policies;

use App\Platform\Models\User;

/**
 * Usuários: só o Administrador (escopo, B1; N1, N2). O gestor e o dev veem nomes de usuários
 * só pelas telas de Projetos.
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

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->isAdmin();
    }
}
