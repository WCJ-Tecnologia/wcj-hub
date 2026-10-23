<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Services\TaskService;
use App\Platform\Models\User;
use DomainException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\Concorrencia;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * Dois *Marquei: testado por mim* ao mesmo tempo (spec b4-teste-e-qa, CRUZ-01 AC 8 e 9): o segundo
 * espera a linha travada pelo primeiro e, depois, encontra a marca e é recusado; fica uma marca só,
 * a do primeiro. Banco de verdade e intercalação forçada (tests/Support/Concorrencia.php).
 */
class TesteCruzadoConcorrenciaTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->semearProjetos();
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        parent::tearDown();
    }

    public function test_two_simultaneous_marks_wait_for_each_other_and_keep_the_first(): void
    {
        $gestor = User::factory()->projectManager()->create();
        [$primeiro, $segundo] = User::factory()->developer()->count(2)->create()->all();
        $project = $this->projeto(['manager_id' => $gestor->id]);
        $this->alocar($project, $primeiro, $segundo);
        $task = $this->tarefa($project, $this->etapa($project, StageRole::TEST));
        $tasks = app(TaskService::class);

        $corrida = Concorrencia::intercalarDepoisDe(
            fn (QueryExecuted $consulta) => str_contains($consulta->sql, 'from `tasks`') && str_contains($consulta->sql, 'for update'),
            fn () => $tasks->markCrossTested($task, $segundo),
        );

        $tasks->markCrossTested($task, $primeiro);

        $this->assertTrue($corrida->rodou, 'A linha do card não foi travada.');
        $this->assertTrue($corrida->bloqueada, 'A outra marca não esperou a trava.');
        $this->assertSame($primeiro->id, $task->fresh()->cross_tested_by);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Teste cruzado já marcado por {$primeiro->name}: desfaça antes.");
        $tasks->markCrossTested($task, $segundo);
    }
}
