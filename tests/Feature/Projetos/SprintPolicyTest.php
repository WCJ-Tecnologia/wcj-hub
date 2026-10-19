<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * SprintPolicy (spec b5-sprint-e-horas: SPR-01 a SPR-03; F5 §09; P45, P49, P50): só quem gere o
 * projeto escreve na sprint e vê a capacidade; quem vê o projeto vê as sprints. Toda negação vira
 * linha `denied` na auditoria, com a ação certa, e nada muda.
 */
class SprintPolicyTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $dev;

    private Sprint $aberta;

    private Sprint $encerrada;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
        $this->alocar($this->project, $this->dev);
        $this->encerrada = $this->sprint($this->project, ['name' => 'Sprint 1', 'closed_at' => now()]);
        $this->aberta = $this->sprint($this->project, ['name' => 'Sprint 2', 'starts_on' => '2026-10-20', 'ends_on' => '2026-11-02']);
        $this->task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $this->aberta->id, 'estimated_hours' => 8]);
    }

    /** @return array<string, array{string}> */
    public static function quemGere(): array
    {
        return ['administrador' => ['admin'], 'gestor responsável' => ['gestor'], 'gestor alocado' => ['alocado']];
    }

    private function quem(string $papel): User
    {
        return match ($papel) {
            'admin' => User::factory()->admin()->create(),
            'gestor' => $this->gestor,
            'alocado' => tap(User::factory()->projectManager()->create(), fn (User $user) => $this->alocar($this->project, $user)),
        };
    }

    private function url(string $suffix = ''): string
    {
        return "/projetos/{$this->project->id}{$suffix}";
    }

    /**
     * Cada escrita de sprint, com o que ela manda.
     *
     * @return array<string, array{string, string, array<string, mixed>, string}>
     */
    private function escritas(): array
    {
        $dados = ['name' => 'Sprint 9', 'goal' => '', 'starts_on' => '2026-11-03', 'ends_on' => '2026-11-16'];

        return [
            'criar' => ['post', '/sprints', $dados, 'created'],
            'editar' => ['put', "/sprints/{$this->aberta->id}", $dados, 'updated'],
            'encerrar' => ['post', "/sprints/{$this->aberta->id}/encerrar", ['create_next' => true, 'next' => $dados], 'finished'],
            'reabrir' => ['post', "/sprints/{$this->encerrada->id}/reabrir", [], 'reopened'],
            'excluir' => ['delete', "/sprints/{$this->aberta->id}", [], 'deleted'],
        ];
    }

    /** @return array<string, mixed> */
    private function retrato(): array
    {
        return [
            'sprints' => Sprint::query()->orderBy('id')->get(['id', 'name', 'starts_on', 'ends_on', 'closed_at'])->toArray(),
            'task' => $this->task->fresh()->only(['sprint_id', 'board_stage_id', 'estimated_hours', 'proposed_hours']),
        ];
    }

    #[DataProvider('quemGere')]
    public function test_who_manages_the_project_sees_capacity_and_write_abilities(string $papel): void
    {
        $this->actingAs($this->quem($papel))->get($this->url('/sprints'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('projects/sprints')
                ->where('selected.id', $this->aberta->id)
                ->has('selected.capacity.developers')
                ->where('selected.can.close', true)
                ->where('can.manage_tasks', true)
                ->where('closing.sprint_id', $this->aberta->id));
    }

    #[DataProvider('quemGere')]
    public function test_who_manages_the_project_writes_on_the_sprint(string $papel): void
    {
        $user = $this->quem($papel);

        $this->actingAs($user)->put($this->url("/sprints/{$this->aberta->id}"), ['name' => 'Fase 2', 'goal' => 'x', 'starts_on' => '2026-10-20', 'ends_on' => '2026-11-02'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->actingAs($user)->put($this->url("/tarefas/{$this->task->id}/horas-previstas"), ['estimated_hours' => 12])->assertSessionHas('success');
        $this->actingAs($user)->delete($this->url("/tarefas/{$this->task->id}/sprint"))->assertSessionHas('success');
        $this->actingAs($user)->post($this->url('/sprint-aberta/tarefas'), ['task_id' => $this->task->id])->assertSessionHas('success');
        $this->actingAs($user)->post($this->url("/sprints/{$this->aberta->id}/encerrar"), ['create_next' => false, 'destinations' => [$this->task->id => 'backlog']])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->actingAs($user)->post($this->url("/sprints/{$this->aberta->id}/reabrir"))->assertSessionHas('success');

        $this->assertSame('Fase 2', $this->aberta->fresh()->name);
        $this->assertNull($this->aberta->fresh()->closed_at);
        $this->assertSame('12.00', $this->task->fresh()->estimated_hours);
    }

    public function test_allocated_developer_sees_sprints_without_capacity_nor_write_abilities(): void
    {
        $this->actingAs($this->dev)->get($this->url('/sprints'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('selected.capacity', null)
                ->where('selected.can', ['update' => false, 'close' => false, 'reopen' => false, 'delete' => false])
                ->where('can.manage_tasks', false)
                ->where('closing', null)
                ->where('proposal', null)
                ->has('selected.tasks', 1));
    }

    /** @return array<string, array{string}> */
    public static function quemNaoVe(): array
    {
        return ['dev não alocado' => ['dev'], 'gestor que não gere' => ['gestor'], 'comercial' => ['comercial']];
    }

    #[DataProvider('quemNaoVe')]
    public function test_who_does_not_see_the_project_gets_403_on_the_list_and_a_denied_row(string $papel): void
    {
        $user = match ($papel) {
            'dev' => User::factory()->developer()->create(),
            'gestor' => User::factory()->projectManager()->create(),
            'comercial' => User::factory()->sales()->create(),
        };

        $this->actingAs($user)->get($this->url('/sprints'))->assertForbidden();

        $log = AuditLog::query()->where('outcome', 'denied')->sole();
        $this->assertSame(['sprint', 'viewed', $user->id], [$log->auditable_type, $log->action, $log->user_id]);
    }

    public function test_allocated_developer_cannot_write_on_the_sprint_and_nothing_changes(): void
    {
        $this->assertDeniedForAll($this->dev);
    }

    public function test_project_manager_who_does_not_manage_cannot_write_and_nothing_changes(): void
    {
        $this->assertDeniedForAll(User::factory()->projectManager()->create());
    }

    public function test_manager_who_lost_the_role_cannot_write_anymore(): void
    {
        $this->gestor->forceFill(['role_id' => RoleEnum::DEVELOPER->value])->save();
        $this->alocar($this->project, $this->gestor);

        $this->assertDeniedForAll($this->gestor->fresh());
    }

    private function assertDeniedForAll(User $user): void
    {
        $antes = $this->retrato();

        foreach ($this->escritas() as $caso => [$method, $suffix, $data, $action]) {
            $this->assertOneDenied($user, fn () => $this->actingAs($user)->{$method}($this->url($suffix), $data)->assertForbidden(), $action, $caso);
        }

        $tarefas = [
            'pôr' => ['post', '/sprint-aberta/tarefas', ['task_id' => $this->task->id]],
            'pôr em lote' => ['post', '/sprint-aberta/tarefas/lote', ['tasks' => [['id' => $this->task->id]]]],
            'tirar' => ['delete', "/tarefas/{$this->task->id}/sprint", []],
            'horas' => ['put', "/tarefas/{$this->task->id}/horas-previstas", ['estimated_hours' => 99]],
            'aprovar' => ['post', "/tarefas/{$this->task->id}/reestimativa/aprovar", []],
            'recusar' => ['post', "/tarefas/{$this->task->id}/reestimativa/recusar", []],
        ];
        foreach ($tarefas as $caso => [$method, $suffix, $data]) {
            $this->assertOneDenied($user, fn () => $this->actingAs($user)->{$method}($this->url($suffix), $data)->assertForbidden(), 'updated', $caso);
        }

        $this->assertSame($antes, $this->retrato());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->whereIn('auditable_type', ['sprint', 'task'])->count());
    }

    /** Exatamente uma linha `denied` nova, desta requisição e desta pessoa (Verifier, rodada 1, gap 3). */
    private function assertOneDenied(User $user, \Closure $request, string $action, string $caso): void
    {
        $before = AuditLog::query()->where('outcome', 'denied')->max('id') ?? 0;
        $request();
        $rows = AuditLog::query()->where('outcome', 'denied')->where('id', '>', $before)->get();

        $this->assertCount(1, $rows, $caso);
        $this->assertSame(['sprint', $action, $user->id], [$rows[0]->auditable_type, $rows[0]->action, $rows[0]->user_id], $caso);
    }

    // Verifier, rodada 1, gap 1: excluir e criar pela rota, por quem gere (SPR-01 AC 1, DEL-01 AC 1).
    #[DataProvider('quemGere')]
    public function test_who_manages_creates_and_deletes_an_empty_open_sprint(string $papel): void
    {
        $user = $this->quem($papel);
        $this->task->forceFill(['sprint_id' => null])->save();

        $this->actingAs($user)->delete($this->url("/sprints/{$this->aberta->id}"))
            ->assertRedirect($this->url('/sprints'))
            ->assertSessionHas('success', 'Sprint 2 excluída.');
        $this->assertNull(Sprint::query()->find($this->aberta->id));
        $log = AuditLog::query()->where('auditable_type', 'sprint')->where('action', 'deleted')->sole();
        $this->assertSame([$user->id, 'success', ['Sprint 2', null]], [$log->user_id, $log->outcome, $log->changes['name']]);

        $this->actingAs($user)->post($this->url('/sprints'), ['name' => 'Sprint 3', 'goal' => '', 'starts_on' => '2026-11-03', 'ends_on' => '2026-11-16'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Sprint 3 criada.');
        $this->assertSame(1, Sprint::query()->whereNull('closed_at')->where('name', 'Sprint 3')->count());
    }

    // Verifier, rodada 1, gap 2: SprintPolicy::view(Sprint), a ability por registro, igual à do projeto.
    public function test_view_of_a_sprint_follows_who_sees_the_project(): void
    {
        $permitidos = [User::factory()->admin()->create(), $this->gestor, $this->dev];
        $negados = [User::factory()->developer()->create(), User::factory()->projectManager()->create(), User::factory()->sales()->create()];

        foreach ($permitidos as $user) {
            $this->assertTrue(Gate::forUser($user)->allows('view', $this->encerrada), $user->email);
        }
        foreach ($negados as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('view', $this->encerrada), $user->email);
        }
        // O dev vê, mas não gere: view não é manage.
        $this->assertFalse(Gate::forUser($this->dev)->allows('update', $this->encerrada));
    }

    public function test_sprint_of_another_project_is_404(): void
    {
        $outro = $this->projeto(['manager_id' => $this->gestor->id]);
        $alheia = $this->sprint($outro);

        $this->actingAs($this->gestor)->put($this->url("/sprints/{$alheia->id}"), ['name' => 'x', 'starts_on' => '2026-10-20', 'ends_on' => '2026-11-02'])->assertNotFound();
        $this->actingAs($this->gestor)->delete($this->url("/tarefas/{$this->tarefa($outro)->id}/sprint"))->assertNotFound();
    }
}
