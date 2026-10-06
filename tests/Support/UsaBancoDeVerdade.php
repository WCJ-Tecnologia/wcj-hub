<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * Para teste que só prova algo no banco de verdade: trava, corrida, SQL específico do
 * motor (skill wcj-testes, references/banco-real.md). No SQLite ele é pulado com o motivo
 * escrito; no CI roda com DB_TESTE=container.
 *
 * Uso, com DatabaseTruncation (a outra conexão só enxerga dado commitado):
 *
 *     protected function setUp(): void
 *     {
 *         $this->exigirBancoDeVerdade(); // ANTES do parent: no SQLite em memória o
 *         parent::setUp();               // DatabaseTruncation nem migra, e o setUp quebraria
 *     }
 *
 *     protected function tearDown(): void
 *     {
 *         $this->limparOQueFicou();      // o DatabaseTruncation só limpa ANTES do teste
 *         parent::tearDown();
 *     }
 */
trait UsaBancoDeVerdade
{
    protected function exigirBancoDeVerdade(): void
    {
        $motor = isset($this->app)
            ? DB::connection()->getDriverName()
            : ($_SERVER['DB_CONNECTION'] ?? $_ENV['DB_CONNECTION'] ?? (getenv('DB_CONNECTION') ?: null));

        if (getenv('DB_TESTE') === 'container') {
            // Pediram o banco de verdade e caiu no SQLite: o bootstrap não entrou (phpunit.xml
            // ainda em vendor/autoload.php?). Pular aqui daria um CI verde sem provar nada.
            if ($motor === 'sqlite' || ! BancoDeVerdade::ativo()) {
                $this->fail('DB_TESTE=container, mas o banco não é o do container: confira bootstrap="tests/bootstrap.php" no phpunit.xml.');
            }

            return;
        }

        if ($motor === 'sqlite' || $motor === null) {
            $this->markTestSkipped('Só prova no banco de verdade (o SQLite não tem trava de linha). Rode com DB_TESTE=container.');
        }
    }

    /** O que o teste commitou não pode ficar para o próximo (que com RefreshDatabase herdaria). */
    protected function limparOQueFicou(): void
    {
        if (isset($this->app) && method_exists($this, 'truncateDatabaseTables')) {
            $this->truncateDatabaseTables();
        }
    }
}
