<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\User;
use App\Platform\Services\UserService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\Concorrencia;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * B1 T39 (A3 da review de 07/10): a trava do último administrador (Usuários AC 14) é
 * check-then-act. Dois admins que se desativam ao mesmo tempo não podem deixar o Hub sem
 * administrador ativo. A corrida é forçada: a outra requisição roda inteira logo depois da
 * checagem da principal, noutra conexão (tests/Support/Concorrencia.php).
 */
class UltimoAdminConcorrenciaTest extends TestCase
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

    public function test_dois_admins_se_desativando_ao_mesmo_tempo_deixam_pelo_menos_um_ativo(): void
    {
        $ana = User::factory()->admin()->create(['name' => 'Ana Admin']);
        $bia = User::factory()->admin()->create(['name' => 'Bia Admin']);
        $users = app(UserService::class);

        // A checagem: a consulta aos administradores ativos.
        $corrida = Concorrencia::intercalarDepoisDe(
            fn (QueryExecuted $consulta) => str_contains($consulta->sql, 'from `users`')
                && str_contains($consulta->sql, '`role_id` = ?')
                && str_contains($consulta->sql, '`deactivated_at` is null'),
            fn () => $users->deactivate(User::query()->findOrFail($ana->id), $bia),
        );

        $users->deactivate($bia, $ana);

        $this->assertTrue($corrida->rodou, 'A checagem do último administrador não rodou.');
        $this->assertNull($corrida->outroErro);
        // A principal desativou a Bia; a Ana, que tentou se desativar no meio, continua.
        $this->assertSame(
            [$ana->id],
            User::query()->active()->where('role_id', RoleEnum::ADMIN->value)->pluck('id')->all(),
        );
        $this->assertTrue($corrida->bloqueada, 'A outra requisição passou pela checagem sem esperar a principal.');
    }
}
