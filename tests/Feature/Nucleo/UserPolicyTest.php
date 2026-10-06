<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B1 T6: matriz ability × papel da UserPolicy (F1 §09; Usuários AC 15, NUC-04, NUC-13).
 * Usuários é só do Administrador; a lista de escolha também é do gestor.
 */
class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const SOBRE_OUTRO = ['view', 'update', 'deactivate', 'reactivate', 'delete', 'resendInvite'];

    private const SEM_REGISTRO = ['viewAny', 'create'];

    /** @return array<string, array{RoleEnum, bool, bool}> papel, pode gerir usuários, pode ver a lista de escolha */
    public static function papeis(): array
    {
        return [
            'administrador' => [RoleEnum::ADMIN, true, true],
            'gestor de projetos' => [RoleEnum::PROJECT_MANAGER, false, true],
            'desenvolvedor' => [RoleEnum::DEVELOPER, false, false],
            'comercial' => [RoleEnum::SALES, false, false],
        ];
    }

    #[DataProvider('papeis')]
    public function test_matriz_de_abilities_por_papel(RoleEnum $papel, bool $gere, bool $escolhe): void
    {
        $actor = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);
        $target = User::factory()->developer()->create();

        foreach (self::SEM_REGISTRO as $ability) {
            $this->assertSame($gere, $actor->can($ability, User::class), "{$papel->slug()} {$ability}");
        }

        foreach (self::SOBRE_OUTRO as $ability) {
            $this->assertSame($gere, $actor->can($ability, $target), "{$papel->slug()} {$ability}");
        }

        $this->assertSame($escolhe, $actor->can('viewChoiceList', User::class), "{$papel->slug()} viewChoiceList");
    }

    public function test_administrador_nao_exclui_a_si_mesmo_mas_desativa_e_edita(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('delete', $admin));
        $this->assertTrue($admin->can('update', $admin));
        $this->assertTrue($admin->can('deactivate', $admin));
        $this->assertTrue($admin->can('resendInvite', $admin));
    }
}
