<?php

namespace Tests\Feature\Nucleo;

use App\Mail\TemporaryPasswordMail;
use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AiAssistant;
use App\Platform\Models\AiAuthorization;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\SessionService;
use App\Platform\Services\UserService;
use Database\Seeders\AiAssistantSeeder;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

/**
 * B1 T7: UserService (Usuários AC 1–12, 14, 16; edge cases de convite simultâneo, e-mail que
 * falha e último administrador; NUC-01, NUC-02, NUC-03, NUC-13).
 */
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seed(AiAssistantSeeder::class);
        $this->service = app(UserService::class);
        $this->admin = User::factory()->admin()->create();
    }

    /** @return array<string, mixed> */
    private function invitation(array $overrides = []): array
    {
        return [
            'role_id' => RoleEnum::DEVELOPER->value,
            'name' => 'Maria Souza',
            'email' => 'maria@example.com',
            'daily_hours' => '7.5',
            ...$overrides,
        ];
    }

    private function sentPassword(string $email, bool $isReset): string
    {
        $plain = null;
        Mail::assertSent(TemporaryPasswordMail::class, function (TemporaryPasswordMail $mail) use ($email, $isReset, &$plain) {
            if ($mail->hasTo($email) && $mail->isReset === $isReset) {
                $plain = $mail->temporaryPassword;

                return true;
            }

            return false;
        });

        return (string) $plain;
    }

    /** @return array{0: AiAuthorization, 1: AiAuthorization, 2: AiAuthorization} duas ativas e uma já revogada */
    private function aiAuthorizations(User $user): array
    {
        $make = fn (int $assistant, ?string $revokedAt) => AiAuthorization::query()->create([
            'user_id' => $user->id,
            'ai_assistant_id' => $assistant,
            'oauth_client_id' => (string) Str::uuid(),
            'revoked_at' => $revokedAt,
        ]);

        return [$make(AiAssistant::CLAUDE, null), $make(AiAssistant::CODEX, null), $make(AiAssistant::CLAUDE, '2026-01-02 03:04:05')];
    }

    private function assertAudited(User $target, string $action, ?array $changes): AuditLog
    {
        $log = AuditLog::query()->where('auditable_type', 'user')->where('auditable_id', $target->id)
            ->where('action', $action)->latest('id')->firstOrFail();

        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(AuditLog::OUTCOME_SUCCESS, $log->outcome);
        $this->assertNull($log->ai_authorization_id);
        // Auditoria AC 1: o IP da requisição de teste.
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertSame($changes, $log->changes);

        return $log;
    }

    // AC 1, 2, 16
    public function test_convite_cria_com_troca_obrigatoria_e_manda_endereco_login_e_senha(): void
    {
        $result = $this->service->invite($this->invitation(), $this->admin);

        $this->assertTrue($result['mailed']);
        $maria = User::query()->where('email', 'maria@example.com')->sole();
        $this->assertTrue($result['user']->is($maria));
        $this->assertTrue($maria->must_change_password);
        $this->assertSame(RoleEnum::DEVELOPER->value, $maria->role_id);
        $this->assertSame('7.50', $maria->daily_hours);
        $this->assertTrue($maria->ai_write_allowed);
        $this->assertTrue($maria->isActive());

        $plain = $this->sentPassword('maria@example.com', isReset: false);
        $this->assertTrue(Hash::check($plain, $maria->password));

        $mail = new TemporaryPasswordMail($maria, $plain, false);
        foreach (['assertSeeInHtml', 'assertSeeInText'] as $assert) {
            $mail->$assert(route('login'), false);
            $mail->$assert('maria@example.com', false);
            $mail->$assert($plain, false);
        }
        $mail->assertSeeInText('Login: maria@example.com', false);

        $log = $this->assertAudited($maria, 'created', [
            'name' => [null, 'Maria Souza'],
            'email' => [null, 'maria@example.com'],
            'role_id' => [null, RoleEnum::DEVELOPER->value],
            'daily_hours' => [null, '7.50'],
            'ai_write_allowed' => [null, true],
        ]);
        // AC 2: nem a chave nem o valor da senha chegam à auditoria.
        $this->assertStringNotContainsString($plain, (string) json_encode(AuditLog::query()->pluck('changes')));
        $this->assertArrayNotHasKey('password', $log->changes);
    }

    public function test_convite_respeita_a_marca_da_ia_desligada(): void
    {
        $user = $this->service->invite($this->invitation(['ai_write_allowed' => false]), $this->admin)['user'];

        $this->assertFalse($user->fresh()->ai_write_allowed);
    }

    // AC 3
    public function test_reenviar_convite_gera_senha_nova_e_invalida_a_anterior(): void
    {
        $joao = User::factory()->developer()->create(['email' => 'joao@example.com', 'password' => 'antiga-senha', 'must_change_password' => false]);

        $this->assertTrue($this->service->resendInvite($joao, $this->admin));

        $joao->refresh();
        $plain = $this->sentPassword('joao@example.com', isReset: true);
        $this->assertTrue(Hash::check($plain, $joao->password));
        $this->assertFalse(Hash::check('antiga-senha', $joao->password));
        $this->assertTrue($joao->must_change_password);
        $this->assertAudited($joao, 'password_reset', ['must_change_password' => [null, true]]);
        $this->assertStringNotContainsString($plain, (string) json_encode(AuditLog::query()->pluck('changes')));
    }

    // AC 4
    public function test_desenvolvedor_sem_carga_diaria_e_recusado_sem_criar_nem_alterar(): void
    {
        foreach ([null, ''] as $empty) {
            try {
                $this->service->invite($this->invitation(['daily_hours' => $empty]), $this->admin);
                $this->fail('Convite de dev sem carga passou.');
            } catch (ValidationException $e) {
                $this->assertSame(['daily_hours' => ['Carga diária é obrigatória para Desenvolvedor']], $e->errors());
            }
        }
        $this->assertSame(0, User::query()->where('email', 'maria@example.com')->count());

        $manager = User::factory()->projectManager()->create(['name' => 'Gil Gestor']);
        try {
            $this->service->update($manager, ['role_id' => RoleEnum::DEVELOPER->value, 'name' => 'Gil Dev', 'email' => $manager->email], $this->admin);
            $this->fail('Troca para dev sem carga passou.');
        } catch (ValidationException $e) {
            $this->assertSame(['daily_hours' => ['Carga diária é obrigatória para Desenvolvedor']], $e->errors());
        }
        $manager->refresh();
        $this->assertSame('Gil Gestor', $manager->name);
        $this->assertSame(RoleEnum::PROJECT_MANAGER->value, $manager->role_id);
        $this->assertSame(0, AuditLog::query()->where('action', 'created')->orWhere('action', 'updated')->count());
    }

    public function test_carga_vazia_vale_para_quem_nao_e_desenvolvedor(): void
    {
        $user = $this->service->invite($this->invitation(['role_id' => RoleEnum::PROJECT_MANAGER->value, 'daily_hours' => null]), $this->admin)['user'];

        $this->assertNull($user->fresh()->daily_hours);
    }

    // AC 5 (a validação do campo é do FormRequest, na rota que o Service atende)
    public function test_carga_fora_de_0_a_24_e_erro_no_campo(): void
    {
        foreach (['0', '-1', '24.01'] as $hours) {
            $this->actingAs($this->admin)->from('/admin/users')
                ->post('/admin/users', $this->invitation(['daily_hours' => $hours]))
                ->assertSessionHasErrors('daily_hours');
        }
        $this->assertSame(0, User::query()->where('email', 'maria@example.com')->count());

        $this->actingAs($this->admin)->post('/admin/users', $this->invitation(['daily_hours' => '24']))->assertSessionHasNoErrors();
        $this->assertSame('24.00', User::query()->where('email', 'maria@example.com')->sole()->daily_hours);
    }

    // AC 6, 16
    public function test_trocar_o_papel_revoga_as_conexoes_de_ia_ativas(): void
    {
        $dev = User::factory()->developer()->create();
        [$claude, $codex, $old] = $this->aiAuthorizations($dev);
        $other = User::factory()->developer()->create();
        [$othersClaude] = $this->aiAuthorizations($other);

        $this->service->update($dev, ['role_id' => RoleEnum::PROJECT_MANAGER->value, 'name' => $dev->name, 'email' => $dev->email, 'daily_hours' => '8'], $this->admin);

        $this->assertSame(RoleEnum::PROJECT_MANAGER->value, $dev->fresh()->role_id);
        $this->assertNotNull($claude->fresh()->revoked_at);
        $this->assertNotNull($codex->fresh()->revoked_at);
        $this->assertSame('2026-01-02 03:04:05', $old->fresh()->revoked_at->toDateTimeString());
        $this->assertNull($othersClaude->fresh()->revoked_at);
        $this->assertAudited($dev, 'updated', ['role_id' => [RoleEnum::DEVELOPER->value, RoleEnum::PROJECT_MANAGER->value]]);
        // banco.txt (ai_authorizations): quem revogou fica na auditoria.
        $this->assertSame(2, AuditLog::query()->where('auditable_type', 'ai_authorization')->where('user_id', $this->admin->id)->count());
    }

    // AC 7
    public function test_trocar_a_marca_ia_pode_escrever_revoga_e_mudar_so_o_nome_nao(): void
    {
        // Lido do banco, como chega pela rota: com o padrão da coluna (true).
        $dev = User::factory()->developer()->create()->fresh();
        [$claude, $codex] = $this->aiAuthorizations($dev);
        $payload = ['role_id' => RoleEnum::DEVELOPER->value, 'name' => 'Outro Nome', 'email' => $dev->email, 'daily_hours' => '8'];

        $this->service->update($dev, $payload, $this->admin);
        $this->assertNull($claude->fresh()->revoked_at);

        $this->service->update($dev, [...$payload, 'ai_write_allowed' => false], $this->admin);

        $this->assertFalse($dev->fresh()->ai_write_allowed);
        $this->assertNotNull($claude->fresh()->revoked_at);
        $this->assertNotNull($codex->fresh()->revoked_at);
        $this->assertAudited($dev, 'updated', ['ai_write_allowed' => [true, false]]);
    }

    // AC 8, 9, 16
    public function test_desativar_grava_quem_e_quando_derruba_sessoes_e_conexoes_e_barra_o_login(): void
    {
        config(['session.driver' => 'database']);
        $dev = User::factory()->developer()->create(['email' => 'dev@example.com', 'password' => 'password']);
        [$claude, $codex] = $this->aiAuthorizations($dev);
        DB::table('sessions')->insert([
            ['id' => 'notebook', 'user_id' => $dev->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'celular', 'user_id' => $dev->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'de-outra-pessoa', 'user_id' => $this->admin->id, 'payload' => '', 'last_activity' => time()],
        ]);
        $this->freezeSecond();

        $this->service->deactivate($dev, $this->admin);

        $dev->refresh();
        $this->assertFalse($dev->isActive());
        $this->assertSame(now()->toDateTimeString(), $dev->deactivated_at->toDateTimeString());
        $this->assertSame($this->admin->id, $dev->deactivated_by);
        $this->assertDatabaseMissing('sessions', ['user_id' => $dev->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'de-outra-pessoa']);
        $this->assertNotNull($claude->fresh()->revoked_at);
        $this->assertNotNull($codex->fresh()->revoked_at);
        $this->assertAudited($dev, 'deactivated', ['active' => [true, false]]);

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'dev@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => AuthenticatedSessionController::INVALID_CREDENTIALS]);
        $this->assertGuest();
    }

    /**
     * O UserService passa a usar um AuditService que falha ao auditar a revogação de uma conexão
     * de IA: a falha cai depois de o usuário, a primeira conexão e a auditoria do usuário já
     * terem sido gravados, o pior ponto para uma escrita sem transação.
     */
    private function failWhenAuditingAiRevocation(): void
    {
        $this->app->instance(AuditService::class, new class extends AuditService
        {
            public function log(AuditActionEnum $action, Model $auditable, ?array $before = null, ?array $after = null, ?int $userId = null, ?int $aiAuthorizationId = null): AuditLog
            {
                if ($auditable instanceof AiAuthorization) {
                    throw new RuntimeException('falha simulada na auditoria da revogação');
                }

                return parent::log($action, $auditable, $before, $after, $userId, $aiAuthorizationId);
            }
        });
        $this->service = app(UserService::class);
    }

    /** Dev com duas conexões de IA ativas e duas sessões, e o estado dele para comparar depois. */
    private function devWithSessionsAndAi(): array
    {
        config(['session.driver' => 'database']);
        $dev = User::factory()->developer()->create()->fresh();
        [$claude, $codex] = $this->aiAuthorizations($dev);
        DB::table('sessions')->insert([
            ['id' => 'notebook', 'user_id' => $dev->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'celular', 'user_id' => $dev->id, 'payload' => '', 'last_activity' => time()],
        ]);

        return [$dev, $claude, $codex];
    }

    private function assertUntouched(User $dev, AiAuthorization ...$authorizations): void
    {
        $fresh = $dev->fresh();
        $this->assertNull($fresh->deactivated_at);
        $this->assertNull($fresh->deactivated_by);
        $this->assertSame(RoleEnum::DEVELOPER->value, $fresh->role_id);
        $this->assertTrue($fresh->ai_write_allowed);
        $this->assertSame(['celular', 'notebook'], DB::table('sessions')->where('user_id', $dev->id)->orderBy('id')->pluck('id')->all());
        foreach ($authorizations as $authorization) {
            $this->assertNull($authorization->fresh()->revoked_at);
        }
        $this->assertSame(0, AuditLog::query()->count());
    }

    // AC 8: "na mesma transação"
    public function test_desativar_que_falha_na_revogacao_da_ia_desfaz_tudo(): void
    {
        [$dev, $claude, $codex] = $this->devWithSessionsAndAi();
        $this->failWhenAuditingAiRevocation();

        $this->assertThrows(fn () => $this->service->deactivate($dev, $this->admin), RuntimeException::class);

        $this->assertUntouched($dev, $claude, $codex);
    }

    // AC 8: a última escrita (encerrar as sessões) também volta.
    public function test_desativar_que_falha_depois_de_encerrar_as_sessoes_desfaz_tudo(): void
    {
        [$dev, $claude, $codex] = $this->devWithSessionsAndAi();
        $this->app->instance(SessionService::class, new class extends SessionService
        {
            public function terminateOthers(User $user, ?string $keepSessionId = null): int
            {
                parent::terminateOthers($user, $keepSessionId);

                throw new RuntimeException('falha simulada depois de encerrar as sessões');
            }
        });
        $this->service = app(UserService::class);

        $this->assertThrows(fn () => $this->service->deactivate($dev, $this->admin), RuntimeException::class);

        $this->assertUntouched($dev, $claude, $codex);
    }

    // AC 6: "na mesma transação"
    public function test_trocar_o_papel_que_falha_na_revogacao_da_ia_desfaz_tudo(): void
    {
        [$dev, $claude, $codex] = $this->devWithSessionsAndAi();
        $this->failWhenAuditingAiRevocation();

        $this->assertThrows(fn () => $this->service->update($dev, [
            'role_id' => RoleEnum::PROJECT_MANAGER->value, 'name' => $dev->name, 'email' => $dev->email, 'daily_hours' => '8',
        ], $this->admin), RuntimeException::class);

        $this->assertUntouched($dev, $claude, $codex);
    }

    // AC 7
    public function test_trocar_a_marca_ia_pode_escrever_que_falha_na_revogacao_desfaz_tudo(): void
    {
        [$dev, $claude, $codex] = $this->devWithSessionsAndAi();
        $this->failWhenAuditingAiRevocation();

        $this->assertThrows(fn () => $this->service->update($dev, [
            'role_id' => RoleEnum::DEVELOPER->value, 'name' => $dev->name, 'email' => $dev->email, 'daily_hours' => '8', 'ai_write_allowed' => false,
        ], $this->admin), RuntimeException::class);

        $this->assertUntouched($dev, $claude, $codex);
    }

    public function test_desativar_quem_ja_esta_desativado_mantem_o_carimbo_original(): void
    {
        $dev = User::factory()->developer()->create();
        $this->service->deactivate($dev, $this->admin);
        $stamp = $dev->fresh()->deactivated_at;
        $this->travel(1)->hour();

        $this->service->deactivate($dev->fresh(), User::factory()->admin()->create());

        $this->assertEquals($stamp, $dev->fresh()->deactivated_at);
        $this->assertSame($this->admin->id, $dev->fresh()->deactivated_by);
        $this->assertSame(1, AuditLog::query()->where('action', 'deactivated')->count());
    }

    // AC 10
    public function test_reativar_limpa_quem_e_quando_sem_devolver_as_conexoes_de_ia(): void
    {
        $dev = User::factory()->developer()->create();
        [$claude] = $this->aiAuthorizations($dev);
        $this->service->deactivate($dev, $this->admin);

        $this->service->reactivate($dev->fresh(), $this->admin);

        $this->assertDatabaseHas('users', ['id' => $dev->id, 'deactivated_at' => null, 'deactivated_by' => null]);
        $this->assertNotNull($claude->fresh()->revoked_at);
        $this->assertSame(0, AiAuthorization::query()->where('user_id', $dev->id)->whereNull('revoked_at')->count());
        $this->assertAudited($dev, 'reactivated', ['active' => [false, true]]);
    }

    // AC 11
    public function test_excluir_quem_nunca_entrou_apaga_e_audita(): void
    {
        $user = $this->service->invite($this->invitation(), $this->admin)['user'];

        $this->service->delete($user, $this->admin);

        $this->assertModelMissing($user);
        $this->assertAudited($user, 'deleted', [
            'name' => ['Maria Souza', null],
            'email' => ['maria@example.com', null],
            'role_id' => [RoleEnum::DEVELOPER->value, null],
            'daily_hours' => ['7.50', null],
            'ai_write_allowed' => [true, null],
        ]);
    }

    // AC 12
    public function test_excluir_quem_tem_historico_e_recusado_e_a_linha_fica(): void
    {
        $signedIn = User::factory()->developer()->create();
        AuditLog::query()->create(['user_id' => $signedIn->id, 'action' => 'login', 'outcome' => 'success', 'auditable_type' => 'user', 'auditable_id' => $signedIn->id]);
        $connected = User::factory()->developer()->create();
        $this->aiAuthorizations($connected);

        foreach ([$signedIn, $connected] as $user) {
            try {
                $this->service->delete($user, $this->admin);
                $this->fail('Excluiu usuário com histórico.');
            } catch (DomainException $e) {
                $this->assertSame('Usuário já usado: desative em vez de excluir', $e->getMessage());
            }
            $this->assertModelExists($user);
        }
        $this->assertSame(0, AuditLog::query()->where('action', 'deleted')->count());
    }

    // AC 14 e edge case do último administrador
    public function test_ultimo_administrador_ativo_nao_e_rebaixado_desativado_nem_excluido(): void
    {
        $payload = ['role_id' => RoleEnum::PROJECT_MANAGER->value, 'name' => $this->admin->name, 'email' => $this->admin->email];
        $refusals = [
            'Este é o último administrador ativo. Promova outra pessoa antes de alterar o papel.' => fn () => $this->service->update($this->admin, $payload, $this->admin),
            'Este é o último administrador ativo. Promova outra pessoa antes de desativar.' => fn () => $this->service->deactivate($this->admin, $this->admin),
            'Este é o último administrador ativo. Promova outra pessoa antes de excluir.' => fn () => $this->service->delete($this->admin, $this->admin),
        ];

        foreach ($refusals as $message => $attempt) {
            try {
                $attempt();
                $this->fail("Passou: {$message}");
            } catch (DomainException $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }

        $this->admin->refresh();
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->admin->isActive());
        $this->assertSame(0, AuditLog::query()->count());

        // A mesma pessoa, com outro administrador ativo, edita a si mesma pelas regras de sempre.
        User::factory()->admin()->create();
        $this->service->update($this->admin, [...$payload, 'daily_hours' => '6'], $this->admin);
        $this->assertTrue($this->admin->fresh()->isProjectManager());
        $this->assertSame('6.00', $this->admin->fresh()->daily_hours);
    }

    public function test_administrador_que_se_rebaixa_a_desenvolvedor_tambem_precisa_de_carga(): void
    {
        User::factory()->admin()->create();

        $this->expectExceptionObject(ValidationException::withMessages(['daily_hours' => 'Carga diária é obrigatória para Desenvolvedor']));
        try {
            $this->service->update($this->admin, ['role_id' => RoleEnum::DEVELOPER->value, 'name' => $this->admin->name, 'email' => $this->admin->email], $this->admin);
        } finally {
            $this->assertTrue($this->admin->fresh()->isAdmin());
        }
    }

    // Edge case: dois administradores convidam o mesmo e-mail ao mesmo tempo.
    public function test_convite_simultaneo_do_mesmo_email_cria_um_so_e_o_segundo_recebe_erro_no_email(): void
    {
        // O primeiro convite já gravou; o segundo passou pela validação antes do commit dele.
        $this->service->invite($this->invitation(), $this->admin);
        Mail::fake();

        try {
            $this->service->invite($this->invitation(['name' => 'Maria Repetida']), User::factory()->admin()->create());
            $this->fail('O segundo convite passou.');
        } catch (ValidationException $e) {
            $this->assertSame(['email' => ['Este e-mail já está em uso.']], $e->errors());
        }

        $this->assertSame('Maria Souza', User::query()->where('email', 'maria@example.com')->sole()->name);
        $this->assertSame(1, AuditLog::query()->where('action', 'created')->count());
        Mail::assertNothingSent();
    }

    // Edge case: o e-mail do convite falha.
    public function test_email_que_falha_mantem_o_usuario_e_vai_para_o_log(): void
    {
        Log::spy();
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP fora do ar'));

        $result = $this->service->invite($this->invitation(), $this->admin);

        $this->assertFalse($result['mailed']);
        $maria = User::query()->where('email', 'maria@example.com')->sole();
        $this->assertTrue($maria->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'user', 'auditable_id' => $maria->id, 'action' => 'created', 'user_id' => $this->admin->id]);
        Log::shouldHaveReceived('error')->once()->withArgs(
            fn (string $message, array $context) => $context === ['user_id' => $maria->id, 'erro' => 'SMTP fora do ar'],
        );

        $this->assertFalse($this->service->resendInvite($maria, $this->admin));
    }

    // NUC-13 (Lista de escolha AC 1)
    public function test_lista_de_escolha_traz_so_ativos_com_id_nome_e_papel(): void
    {
        $ana = User::factory()->projectManager()->create(['name' => 'Ana Gestora']);
        User::factory()->developer()->inactive()->create(['name' => 'Bia Desativada']);
        $caio = User::factory()->developer()->create(['name' => 'Caio Dev']);

        $list = collect($this->service->choiceList($this->admin))->keyBy('id');

        $this->assertEqualsCanonicalizing([$this->admin->id, $ana->id, $caio->id], $list->keys()->all());
        $this->assertSame(['id' => $caio->id, 'name' => 'Caio Dev', 'role' => 'developer'], $list[$caio->id]);
        $this->assertSame(['id' => $ana->id, 'name' => 'Ana Gestora', 'role' => 'project_manager'], $list[$ana->id]);
    }

    // CR-1-02 do ciclo 1: o e-mail de outro (gravado depois da validação do FormRequest, ou por
    // quem chama o Service direto) volta como erro do campo, não como 500.
    public function test_editar_para_o_email_de_outro_e_recusado_no_campo_sem_gravar(): void
    {
        User::factory()->developer()->create(['email' => 'ana@example.com']);
        $bia = User::factory()->developer()->create(['name' => 'Bia', 'email' => 'bia@example.com', 'daily_hours' => 8]);

        try {
            $this->service->update($bia, ['role_id' => RoleEnum::DEVELOPER->value, 'name' => 'Bia', 'email' => 'ana@example.com', 'daily_hours' => 8], $this->admin);
            $this->fail('Gravou o e-mail repetido.');
        } catch (ValidationException $e) {
            $this->assertSame(['email' => ['Este e-mail já está em uso.']], $e->errors());
        }

        $this->assertSame('bia@example.com', $bia->fresh()->email);
        $this->assertSame(0, AuditLog::query()->where('action', 'updated')->count());
    }

    // CR-1-05 do ciclo 1, NUC-13 (Lista de escolha AC 3): a Policy vale dentro do Service.
    public function test_desenvolvedor_e_recusado_na_lista_de_escolha_e_a_negacao_e_auditada(): void
    {
        $dev = User::factory()->developer()->create();

        try {
            $this->service->choiceList($dev);
            $this->fail('Desenvolvedor recebeu a lista de escolha.');
        } catch (AuthorizationException) {
        }

        $log = AuditLog::query()->sole();
        $this->assertSame(
            [$dev->id, 'viewed', AuditLog::OUTCOME_DENIED, 'user', null],
            [$log->user_id, $log->action, $log->outcome, $log->auditable_type, $log->auditable_id],
        );
    }

    public function test_gestor_recebe_a_lista_de_escolha_sem_negacao_auditada(): void
    {
        $manager = User::factory()->projectManager()->create(['name' => 'Ana Gestora']);
        User::factory()->developer()->inactive()->create(['name' => 'Bia Desativada']);

        $ids = array_column($this->service->choiceList($manager), 'id');

        $this->assertEqualsCanonicalizing([$this->admin->id, $manager->id], $ids);
        $this->assertSame(0, AuditLog::query()->count());
    }
}
