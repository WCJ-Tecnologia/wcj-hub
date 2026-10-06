<?php

namespace App\Platform\Enums;

/**
 * Os 4 papéis fixos da v1 (N2; docs/banco.txt, roles). Os ids são os da semente: o código
 * compara pelo id ou pelo slug, nunca pelo rótulo.
 */
enum RoleEnum: int
{
    case ADMIN = 1;
    case PROJECT_MANAGER = 2;
    case DEVELOPER = 3;
    case SALES = 4;

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::PROJECT_MANAGER => 'Gestor de projetos',
            self::DEVELOPER => 'Desenvolvedor',
            self::SALES => 'Comercial',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::ADMIN => 'admin',
            self::PROJECT_MANAGER => 'project_manager',
            self::DEVELOPER => 'developer',
            self::SALES => 'sales',
        };
    }

    /**
     * Papéis que entram pelo login. Todos são usuários internos da WCJ; o que cada um
     * vê é decidido pelas Policies.
     *
     * @return list<self>
     */
    public static function backoffice(): array
    {
        return self::cases();
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
