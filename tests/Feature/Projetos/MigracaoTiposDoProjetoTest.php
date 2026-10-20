<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Platform\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * MIG-01 (spec b3-quadro-e-card): task_types deixa de ser lista geral e passa a ser do projeto
 * sem perder o tipo de nenhuma tarefa. A migração mexe em schema (DDL faz commit implícito no
 * MySQL), então o teste roda sem o RefreshDatabase e devolve o banco ao estado migrado no fim.
 */
class MigracaoTiposDoProjetoTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    private Migration $migration;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->semearProjetos();
        $this->migration = require database_path('migrations/2026_10_16_000030_move_task_types_to_projects.php');
    }

    protected function tearDown(): void
    {
        if (! Schema::hasColumn('task_types', 'project_id')) {
            $this->migration->up();
        }
        $this->limparOQueFicou();
        parent::tearDown();
    }

    public function test_each_project_gets_a_copy_of_every_general_type_and_tasks_keep_their_type(): void
    {
        [$a, $b] = [$this->projeto(), $this->projeto()];
        $this->migration->down();

        // Banco "antes": lista geral, sem Desenvolvimento, com um tipo criado pelo Administrador e desativado.
        DB::table('task_types')->where('slug', 'development')->delete();
        $admin = User::factory()->admin()->create();
        $pesquisa = DB::table('task_types')->insertGetId([
            'label' => 'Pesquisa', 'color' => 'purple', 'deactivated_at' => now(), 'deactivated_by' => $admin->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $suporte = DB::table('task_types')->where('slug', 'support')->value('id');
        $naA = $this->linha($a, $suporte);
        $naB = $this->linha($b, $pesquisa);
        $semTipo = $this->linha($b, null);

        $this->migration->up();

        $esperado = ['Melhoria', 'Correção de bug', 'Suporte', 'Mudança (GMUD)', 'Pesquisa', 'Desenvolvimento'];
        foreach ([$a, $b] as $project) {
            $this->assertSame($esperado, $this->rotulos($project));
        }
        $this->assertSame(0, DB::table('task_types')->whereNull('project_id')->count(), 'nenhum tipo sem projeto');

        $tipoDe = fn (int $task) => DB::table('tasks')->join('task_types', 'task_types.id', '=', 'tasks.task_type_id')
            ->where('tasks.id', $task)->first(['task_types.project_id', 'task_types.label', 'task_types.slug']);
        $this->assertEquals((object) ['project_id' => $a->id, 'label' => 'Suporte', 'slug' => 'support'], $tipoDe($naA));
        $this->assertEquals((object) ['project_id' => $b->id, 'label' => 'Pesquisa', 'slug' => null], $tipoDe($naB));
        $this->assertNull(DB::table('tasks')->where('id', $semTipo)->value('task_type_id'));

        $copia = DB::table('task_types')->where('project_id', $b->id)->where('label', 'Pesquisa')->first();
        $this->assertSame(['purple', $admin->id], [$copia->color, $copia->deactivated_by]);
        $this->assertNotNull($copia->deactivated_at, 'a desativação vem junto (G7)');
        $this->assertSame(1, DB::table('task_types')->where('project_id', $a->id)->where('slug', 'development')->count());
    }

    public function test_project_id_is_not_null_and_labels_are_unique_per_project_only(): void
    {
        $this->projeto();
        $this->projeto();

        $coluna = collect(Schema::getColumns('task_types'))->firstWhere('name', 'project_id');
        $this->assertFalse($coluna['nullable']);
        $this->assertSame(2, DB::table('task_types')->where('label', 'Melhoria')->count(), 'o mesmo rótulo em dois projetos');

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('task_types')->insert([
            'project_id' => DB::table('projects')->value('id'), 'label' => 'Melhoria', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_running_up_again_duplicates_nothing(): void
    {
        $project = $this->projeto();

        $this->migration->up();

        $this->assertSame(['Melhoria', 'Correção de bug', 'Suporte', 'Mudança (GMUD)', 'Desenvolvimento'], $this->rotulos($project));
    }

    public function test_without_projects_the_general_types_are_simply_removed(): void
    {
        $this->migration->down();
        DB::table('task_types')->insert(['slug' => 'improvement', 'label' => 'Melhoria', 'created_at' => now(), 'updated_at' => now()]);

        $this->migration->up();

        $this->assertSame(0, DB::table('task_types')->count());
    }

    public function test_down_goes_back_to_one_general_type_per_label_with_tasks_pointing_to_it(): void
    {
        [$a, $b] = [$this->projeto(), $this->projeto()];
        $naA = $this->linha($a, $this->tipo($a, 'support'));
        $naB = $this->linha($b, $this->tipo($b, 'support'));

        $this->migration->down();

        $this->assertSame(5, DB::table('task_types')->count());
        $geral = DB::table('task_types')->where('slug', 'support')->value('id');
        $this->assertSame([$geral, $geral], DB::table('tasks')->whereIn('id', [$naA, $naB])->orderBy('id')->pluck('task_type_id')->all());
    }

    /** @return list<string> */
    private function rotulos(Project $project): array
    {
        return DB::table('task_types')->where('project_id', $project->id)->orderBy('id')->pluck('label')->all();
    }

    private function linha(Project $project, ?int $typeId): int
    {
        $project->increment('last_task_number');

        return DB::table('tasks')->insertGetId([
            'project_id' => $project->id, 'number' => $project->last_task_number,
            'board_stage_id' => $this->etapa($project, StageRole::QUEUE)->id, 'task_type_id' => $typeId,
            'title' => 'Card', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
