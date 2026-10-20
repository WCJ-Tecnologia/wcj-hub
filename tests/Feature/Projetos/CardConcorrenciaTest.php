<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Services\TaskMoveService;
use App\Domains\Projetos\Services\TaskService;
use App\Platform\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\Concorrencia;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * T4 e P13 no banco de verdade: a chave do card sai do contador atômico, e duas escritas no mesmo
 * projeto (criar, mover para a mesma coluna) não se cruzam, porque as duas travam a linha do
 * projeto antes de ler o contador ou a maior posição da coluna. A outra requisição roda no meio da
 * principal e tem de esperar a trava (tests/Support/Concorrencia.php).
 */
class CardConcorrenciaTest extends TestCase
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

    public function test_two_quick_cards_at_the_same_time_never_share_a_key(): void
    {
        $gestor = User::factory()->projectManager()->create();
        $project = $this->projeto(['manager_id' => $gestor->id]);
        $tasks = app(TaskService::class);

        $corrida = Concorrencia::intercalarDepoisDe(
            fn (QueryExecuted $consulta) => str_contains($consulta->sql, 'LAST_INSERT_ID(last_task_number + 1)'),
            fn () => $tasks->create($project, $gestor, 'Da outra aba'),
        );

        $tasks->create($project, $gestor, 'Desta aba');

        $this->assertTrue($corrida->rodou, 'O contador não rodou.');
        $this->assertNull($corrida->outroErro);
        $this->assertTrue($corrida->bloqueada, 'A outra criação não esperou a linha do projeto.');
        $this->assertSame([[1, 'Desta aba']], Task::query()->get(['number', 'title'])->map(fn ($t) => [$t->number, $t->title])->all());

        $tasks->create($project, $gestor, 'Depois');
        $this->assertSame([1, 2], Task::query()->orderBy('number')->pluck('number')->all());
    }

    public function test_two_cards_dropped_on_the_same_column_at_the_same_time_wait_for_each_other(): void
    {
        $gestor = User::factory()->projectManager()->create();
        $project = $this->projeto(['manager_id' => $gestor->id]);
        $sprint = $this->sprint($project);
        $doing = $this->etapa($project, StageRole::DEVELOPMENT);
        $review = $this->etapa($project, StageRole::CODE_REVIEW);
        $primeiro = $this->tarefa($project, $doing, ['sprint_id' => $sprint->id]);
        $segundo = $this->tarefa($project, $doing, ['sprint_id' => $sprint->id]);
        $tasks = app(TaskMoveService::class);

        $corrida = Concorrencia::intercalarDepoisDe(
            fn (QueryExecuted $consulta) => str_contains($consulta->sql, 'max(`position`)'),
            fn () => $tasks->move($segundo, $review, $gestor),
        );

        $tasks->move($primeiro, $review, $gestor);

        $this->assertTrue($corrida->rodou, 'A posição da coluna não foi lida.');
        $this->assertNull($corrida->outroErro);
        $this->assertTrue($corrida->bloqueada, 'O outro movimento não esperou a linha do projeto.');

        $tasks->move($segundo->fresh(), $review, $gestor);
        $this->assertSame([1, 2], Task::query()->where('board_stage_id', $review->id)->orderBy('position')->pluck('position')->all());
    }
}
