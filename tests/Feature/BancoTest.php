<?php

namespace Tests\Feature;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\TaskPriority;
use App\Platform\Models\AiAssistant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * O banco do B0 é o do docs/banco.md: as 27 tabelas, as travas que o banco segura sozinho
 * (colunas geradas + unique, CHECK) e created_at/updated_at not null. Só prova no MySQL 8
 * (ADR-002): no SQLite metade disto nem existe.
 */
class BancoTest extends TestCase
{
    use RefreshDatabase;

    private const TABELAS = [
        // Platform
        'roles', 'users', 'clients', 'contacts', 'contact_channels', 'audit_logs', 'ai_assistants', 'ai_authorizations',
        // Projetos
        'projects', 'project_members', 'stage_roles', 'board_stages', 'task_types', 'project_areas', 'task_priorities',
        'tasks', 'task_stage_movements', 'rework_reasons', 'task_acceptance_criteria', 'test_results', 'validation_types',
        'task_pull_requests', 'task_comments', 'task_attachments', 'time_entries', 'packages', 'sprints',
    ];

    private const SAIRAM = ['user_types', 'enterprises', 'user_has_enterprises'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_the_27_tables_exist_and_the_template_ones_are_gone(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertCount(27, self::TABELAS);

        foreach (self::TABELAS as $tabela) {
            $this->assertTrue(Schema::hasTable($tabela), "falta {$tabela}");
        }
        foreach (self::SAIRAM as $tabela) {
            $this->assertFalse(Schema::hasTable($tabela), "{$tabela} saiu na seção 9");
        }
        foreach (['document', 'phone', 'email_verified_at', 'last_login_at', 'remember_token'] as $coluna) {
            $this->assertFalse(Schema::hasColumn('users', $coluna), "users.{$coluna} saiu na seção 9");
        }
    }

    public function test_created_and_updated_at_are_datetime_not_null_and_no_timestamp_type_exists(): void
    {
        $colunas = DB::select(
            'select table_name as t, column_name as c, data_type as tipo, is_nullable as nulo from information_schema.columns
             where table_schema = database() and table_name in ('.implode(',', array_fill(0, 27, '?')).')',
            self::TABELAS,
        );

        foreach ($colunas as $coluna) {
            $this->assertNotSame('timestamp', $coluna->tipo, "{$coluna->t}.{$coluna->c} é TIMESTAMP");
            if (in_array($coluna->c, ['created_at', 'updated_at'], true)) {
                $this->assertSame('datetime', $coluna->tipo, "{$coluna->t}.{$coluna->c}");
                $this->assertSame('NO', $coluna->nulo, "{$coluna->t}.{$coluna->c} tem de ser not null");
            }
        }
    }

    public function test_one_active_queue_sprint_and_support_stage_per_project(): void
    {
        $projeto = $this->projeto();
        $etapa = fn (int $papel, ?string $arquivada = null) => DB::table('board_stages')->insert([
            'project_id' => $projeto, 'stage_role_id' => $papel, 'name' => 'Etapa '.uniqid(), 'position' => 1,
            'archived_at' => $arquivada, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $etapa(StageRole::QUEUE);
        $etapa(StageRole::QUEUE, now()->toDateTimeString()); // arquivada não colide
        $etapa(StageRole::DEVELOPMENT);
        $etapa(StageRole::DEVELOPMENT); // development pode repetir

        $this->expectException(QueryException::class);
        $etapa(StageRole::QUEUE);
    }

    public function test_one_active_ai_connection_per_user_and_assistant(): void
    {
        $user = $this->usuario();
        $conexao = fn (?string $revogada = null) => DB::table('ai_authorizations')->insert([
            'user_id' => $user, 'ai_assistant_id' => AiAssistant::CLAUDE, 'oauth_client_id' => (string) str()->uuid(),
            'revoked_at' => $revogada, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $conexao(now()->toDateTimeString());
        $conexao(now()->toDateTimeString()); // as revogadas não colidem
        $conexao();

        $this->expectException(QueryException::class);
        $conexao();
    }

    public function test_criterion_number_is_unique_per_parent_even_at_the_top_level(): void
    {
        $tarefa = $this->tarefa();
        $criterio = fn (int $numero, ?int $pai = null) => DB::table('task_acceptance_criteria')->insertGetId([
            'task_id' => $tarefa, 'parent_id' => $pai, 'number' => $numero, 'description' => 'CA',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $ca1 = $criterio(1);
        $criterio(1, $ca1); // CA_1.1: outro pai, mesmo número
        $this->assertSame(1, (int) DB::table('task_acceptance_criteria')->where('id', $ca1)->value('test_result_id'));

        try {
            $criterio(1); // CA_1 de novo, no primeiro nível (parent_id nulo)
            $this->fail('parent_key não barrou o número repetido no primeiro nível');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        // VIRTUAL: o cascade de parent_id apaga o subitem junto (T9).
        DB::table('task_acceptance_criteria')->where('id', $ca1)->delete();
        $this->assertSame(0, DB::table('task_acceptance_criteria')->where('task_id', $tarefa)->count());
    }

    /**
     * @return iterable<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function checks(): iterable
    {
        yield 'users.daily_hours acima de 24' => ['users', ['daily_hours' => 25]];
        yield 'clients BR sem CNPJ' => ['clients', ['document' => null]];
        yield 'audit_logs outcome fora da lista' => ['audit_logs', ['outcome' => 'maybe']];
        yield 'projects due_on antes do início' => ['projects', ['due_on' => '2026-01-01']];
        yield 'tasks horas previstas zero' => ['tasks', ['estimated_hours' => 0]];
        yield 'tasks arquivada sem motivo' => ['tasks', ['archived_at' => '2026-10-06 12:00:00']];
        yield 'tasks teste cruzado sem data' => ['tasks', ['cross_tested_by' => 'usuario']];
        yield 'sprints fim antes do início' => ['sprints', ['ends_on' => '2026-01-01']];
        yield 'time_entries mais de 24 h' => ['time_entries', ['hours' => 24.5]];
    }

    /**
     * @param  array<string, mixed>  $invalido
     */
    #[DataProvider('checks')]
    public function test_the_database_refuses_what_the_check_forbids(string $tabela, array $invalido): void
    {
        $linha = match ($tabela) {
            'users' => fn () => ['role_id' => 3, 'name' => 'X', 'email' => uniqid().'@example.com', 'password' => 'x'],
            'clients' => fn () => ['name' => 'X', 'country' => 'BR', 'document' => uniqid()],
            'audit_logs' => fn () => ['action' => 'updated', 'outcome' => 'success', 'auditable_type' => 'user'],
            'projects' => fn () => ['client_id' => $this->cliente(), 'manager_id' => $this->usuario(), 'key_prefix' => strtoupper(substr(uniqid(), -6)), 'name' => 'P', 'starts_on' => '2026-10-06'],
            'tasks' => fn () => $this->linhaDeTarefa(),
            'sprints' => fn () => ['project_id' => $this->projeto(), 'name' => 'S1', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-20'],
            'time_entries' => fn () => ['task_id' => $this->tarefa(), 'user_id' => $this->usuario(), 'worked_on' => '2026-10-06', 'hours' => 8],
        };
        $valida = $linha();
        if (($invalido['cross_tested_by'] ?? null) === 'usuario') {
            $invalido['cross_tested_by'] = $this->usuario();
        }
        $carimbo = $tabela === 'audit_logs' ? ['created_at' => now()] : ['created_at' => now(), 'updated_at' => now()];

        // A linha válida entra: o que recusa é só o campo inválido.
        DB::table($tabela)->insert([...$valida, ...$carimbo]);

        $this->expectException(QueryException::class);
        DB::table($tabela)->insert([...$linha(), ...$invalido, ...$carimbo]);
    }

    private function usuario(): int
    {
        return DB::table('users')->insertGetId([
            'role_id' => 3, 'name' => 'Dev', 'email' => uniqid().'@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function cliente(): int
    {
        return DB::table('clients')->insertGetId(['name' => 'C '.uniqid(), 'document' => uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function projeto(): int
    {
        return DB::table('projects')->insertGetId([
            'client_id' => $this->cliente(), 'manager_id' => $this->usuario(), 'key_prefix' => strtoupper(substr(uniqid(), -6)),
            'name' => 'Projeto', 'starts_on' => '2026-10-06', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function linhaDeTarefa(): array
    {
        $projeto = $this->projeto();
        $etapa = DB::table('board_stages')->insertGetId([
            'project_id' => $projeto, 'stage_role_id' => StageRole::QUEUE, 'name' => 'Backlog', 'position' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [
            'project_id' => $projeto, 'number' => 1, 'board_stage_id' => $etapa,
            'task_type_id' => DB::table('task_types')->where('slug', 'improvement')->value('id'),
            'task_priority_id' => TaskPriority::NORMAL, 'title' => 'Card',
        ];
    }

    private function tarefa(): int
    {
        return DB::table('tasks')->insertGetId([...$this->linhaDeTarefa(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
