<?php

namespace App\Platform\Policies;

use App\Platform\Models\User;

/**
 * Auditoria (G3; F1 §09, decisão de 06/10): a tela geral é só do Administrador. O gestor vê o
 * histórico de cada registro pela ficha (F7), não por aqui. Não há editar nem apagar.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
