<?php

namespace App\Platform\Enums;

enum AuditActionEnum: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case LOGIN = 'login';
    case PASSWORD_RESET = 'password_reset';
    // Prova do MCP (D-GER-17, branch poc/mcp): nota gravada pela ferramenta "anotar".
    case POC_NOTE = 'poc_note';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Criado',
            self::UPDATED => 'Atualizado',
            self::DELETED => 'Excluído',
            self::LOGIN => 'Login',
            self::PASSWORD_RESET => 'Senha redefinida',
            self::POC_NOTE => 'Nota da prova do MCP',
        };
    }
}
