<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** B1 T20: a tela geral da auditoria é só do Administrador (Auditoria AC 7; F1 §09). */
class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{RoleEnum, bool}> */
    public static function papeis(): array
    {
        return [
            'administrador' => [RoleEnum::ADMIN, true],
            'gestor de projetos' => [RoleEnum::PROJECT_MANAGER, false],
            'desenvolvedor' => [RoleEnum::DEVELOPER, false],
            'comercial' => [RoleEnum::SALES, false],
        ];
    }

    #[DataProvider('papeis')]
    public function test_so_o_administrador_ve_a_auditoria(RoleEnum $papel, bool $ve): void
    {
        $actor = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);

        $this->assertSame($ve, $actor->can('viewAny', AuditLog::class));
    }
}
