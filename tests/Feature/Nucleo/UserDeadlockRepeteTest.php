<?php

namespace Tests\Feature\Nucleo;

use App\Mail\TemporaryPasswordMail;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\UserService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PDOException;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * CR-3-03: as escritas de usuário repetem a transação em deadlock (1213). O deadlock de verdade
 * não se reproduz no harness; aqui ele é simulado na primeira gravação da auditoria, depois de o
 * alvo já ter sido salvo, e a prova é que a repetição grava o mesmo que uma tentativa só. Com
 * DatabaseTruncation: dentro da transação do RefreshDatabase o Laravel não repete.
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

    private function deadlockNaPrimeiraAuditoria(): void
    {
        $this->app->instance(AuditService::class, new class extends AuditService
        {
            private bool $falhou = false;

            public function log(AuditActionEnum $action, Model $auditable, ?array $before = null, ?array $after = null, ?int $userId = null, ?int $aiAuthorizationId = null): AuditLog
            {
                if (! $this->falhou) {
                    $this->falhou = true;

                    throw new QueryException('mysql', 'insert into `audit_logs`', [], new PDOException('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction'));
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
}
