<?php

namespace App\Platform\Enums;

enum AuditActionEnum: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case LOGIN = 'login';
    case PASSWORD_RESET = 'password_reset';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Criado',
            self::UPDATED => 'Atualizado',
            self::DELETED => 'Excluído',
            self::LOGIN => 'Login',
            self::PASSWORD_RESET => 'Senha redefinida',
        };
    }
}
