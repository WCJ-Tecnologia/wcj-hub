<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Services\SprintService;
use App\Domains\Projetos\Services\SprintTaskService;
use App\Platform\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\Concorrencia;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * P45: uma sprint aberta por projeto, sem coluna nem índice no banco, só com a linha do projeto
 * travada. Dois gestores criando ao mesmo tempo (ou arrastando para o To do sem sprint aberta): a
 * outra requisição roda no meio, logo depois de a principal conferir "há aberta?", e tem de ficar
 * esperando a trava; no fim, uma aberta só (tests/Support/Concorrencia.php).
 */
class SprintAbertaConcorrenciaTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    private const SPRINT = ['name' => 'Sprint 1', 'goal' => null, 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'];

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

    /** A checagem "há sprint aberta neste projeto?". */
    private static function checagemDaAberta(): \Closure
    {
        return fn (QueryExecuted $consulta) => str_contains($consulta->sql, 'from `sprints`')
            && str_contains($consulta->sql, '`closed_at` is null');
    }

    public function test_two_creations_at_the_same_time_leave_one_open_sprint(): void
    {
        $gestor = User::factory()->projectManager()->create();
        $project = $this->projeto(['manager_id' => $gestor->id]);
        $sprints = app(SprintService::class);

        $corrida = Concorrencia::intercalarDepoisDe(
            self::checagemDaAberta(),
            fn () => $sprints->create($project, [...self::SPRINT, 'name' => 'Sprint 2'], $gestor),
        );

        $sprints->create($project, self::SPRINT, $gestor);

        $this->assertTrue($corrida->rodou, 'A checagem da aberta não rodou.');
        $this->assertNull($corrida->outroErro);
        $this->assertTrue($corrida->bloqueada, 'A outra criação não esperou a linha do projeto.');
        $this->assertSame(['Sprint 1'], Sprint::query()->whereNull('closed_at')->pluck('name')->all());
    }

    public function test_two_drags_without_open_sprint_create_one_sprint(): void
    {
        $gestor = User::factory()->projectManager()->create();
        $project = $this->projeto(['manager_id' => $gestor->id]);
        $primeiro = $this->tarefa($project);
        $segundo = $this->tarefa($project);
        $sprints = app(SprintService::class);

        $corrida = Concorrencia::intercalarDepoisDe(
            self::checagemDaAberta(),
            fn () => app(SprintTaskService::class)->addTask($segundo, $gestor, [...self::SPRINT, 'name' => 'Sprint 2']),
        );

        app(SprintTaskService::class)->addTask($primeiro, $gestor, self::SPRINT);

        $this->assertTrue($corrida->rodou);
        $this->assertNull($corrida->outroErro);
        $this->assertTrue($corrida->bloqueada);
        $this->assertSame(1, Sprint::query()->count());
    }

    public function test_reopen_and_create_at_the_same_time_leave_one_open_sprint(): void
    {
        $gestor = User::factory()->projectManager()->create();
        $project = $this->projeto(['manager_id' => $gestor->id]);
        $antiga = $this->sprint($project, ['name' => 'Sprint 1', 'closed_at' => now()]);
        $sprints = app(SprintService::class);

        $corrida = Concorrencia::intercalarDepoisDe(
            self::checagemDaAberta(),
            fn () => $sprints->create($project, [...self::SPRINT, 'name' => 'Sprint 2'], $gestor),
        );

        $sprints->reopen($antiga, $gestor);

        $this->assertTrue($corrida->rodou);
        $this->assertNull($corrida->outroErro);
        $this->assertTrue($corrida->bloqueada);
        $this->assertSame(['Sprint 1'], Sprint::query()->whereNull('closed_at')->pluck('name')->all());
    }

    /**
     * Dois *Adicionar do Backlog* sem sprint aberta ao mesmo tempo (D-GER-35, F5 §07): o segundo
     * espera a linha do projeto em vez de criar outra sprint (quando a trava solta, ele entra na
     * aberta, `joined_existing`, coberto no SprintEmLoteTest). No fim, uma sprint só.
     */
    public function test_two_batches_without_open_sprint_create_one_sprint(): void
    {
        $gestor = User::factory()->projectManager()->create();
        $project = $this->projeto(['manager_id' => $gestor->id]);
        [$a, $b, $c] = [$this->tarefa($project), $this->tarefa($project), $this->tarefa($project)];

        $corrida = Concorrencia::intercalarDepoisDe(
            self::checagemDaAberta(),
            fn () => app(SprintTaskService::class)->addMany($project, [$c->id], $gestor, [...self::SPRINT, 'name' => 'Sprint 2']),
        );

        app(SprintTaskService::class)->addMany($project, [$a->id, $b->id], $gestor, self::SPRINT);

        $this->assertTrue($corrida->rodou);
        $this->assertNull($corrida->outroErro);
        $this->assertTrue($corrida->bloqueada);
        $sprint = Sprint::query()->sole();
        $this->assertSame('Sprint 1', $sprint->name);
        $this->assertSame([$sprint->id, $sprint->id, null], [$a->fresh()->sprint_id, $b->fresh()->sprint_id, $c->fresh()->sprint_id]);
    }
}
