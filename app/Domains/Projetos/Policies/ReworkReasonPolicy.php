<?php

namespace App\Domains\Projetos\Policies;

use App\Domains\Projetos\Models\ReworkReason;
use App\Platform\Models\User;

/**
 * Motivos de retrabalho, lista geral (G7, P40; F2 §09; escopo B2: "listas gerais: só admin").
 * Usar o motivo numa volta para desenvolvimento não passa por aqui: é do movimento do card (B3).
 */
class ReworkReasonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ReworkReason $reason): bool
    {
        return $user->isAdmin();
    }

    public function deactivate(User $user, ReworkReason $reason): bool
    {
        return $user->isAdmin();
    }

    public function reactivate(User $user, ReworkReason $reason): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ReworkReason $reason): bool
    {
        return $user->isAdmin();
    }
}
