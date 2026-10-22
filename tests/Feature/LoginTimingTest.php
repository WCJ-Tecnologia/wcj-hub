<?php

namespace Tests\Feature;

use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use App\Platform\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Todo login recusado leva o mesmo tempo mínimo, o do Timebox do Laravel, e a conta desativada
 * com a senha certa não chega a logar (seguranca.md §3; revisão dirigida, achados 2 e 4; B9,
 * SEC-06 e SEC-22). Só o piso do tempo é conferido: o teto varia com a máquina.
 */
class LoginTimingTest extends TestCase
{
    use RefreshDatabase;

    private const TIMEBOX_MS = 300;

    protected function setUp(): void
    {
        parent::setUp();

        // Antes do primeiro uso do guard: ele lê a duração quando é criado.
        config(['auth.timebox_duration' => self::TIMEBOX_MS * 1000]);
        User::factory()->projectManager()->create(['email' => 'existe@exemplo.wcj.test', 'password' => 'senha-certa-1']);
        User::factory()->projectManager()->inactive()->create(['email' => 'inativo@exemplo.wcj.test', 'password' => 'senha-certa-1']);
    }

    public function test_the_production_timebox_is_500_ms_by_default(): void
    {
        $config = require config_path('auth.php');

        $this->assertSame(500_000, $config['timebox_duration']);
    }

    public function test_every_refused_login_waits_for_the_timebox_with_the_same_message(): void
    {
        $refused = [
            'sem conta' => ['ninguem@exemplo.wcj.test', 'senha-certa-1'],
            'senha errada' => ['existe@exemplo.wcj.test', 'senha-errada-1'],
            'conta desativada' => ['inativo@exemplo.wcj.test', 'senha-certa-1'],
        ];

        foreach ($refused as $case => [$email, $password]) {
            $start = microtime(true);
            $this->post('/admin/login', ['email' => $email, 'password' => $password])
                ->assertSessionHasErrors(['email' => AuthenticatedSessionController::INVALID_CREDENTIALS]);
            $elapsedMs = (microtime(true) - $start) * 1000;

            $this->assertGreaterThanOrEqual(self::TIMEBOX_MS, $elapsedMs, "login recusado ({$case}) respondeu antes do Timebox");
            $this->assertGuest();
        }
    }

    /**
     * O id da sessão troca no SessionGuard::login() (updateSession → migrate), que dispara o
     * evento Login. Conta recusada que não dispara Login não trocou a sessão.
     */
    public function test_a_deactivated_account_with_the_right_password_never_logs_in(): void
    {
        Event::fake([Login::class]);

        $this->post('/admin/login', ['email' => 'inativo@exemplo.wcj.test', 'password' => 'senha-certa-1'])
            ->assertSessionHasErrors(['email' => AuthenticatedSessionController::INVALID_CREDENTIALS]);

        Event::assertNotDispatched(Login::class);
        $this->assertGuest();
    }
}
