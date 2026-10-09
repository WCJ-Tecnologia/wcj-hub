<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B1 T23: as abilities que o front usa para montar o menu Administração (F1 §09; NUC-14). O
 * Administrador vê Usuários, Clientes, Contatos e Auditoria; o gestor, Clientes e Contatos; o dev
 * e o comercial, nenhum. Projetos (F2 §09, B2): todos menos o comercial veem a lista; admin e
 * gestor criam. As abilities saem das Policies, que continuam decidindo cada rota.
 */
class AbilitiesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{RoleEnum, array<string, bool>}> */
    public static function papeis(): array
    {
        return [
            'administrador' => [RoleEnum::ADMIN, [
                'manage_users' => true, 'delete_records' => true, 'view_audit_logs' => true,
                'reset_passwords' => true, 'view_clients' => true, 'view_contacts' => true,
                'view_projects' => true, 'manage_projects' => true,
            ]],
            'gestor de projetos' => [RoleEnum::PROJECT_MANAGER, [
                'manage_users' => false, 'delete_records' => false, 'view_audit_logs' => false,
                'reset_passwords' => false, 'view_clients' => true, 'view_contacts' => true,
                'view_projects' => true, 'manage_projects' => true,
            ]],
            'desenvolvedor' => [RoleEnum::DEVELOPER, [
                'manage_users' => false, 'delete_records' => false, 'view_audit_logs' => false,
                'reset_passwords' => false, 'view_clients' => false, 'view_contacts' => false,
                'view_projects' => true, 'manage_projects' => false,
            ]],
            'comercial' => [RoleEnum::SALES, [
                'manage_users' => false, 'delete_records' => false, 'view_audit_logs' => false,
                'reset_passwords' => false, 'view_clients' => false, 'view_contacts' => false,
                'view_projects' => false, 'manage_projects' => false,
            ]],
        ];
    }

    #[DataProvider('papeis')]
    public function test_abilities_do_menu_por_papel(RoleEnum $papel, array $esperadas): void
    {
        $user = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);

        $this->actingAs($user)->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auth.user.abilities', $esperadas));
    }

    /** O menu só esconde: cada item que some para o papel também responde 403 na rota. */
    #[DataProvider('papeis')]
    public function test_item_escondido_no_menu_tambem_e_negado_na_rota(RoleEnum $papel, array $esperadas): void
    {
        $user = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);
        $rotas = [
            'manage_users' => '/admin/users',
            'view_clients' => '/admin/clients',
            'view_contacts' => '/admin/contacts',
            'view_audit_logs' => '/admin/audit-logs',
            'view_projects' => '/projetos',
        ];

        foreach ($rotas as $ability => $rota) {
            $this->actingAs($user)->get($rota)->assertStatus($esperadas[$ability] ? 200 : 403);
        }
    }
}
