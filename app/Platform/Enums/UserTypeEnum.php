<?php

namespace App\Platform\Enums;

enum UserTypeEnum: int
{
    case ADMIN = 1;
    case SUPERVISOR = 2;
    case FINANCIAL = 3;

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::SUPERVISOR => 'Supervisor',
            self::FINANCIAL => 'Financeiro',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::ADMIN => 'admin',
            self::SUPERVISOR => 'supervisor',
            self::FINANCIAL => 'financial',
        };
    }

    /**
     * Types allowed to authenticate through the backoffice login.
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
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
