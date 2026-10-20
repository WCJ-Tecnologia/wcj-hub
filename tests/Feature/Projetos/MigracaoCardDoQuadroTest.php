<?php

namespace Tests\Feature\Projetos;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * MIG-01 AC 5 e o critério "`migrate:rollback` + `migrate` sem erro" (spec b3-quadro-e-card;
 * Verifier rodada 1, Fix 1): as três migrações de 16/10 descem na ordem inversa e sobem de novo,
 * e a volta de task_type_id/task_priority_id a not null passa mesmo com a FK ligada. Só no banco
 * de verdade (DDL faz commit implícito), sem RefreshDatabase; o tearDown repõe o schema migrado.
 */
class MigracaoCardDoQuadroTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    /** @var array<string, Migration> na ordem do up() */
    private array $migrations;

    /** A coluna que diz se cada migração está aplicada. */
    private const MARCA = [
        'card' => ['tasks', 'parent_task_id'],
        'capa' => ['tasks', 'cover_attachment_id'],
        'tipos' => ['task_types', 'project_id'],
    ];

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->migrations = [
            'card' => require database_path('migrations/2026_10_16_000010_add_card_do_quadro_to_tasks.php'),
            'capa' => require database_path('migrations/2026_10_16_000020_add_cover_attachment_id_to_tasks.php'),
            'tipos' => require database_path('migrations/2026_10_16_000030_move_task_types_to_projects.php'),
        ];
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        foreach (self::MARCA as $nome => [$tabela, $coluna]) {
            if (! Schema::hasColumn($tabela, $coluna)) {
                $this->migrations[$nome]->up();
            }
        }
        parent::tearDown();
    }

    private function nullable(string $tabela, string $coluna): bool
    {
        return (bool) collect(Schema::getColumns($tabela))->firstWhere('name', $coluna)['nullable'];
    }

    /** @return list<string> */
    private function fks(string $tabela): array
    {
        return collect(Schema::getForeignKeys($tabela))->pluck('columns')->flatten()->sort()->values()->all();
    }

    public function test_the_three_migrations_go_down_in_reverse_order_and_come_back(): void
    {
        $this->migrations['tipos']->down();
        $this->migrations['capa']->down();
        $this->migrations['card']->down();

        $this->assertFalse(Schema::hasColumn('tasks', 'parent_task_id'));
        $this->assertFalse(Schema::hasColumn('tasks', 'cover_attachment_id'));
        $this->assertFalse(Schema::hasTable('task_participants'));
        $this->assertFalse(Schema::hasColumn('task_types', 'color'));
        $this->assertFalse(Schema::hasColumn('task_types', 'project_id'));
        $this->assertFalse(Schema::hasColumn('project_areas', 'color'));
        $this->assertFalse($this->nullable('tasks', 'task_type_id'), 'task_type_id voltou a not null');
        $this->assertFalse($this->nullable('tasks', 'task_priority_id'), 'task_priority_id voltou a not null');
        $this->assertContains('task_type_id', $this->fks('tasks'), 'a FK do tipo continua');
        $this->assertContains('task_priority_id', $this->fks('tasks'), 'a FK da prioridade continua');

        $this->migrations['card']->up();
        $this->migrations['capa']->up();
        $this->migrations['tipos']->up();

        $this->assertTrue(Schema::hasColumn('tasks', 'parent_task_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'cover_attachment_id'));
        $this->assertTrue(Schema::hasTable('task_participants'));
        $this->assertTrue(Schema::hasColumn('task_types', 'color'));
        $this->assertTrue(Schema::hasColumn('task_types', 'project_id'));
        $this->assertTrue(Schema::hasColumn('project_areas', 'color'));
        $this->assertTrue($this->nullable('tasks', 'task_type_id'));
        $this->assertTrue($this->nullable('tasks', 'task_priority_id'));
        $this->assertSame(
            ['cover_attachment_id', 'parent_task_id', 'task_priority_id', 'task_type_id'],
            array_values(array_intersect($this->fks('tasks'), ['cover_attachment_id', 'parent_task_id', 'task_priority_id', 'task_type_id'])),
        );
    }

    /** Card sem tipo impede a volta ao not null; como o change() vem primeiro, nada mais sai do banco. */
    public function test_down_refuses_a_card_without_type_and_leaves_the_schema_as_it_was(): void
    {
        $this->semearProjetos();
        $this->tarefa($this->projeto(), null, ['task_type_id' => null]);
        $this->migrations['tipos']->down();
        $this->migrations['capa']->down();

        try {
            $this->migrations['card']->down();
            $this->fail('o down() aceitou voltar a not null com um card sem tipo');
        } catch (QueryException $e) {
            $this->assertStringContainsString('task_type_id', $e->getMessage());
        }

        $this->assertTrue($this->nullable('tasks', 'task_type_id'));
        $this->assertTrue(Schema::hasColumn('tasks', 'parent_task_id'));
        $this->assertTrue(Schema::hasTable('task_participants'));
        $this->assertTrue(Schema::hasColumn('task_types', 'color'));
        $this->assertTrue(Schema::hasColumn('project_areas', 'color'));
    }
}
