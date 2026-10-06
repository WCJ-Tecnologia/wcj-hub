<?php

namespace Tests\Feature;

use App\Platform\Models\Client;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\Concorrencia;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * deleteIfUnused() promete travar a linha da checagem até o delete (.ai/core/banco.md §4),
 * para nenhum histórico entrar no meio. Só o banco de verdade prova a trava: no SQLite o
 * teste se pula (rode com composer test:banco).
 */
class TravaDeleteIfUnusedTest extends TestCase
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

    public function test_the_row_stays_locked_while_the_history_is_checked(): void
    {
        $client = Client::factory()->create();

        // Control: with no transaction open, the same probe finds the row free.
        $this->assertFalse(Concorrencia::linhaEstaTravada('clients', $client->getKey()));

        ProbedClient::query()->findOrFail($client->getKey())->deleteIfUnused();

        $this->assertTrue(ProbedClient::$rowWasLocked, 'hasHistory() ran without the row lock: another process could add history between the check and the delete.');
        $this->assertDatabaseHas('clients', ['id' => $client->getKey()]);
    }
}

/** A client that, in the middle of deleteIfUnused(), asks another connection whether its row is locked. */
class ProbedClient extends Client
{
    public static ?bool $rowWasLocked = null;

    protected $table = 'clients';

    public function hasHistory(): bool
    {
        self::$rowWasLocked = Concorrencia::linhaEstaTravada($this->getTable(), $this->getKey());

        return true; // keep the row: the proof is the lock, not the delete
    }
}
