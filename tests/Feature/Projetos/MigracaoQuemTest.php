<?php

namespace Tests\Feature\Projetos;

use App\Platform\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * MIG-04 (spec b3-ficha; Verifier rodada 1, Fix 4): a migração das quatro colunas de "quem" desce
 * (com as FKs; o MySQL recusa apagar coluna com FK, erro 1828), sobe de novo e é idempotente. Só no
 * banco de verdade (DDL faz commit implícito), sem RefreshDatabase; o tearDown repõe o schema.
 */
class MigracaoQuemTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    private const COLUNAS = [['task_pull_requests', 'user_id'], ['task_attachments', 'user_id'], ['tasks', 'proposed_by'], ['tasks', 'archived_by']];

    private Migration $migration;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->migration = require database_path('migrations/2026_10_16_000040_add_quem_to_task_records.php');
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        if (! Schema::hasColumn('tasks', 'proposed_by')) {
            $this->migration->up();
        }
        parent::tearDown();
    }

    /** @return list<string> */
    private function fks(string $tabela): array
    {
        return collect(Schema::getForeignKeys($tabela))->pluck('columns')->flatten()->sort()->values()->all();
    }

    public function test_the_four_columns_go_down_with_their_foreign_keys_and_come_back_idempotently(): void
    {
        $this->semearProjetos();
        $dev = User::factory()->developer()->create();
        $task = $this->tarefa($this->projeto(), null, ['proposed_hours' => 5, 'proposed_by' => $dev->id]);
        $task->pullRequests()->create(['url' => 'https://github.com/x/y/pull/1', 'user_id' => $dev->id]);
        $task->attachments()->create(['path' => 'tasks/a.pdf', 'original_name' => 'a.pdf', 'mime_type' => 'application/pdf', 'user_id' => $dev->id]);

        $this->migration->down();

        foreach (self::COLUNAS as [$tabela, $coluna]) {
            $this->assertFalse(Schema::hasColumn($tabela, $coluna), "{$tabela}.{$coluna} saiu");
            $this->assertNotContains($coluna, $this->fks($tabela), "a FK de {$tabela}.{$coluna} saiu");
        }
        $this->assertSame(1, $task->pullRequests()->count(), 'o link continua, sem o quem');

        $this->migration->up();
        $this->migration->up();

        foreach (self::COLUNAS as [$tabela, $coluna]) {
            $this->assertTrue(Schema::hasColumn($tabela, $coluna), "{$tabela}.{$coluna} voltou");
            $this->assertContains($coluna, $this->fks($tabela), "a FK de {$tabela}.{$coluna} voltou");
        }
        $this->assertNull($task->fresh()->proposed_by, 'nulo nas linhas de antes');
    }
}
