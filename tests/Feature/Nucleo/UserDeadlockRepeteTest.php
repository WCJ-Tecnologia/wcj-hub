<?php

namespace Tests\Feature\Nucleo;

use App\Mail\TemporaryPasswordMail;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\UserService;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PDOException;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * CR-3-03: as escritas de usuário repetem a transação em deadlock (1213). O deadlock de verdade
 * não se reproduz no harness; aqui ele é simulado na primeira gravação da auditoria, depois de o
 * alvo já ter sido salvo, e a prova é que a repetição grava o mesmo que uma tentativa só. O lock
 * wait timeout (1205) não repete (CR-4-04). Com DatabaseTruncation: dentro de uma transação aberta
 * por fora (a do RefreshDatabase) a escrita não repete.
 */
class UserDeadlockRepeteTest extends TestCase
{
    use DatabaseTruncation, UsaBancoDeVerdade;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        parent::tearDown();
    }

    /** Quantas vezes a auditoria foi chamada: uma por tentativa da transação. */
    private int $tentativas = 0;

    private function deadlockNaPrimeiraAuditoria(): void
    {
        $this->falhaNaPrimeiraAuditoria('40001', 1213, 'Deadlock found when trying to get lock; try restarting transaction');
    }

    // O erro como o PDO do MySQL entrega: código no errorInfo, que é o que a repetição confere.
    private function falhaNaPrimeiraAuditoria(string $sqlstate, int $codigo, string $mensagem): void
    {
        $erro = new PDOException("SQLSTATE[{$sqlstate}]: {$codigo} {$mensagem}");
        $erro->errorInfo = [$sqlstate, $codigo, $mensagem];
        $tentativas = &$this->tentativas;

        $this->app->instance(AuditService::class, new class($erro, $tentativas) extends AuditService
        {
            public function __construct(private readonly PDOException $erro, private int &$tentativas) {}

            public function log(AuditActionEnum $action, Model $auditable, ?array $before = null, ?array $after = null, ?int $userId = null, ?int $aiAuthorizationId = null): AuditLog
            {
                if ($this->tentativas++ === 0) {
                    throw new QueryException('mysql', 'insert into `audit_logs`', [], $this->erro);
                }

                return parent::log($action, $auditable, $before, $after, $userId, $aiAuthorizationId);
            }
        });
    }

    public function test_editar_repetido_depois_do_deadlock_grava_e_audita_a_mudanca(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['name' => 'Maria Souza', 'daily_hours' => '8']);
        $this->deadlockNaPrimeiraAuditoria();

        app(UserService::class)->update($dev, ['role_id' => RoleEnum::DEVELOPER->value, 'name' => 'Maria Lima', 'email' => $dev->email, 'daily_hours' => '8'], $admin);

        $this->assertSame(2, $this->tentativas);
        $this->assertSame('Maria Lima', $dev->fresh()->name);
        $this->assertSame(['name' => ['Maria Souza', 'Maria Lima']], AuditLog::query()->where('action', 'updated')->sole()->changes);
    }

    public function test_reenviar_convite_repetido_depois_do_deadlock_manda_a_senha_que_ficou_no_banco(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['must_change_password' => false]);
        $this->deadlockNaPrimeiraAuditoria();

        app(UserService::class)->resendInvite($dev, $admin);

        // CR-4-01: a tentativa desfeita já tinha sincronizado a flag na memória; a repetição
        // precisa gravá-la de novo, senão a senha provisória vira definitiva.
        $this->assertTrue($dev->fresh()->must_change_password);
        $this->assertSame(['must_change_password' => [false, true]], AuditLog::query()->where('action', 'password_reset')->sole()->changes);
        Mail::assertSent(TemporaryPasswordMail::class, 1);
        Mail::assertSent(TemporaryPasswordMail::class, fn (TemporaryPasswordMail $mail) => Hash::check($mail->temporaryPassword, $dev->fresh()->password));
    }

    // O deadlock dentro do deleteIfUnused (transação aninhada) sobe como DeadlockException, com o
    // 1213 dentro: a transação de fora repete inteira.
    public function test_excluir_repetido_depois_do_deadlock_na_transacao_aninhada_exclui(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create();
        $this->deadlockNaPrimeiraAuditoria();

        app(UserService::class)->delete($dev, $admin);

        $this->assertSame(2, $this->tentativas);
        $this->assertModelMissing($dev);
        $this->assertSame(1, AuditLog::query()->where('action', 'deleted')->count());
    }

    // CR-4-04: o "Lock wait timeout" (1205) não repete. Repetido, seriam até 3 × 50 s segurando
    // as travas, e a escrita podia commitar depois de a tela já ter recebido erro.
    public function test_lock_wait_timeout_nao_repete_e_sobe_na_primeira_tentativa(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['must_change_password' => false]);
        $this->falhaNaPrimeiraAuditoria('HY000', 1205, 'Lock wait timeout exceeded; try restarting transaction');

        try {
            app(UserService::class)->resendInvite($dev, $admin);
            $this->fail('O lock wait timeout não subiu.');
        } catch (QueryException $e) {
            $this->assertSame(1205, $e->errorInfo[1]);
        }

        $this->assertSame(1, $this->tentativas);
        $this->assertFalse($dev->fresh()->must_change_password);
        $this->assertSame(0, AuditLog::query()->where('action', 'password_reset')->count());
        Mail::assertNothingSent();
    }

    // Transação aberta por quem chamou (o B8 pode fazer isso): o deadlock já desfez a de fora
    // inteira, então repetir só a de dentro gravaria em cima de nada. Sobe na primeira tentativa;
    // quem abriu a transação é que repete (rodada 10 do Verifier, mutante g2).
    public function test_dentro_de_transacao_aberta_por_fora_nao_repete_e_sobe(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['name' => 'Maria Souza', 'daily_hours' => '8']);
        $this->deadlockNaPrimeiraAuditoria();

        try {
            DB::transaction(fn () => app(UserService::class)->update($dev, ['role_id' => RoleEnum::DEVELOPER->value, 'name' => 'Maria Lima', 'email' => $dev->email, 'daily_hours' => '8'], $admin));
            $this->fail('O deadlock não subiu.');
        } catch (PDOException $e) {
            // Aninhado, o Laravel o embrulha em DeadlockException (PDOException), com o erro do MySQL dentro.
            $this->assertSame(1213, ($e instanceof DeadlockException ? $e->getPrevious() : $e)->errorInfo[1]);
        }

        $this->assertSame(1, $this->tentativas);
        $this->assertSame('Maria Souza', $dev->fresh()->name);
        $this->assertSame(0, AuditLog::query()->where('action', 'updated')->count());
    }
}
