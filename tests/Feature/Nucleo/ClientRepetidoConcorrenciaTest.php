<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Services\ClientService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Validation\ValidationException;
use Tests\Support\Concorrencia;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * CR-1-02 do ciclo 1: duas gravações do mesmo CNPJ ao mesmo tempo passam as duas pela
 * checagem de repetido (N5); o unique do banco barra a segunda, que tem de voltar como o erro
 * do campo, não como 500. A outra gravação é forçada logo depois da checagem da principal,
 * noutra conexão (tests/Support/Concorrencia.php).
 */
class ClientRepetidoConcorrenciaTest extends TestCase
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

    public function test_cnpj_gravado_por_outro_no_meio_vira_erro_do_campo_citando_o_outro(): void
    {
        $admin = User::factory()->admin()->create();
        $clients = app(ClientService::class);
        $data = ['country' => 'BR', 'document' => '12.ABC.345/01DE-35'];

        // A checagem de repetido: a consulta por país e documento.
        $corrida = Concorrencia::intercalarDepoisDe(
            fn (QueryExecuted $consulta) => str_contains($consulta->sql, 'from `clients`')
                && str_contains($consulta->sql, '`country` = ?')
                && str_contains($consulta->sql, '`document` = ?'),
            fn () => $clients->create(['name' => 'Mercado Central', ...$data], $admin),
        );

        try {
            $clients->create(['name' => 'Padaria Pão Quente', ...$data], $admin);
            $this->fail('Gravou o CNPJ repetido.');
        } catch (ValidationException $e) {
            $this->assertSame(['document' => ['Já existe o cliente Mercado Central com este CNPJ']], $e->errors());
        }

        $this->assertTrue($corrida->rodou, 'A checagem de repetido não rodou.');
        $this->assertNull($corrida->outroErro);
        $this->assertSame(['Mercado Central'], Client::query()->where('document', '12ABC34501DE35')->pluck('name')->all());
    }
}
