<?php

namespace Tests\Feature\Nucleo;

use App\Mail\TemporaryPasswordMail;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * B1 T8: rotas de usuários sobre o UserService (Usuários AC 2, 4, 13, 15; edge case do e-mail
 * que falha; NUC-04). Feliz, 403 com "nada muda" para gestor e dev, erro de validação.
 */
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
    }

    /** @return array<string, mixed> */
    private function invitation(array $overrides = []): array
    {
        return ['role_id' => RoleEnum::DEVELOPER->value, 'name' => 'Maria Souza', 'email' => 'maria@example.com', 'daily_hours' => '8', ...$overrides];
    }

    private function login(User $user, string $at): void
    {
        AuditLog::query()->forceCreate([
            'user_id' => $user->id, 'action' => 'login', 'outcome' => 'success',
            'auditable_type' => 'user', 'auditable_id' => $user->id, 'created_at' => $at,
        ]);
    }

    // AC 13
    public function test_lista_traz_as_colunas_do_f1_com_ultimo_acesso_no_fuso_local_e_esconde_desativados(): void
    {
        $bia = User::factory()->developer()->create(['name' => 'Bia Dev', 'daily_hours' => '6.5']);
        $this->login($bia, '2026-10-01 10:00:00');
        $this->login($bia, '2026-10-05 02:30:00');
        $this->login($this->admin, '2026-10-06 12:00:00');
        $caio = User::factory()->projectManager()->create(['name' => 'Caio Gestor']);
        $this->actingAs($this->admin)->post("/admin/users/{$caio->id}/deactivate");

        $this->actingAs($this->admin)->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/index')
                ->has('users.data', 2)
                ->where('users.data.1.name', 'Bia Dev')
                ->where('users.data.1.email', $bia->email)
                ->where('users.data.1.role_label', 'Desenvolvedor')
                ->where('users.data.1.daily_hours', '6.50')
                ->where('users.data.1.ai_write_allowed', true)
                ->where('users.data.1.active', true)
                // Último login, 02:30 UTC = 23:30 do dia anterior em São Paulo.
                ->where('users.data.1.last_login_at', LocalTime::format(Carbon::parse('2026-10-05 02:30:00', 'UTC')))
                ->where('users.data.1.last_login_at', '04/10/2026 23:30')
                ->where('filters.show_inactive', false));

        $this->actingAs($this->admin)->get('/admin/users?show_inactive=1')
            ->assertInertia(fn ($page) => $page
                ->has('users.data', 3)
                ->where('users.data.2.name', 'Caio Gestor')
                ->where('users.data.2.active', false)
                ->where('users.data.2.last_login_at', null)
                ->where('users.data.2.deactivated_by', 'Ana Admin')
                ->where('filters.show_inactive', true));
    }

    // AC 1, 2: a senha provisória nunca aparece na tela.
    public function test_convidar_responde_sem_a_senha_e_grava_a_marca_da_ia(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', $this->invitation(['ai_write_allowed' => false]));

        $plain = null;
        Mail::assertSent(TemporaryPasswordMail::class, function (TemporaryPasswordMail $mail) use (&$plain) {
            $plain = $mail->temporaryPassword;

            return $mail->hasTo('maria@example.com');
        });
        $response->assertRedirect()->assertSessionHas('success', 'Convite enviado para maria@example.com. No primeiro acesso, a pessoa troca a senha.');
        $this->assertStringNotContainsString((string) $plain, (string) json_encode(session()->all()));
        $this->assertFalse(User::query()->where('email', 'maria@example.com')->sole()->ai_write_allowed);
    }

    // Edge case: e-mail que falha.
    public function test_convite_com_email_que_falha_mantem_o_usuario_e_avisa_para_reenviar(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP fora do ar'));

        $this->actingAs($this->admin)->post('/admin/users', $this->invitation())
            ->assertRedirect()
            ->assertSessionHas('error', 'Usuário criado; o e-mail não pôde ser enviado. Use Reenviar convite.');

        $this->assertSame(1, User::query()->where('email', 'maria@example.com')->count());
    }

    // AC 4
    public function test_desenvolvedor_sem_carga_e_recusado_no_campo_com_a_mensagem_da_spec(): void
    {
        $this->actingAs($this->admin)->from('/admin/users')
            ->post('/admin/users', $this->invitation(['daily_hours' => '']))
            ->assertSessionHasErrors(['daily_hours' => 'Carga diária é obrigatória para Desenvolvedor']);

        $this->assertSame(0, User::query()->where('email', 'maria@example.com')->count());
    }

    public function test_validacao_do_formulario(): void
    {
        $dev = User::factory()->developer()->create();

        $this->actingAs($this->admin)->from('/admin/users')
            ->put("/admin/users/{$dev->id}", ['role_id' => 9, 'name' => '', 'email' => 'não-é-email', 'daily_hours' => '25', 'ai_write_allowed' => 'talvez'])
            ->assertSessionHasErrors(['role_id', 'name', 'email', 'daily_hours', 'ai_write_allowed']);

        $this->actingAs($this->admin)->from('/admin/users')
            ->post('/admin/users', $this->invitation(['email' => $dev->email]))
            ->assertSessionHasErrors(['email' => 'Este e-mail já está em uso.']);
    }

    public function test_editar_troca_papel_carga_e_marca_da_ia(): void
    {
        $dev = User::factory()->developer()->create();

        $this->actingAs($this->admin)
            ->put("/admin/users/{$dev->id}", ['role_id' => RoleEnum::PROJECT_MANAGER->value, 'name' => 'Davi', 'email' => $dev->email, 'daily_hours' => '4', 'ai_write_allowed' => false])
            ->assertSessionHas('success', 'Usuário atualizado.');

        $dev->refresh();
        $this->assertTrue($dev->isProjectManager());
        $this->assertSame('4.00', $dev->daily_hours);
        $this->assertFalse($dev->ai_write_allowed);
    }

    public function test_desativar_e_reativar_pelas_rotas_proprias(): void
    {
        $dev = User::factory()->developer()->create(['name' => 'Davi Dev']);

        $this->actingAs($this->admin)->post("/admin/users/{$dev->id}/deactivate")
            ->assertRedirect()->assertSessionHas('success', 'Davi Dev perdeu o acesso.');
        $this->assertFalse($dev->fresh()->isActive());
        $this->assertSame($this->admin->id, $dev->fresh()->deactivated_by);

        $this->actingAs($this->admin)->post("/admin/users/{$dev->id}/reactivate")
            ->assertRedirect()->assertSessionHas('success', 'Davi Dev voltou a ter acesso.');
        $this->assertTrue($dev->fresh()->isActive());
    }

    // AC 12 (SPEC_DEVIATION: toast de erro em vez de 403; ver UserController::destroy)
    public function test_excluir_quem_ja_entrou_mostra_a_mensagem_e_mantem_a_linha(): void
    {
        $dev = User::factory()->developer()->create();
        $this->login($dev, '2026-10-01 10:00:00');

        $this->actingAs($this->admin)->delete("/admin/users/{$dev->id}")
            ->assertRedirect()->assertSessionHas('error', 'Usuário já usado: desative em vez de excluir');

        $this->assertModelExists($dev);
    }

    public function test_reenviar_convite_com_email_que_falha_avisa(): void
    {
        $dev = User::factory()->developer()->create();
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP fora do ar'));

        $this->actingAs($this->admin)->post("/admin/users/{$dev->id}/reset-password")
            ->assertRedirect()
            ->assertSessionHas('error', 'Senha provisória gerada; o e-mail não pôde ser enviado. Use Reenviar convite.');
        $this->assertAuthenticatedAs($this->admin);
    }

    /** @return array<string, array{RoleEnum}> */
    public static function semPermissao(): array
    {
        return ['gestor de projetos' => [RoleEnum::PROJECT_MANAGER], 'desenvolvedor' => [RoleEnum::DEVELOPER]];
    }

    // AC 15
    #[DataProvider('semPermissao')]
    public function test_gestor_e_dev_recebem_403_em_toda_escrita_e_nada_muda(RoleEnum $papel): void
    {
        $actor = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);
        $target = User::factory()->developer()->create(['name' => 'Alvo', 'password' => 'senha-do-alvo']);
        $inactive = User::factory()->developer()->inactive()->create();
        $snapshot = fn () => User::query()->orderBy('id')->get()->map->only(['id', 'name', 'email', 'role_id', 'daily_hours', 'ai_write_allowed', 'deactivated_at', 'password', 'must_change_password'])->all();
        $before = $snapshot();

        $attempts = [
            ['created', fn () => $this->post('/admin/users', $this->invitation())],
            ['updated', fn () => $this->put("/admin/users/{$target->id}", $this->invitation(['email' => $target->email, 'role_id' => RoleEnum::ADMIN->value]))],
            ['deactivated', fn () => $this->post("/admin/users/{$target->id}/deactivate")],
            ['reactivated', fn () => $this->post("/admin/users/{$inactive->id}/reactivate")],
            ['deleted', fn () => $this->delete("/admin/users/{$target->id}")],
            ['password_reset', fn () => $this->post("/admin/users/{$target->id}/reset-password")],
        ];

        foreach ($attempts as [$action, $attempt]) {
            $this->actingAs($actor);
            $attempt()->assertForbidden();
            $this->assertDatabaseHas('audit_logs', ['user_id' => $actor->id, 'action' => $action, 'outcome' => 'denied', 'auditable_type' => 'user']);
        }

        $this->assertEquals($before, $snapshot());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->count());
        Mail::assertNothingSent();
    }
}
