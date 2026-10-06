<?php

namespace App\Platform\Enums;

use InvalidArgumentException;

enum AuditActionEnum: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case LOGIN = 'login';
    case PASSWORD_RESET = 'password_reset';
    case DEACTIVATED = 'deactivated';
    case REACTIVATED = 'reactivated';
    case ANONYMIZED = 'anonymized';
    // Só aparece em linha negada: a leitura permitida não se audita.
    case VIEWED = 'viewed';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Criado',
            self::UPDATED => 'Atualizado',
            self::DELETED => 'Excluído',
            self::LOGIN => 'Login',
            self::PASSWORD_RESET => 'Senha redefinida',
            self::DEACTIVATED => 'Desativado',
            self::REACTIVATED => 'Reativado',
            self::ANONYMIZED => 'Anonimizado',
            self::VIEWED => 'Consultado',
        };
    }

    /**
     * A ação que a pessoa tentou, para a linha negada (G3). Ability sem ação falha em vez de
     * gravar uma ação que ninguém tentou: toda ability nova entra aqui.
     */
    public static function forAbility(string $ability): self
    {
        return match (true) {
            $ability === 'create' => self::CREATED,
            $ability === 'update' => self::UPDATED,
            $ability === 'delete' => self::DELETED,
            $ability === 'deactivate' => self::DEACTIVATED,
            $ability === 'reactivate' => self::REACTIVATED,
            $ability === 'anonymize' => self::ANONYMIZED,
            in_array($ability, ['resendInvite', 'resetPassword'], true) => self::PASSWORD_RESET,
            str_starts_with($ability, 'view') => self::VIEWED,
            default => throw new InvalidArgumentException("Ability sem ação de auditoria: {$ability}."),
        };
    }
}
