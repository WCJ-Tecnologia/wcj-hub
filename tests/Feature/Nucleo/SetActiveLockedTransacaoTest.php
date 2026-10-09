<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * CR-5-02 (Fix 7 da rodada 12): setActiveLocked() abre a própria transação, como deleteIfUnused().
 * Chamado solto, sem transação de quem chama, a gravação e o $then (a auditoria) ainda são um
 * commit só (G3): se o $then falha, nada fica gravado. Só o banco de verdade prova, sem o
 * RefreshDatabase (que envolve o teste numa transação e esconderia a falta dela).
 */
class SetActiveLockedTransacaoTest extends TestCase
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

    public function test_chamado_fora_de_transacao_desfaz_a_gravacao_quando_o_then_falha(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        $this->assertSame(0, DB::transactionLevel());

        try {
            $client->setActiveLocked(false, $admin, function () {
                throw new RuntimeException('a auditoria falhou');
            });
            $this->fail('A exceção do $then não subiu.');
        } catch (RuntimeException $e) {
            $this->assertSame('a auditoria falhou', $e->getMessage());
        }

        $this->assertSame(0, DB::transactionLevel());
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'deactivated_at' => null, 'deactivated_by' => null]);
    }
}
