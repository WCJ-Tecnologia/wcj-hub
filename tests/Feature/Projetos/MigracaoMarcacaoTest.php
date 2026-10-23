<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\TestResult;
use App\Platform\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * A migração da última marcação do item (spec b4-teste-e-qa): desce com a FK (o MySQL recusa apagar
 * coluna com FK, erro 1828), sobe de novo e é idempotente. Só no banco de verdade (DDL faz commit
 * implícito), sem RefreshDatabase; o tearDown repõe o schema.
 */
class MigracaoMarcacaoTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    private Migration $migration;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->migration = require database_path('migrations/2026_10_23_000010_add_marcacao_to_task_acceptance_criteria.php');
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        if (! Schema::hasColumn('task_acceptance_criteria', 'marked_by') || ! Schema::hasColumn('task_acceptance_criteria', 'marked_at')) {
            $this->migration->up();
        }
        parent::tearDown();
    }

    /** @return list<string> */
    private function fks(): array
    {
        return collect(Schema::getForeignKeys('task_acceptance_criteria'))->pluck('columns')->flatten()->sort()->values()->all();
    }

    public function test_the_two_columns_go_down_with_the_foreign_key_and_come_back_idempotently(): void
    {
        $this->semearProjetos();
        $qa = User::factory()->developer()->create();
        $task = $this->tarefa($this->projeto());
        $criterion = $task->acceptanceCriteria()->create([
            'number' => 1, 'description' => 'Salva', 'test_result_id' => TestResult::PASSED, 'marked_by' => $qa->id, 'marked_at' => now(),
        ]);

        $this->migration->down();

        $this->assertFalse(Schema::hasColumn('task_acceptance_criteria', 'marked_by'));
        $this->assertFalse(Schema::hasColumn('task_acceptance_criteria', 'marked_at'));
        $this->assertNotContains('marked_by', $this->fks(), 'a FK saiu');
        $this->assertSame(TestResult::PASSED, $criterion->fresh()->test_result_id, 'o resultado continua, sem a marcação');

        $this->migration->up();
        $this->migration->up();

        $this->assertTrue(Schema::hasColumn('task_acceptance_criteria', 'marked_by'));
        $this->assertTrue(Schema::hasColumn('task_acceptance_criteria', 'marked_at'));
        $this->assertContains('marked_by', $this->fks(), 'a FK voltou');
        $this->assertNull($criterion->fresh()->marked_by, 'nulo nas linhas de antes');
    }
}
