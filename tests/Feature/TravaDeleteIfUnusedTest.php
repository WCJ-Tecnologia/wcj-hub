<?php

namespace Tests\Feature;

use App\Platform\Models\Enterprise;
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
        $enterprise = Enterprise::factory()->create();

        // Control: with no transaction open, the same probe finds the row free.
        $this->assertFalse(Concorrencia::linhaEstaTravada('enterprises', $enterprise->getKey()));

        ProbedEnterprise::query()->findOrFail($enterprise->getKey())->deleteIfUnused();

        $this->assertTrue(ProbedEnterprise::$rowWasLocked, 'hasHistory() ran without the row lock: another process could add history between the check and the delete.');
        $this->assertDatabaseHas('enterprises', ['id' => $enterprise->getKey()]);
    }
}

/** An enterprise that, in the middle of deleteIfUnused(), asks another connection whether its row is locked. */
class ProbedEnterprise extends Enterprise
{
    public static ?bool $rowWasLocked = null;

    protected $table = 'enterprises';

    public function hasHistory(): bool
    {
        self::$rowWasLocked = Concorrencia::linhaEstaTravada($this->getTable(), $this->getKey());

        return true; // keep the row: the proof is the lock, not the delete
    }
}
