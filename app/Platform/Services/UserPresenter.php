<?php

namespace App\Platform\Services;

use App\Platform\Models\User;
use App\Support\LocalTime;

/**
 * A camada de exibição do usuário (stacks/ui.md §2), como o ContactPresenter e o
 * AuditLogPresenter: a linha da lista de Usuários. Os botões vêm da UserPolicy, como em
 * Clientes e Contatos (CR-1-08): a tela não repete regra. Excluir só aparece para quem nunca
 * entrou (F1 §04, G2).
 */
class UserPresenter
{
    /** @return array<string, mixed> */
    public function row(User $user, User $viewer): array
    {
        $active = $user->isActive();

        return [
            ...$user->only(['id', 'name', 'email', 'role_id', 'daily_hours', 'ai_write_allowed', 'must_change_password']),
            'role_label' => $user->type()->label(),
            'active' => $active,
            'deactivated_at' => LocalTime::format($user->deactivated_at),
            'deactivated_by' => $user->deactivatedBy?->name,
            'last_login_at' => LocalTime::format($user->last_login_at),
            'can' => [
                'update' => $viewer->can('update', $user),
                'deactivate' => $active && $viewer->can('deactivate', $user),
                'reactivate' => ! $active && $viewer->can('reactivate', $user),
                'delete' => $user->last_login_at === null && $viewer->can('delete', $user),
                'resend_invite' => $viewer->can('resendInvite', $user),
            ],
        ];
    }
}
