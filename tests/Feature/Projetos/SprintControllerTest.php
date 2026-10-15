<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Tela e rotas de sprints (F5 §02 a §05, §07) pelo gestor: o que a tela recebe, recusas e toasts. */
class SprintControllerTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-06 12:00:00');
        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id, 'sprint_days' => 10]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function url(string $suffix = ''): string
    {
        return "/projetos/{$this->project->id}{$suffix}";
    }

    public function test_empty_project_proposes_sprint_1_from_today(): void
    {
        $this->actingAs($this->gestor)->get($this->url('/sprints'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sprints', [])
                ->where('selected', null)
                ->where('proposal', ['name' => 'Sprint 1', 'goal' => '', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'])
                ->where('today', '2026-10-06'));
    }

    public function test_list_has_the_open_sprint_on_top_then_the_closed_ones_most_recent_first(): void
    {
        $this->sprint($this->project, ['name' => 'Sprint 1', 'starts_on' => '2026-09-08', 'ends_on' => '2026-09-21', 'closed_at' => now()]);
        $this->sprint($this->project, ['name' => 'Sprint 2', 'starts_on' => '2026-09-22', 'ends_on' => '2026-10-05', 'closed_at' => now()]);
        $aberta = $this->sprint($this->project, ['name' => 'Sprint 3']);
        $this->tarefa($this->project, $this->etapa($this->project, StageRole::DONE), ['sprint_id' => $aberta->id]);
        $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $aberta->id]);

        $this->actingAs($this->gestor)->get($this->url('/sprints'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sprints.0.name', 'Sprint 3')
                ->where('sprints.1.name', 'Sprint 2')
                ->where('sprints.2.name', 'Sprint 1')
                ->where('sprints.0.business_days', 10)
                ->where('sprints.0.tasks_done', 1)
                ->where('sprints.0.tasks_total', 2)
                ->where('sprints.1.closed_at', '06/10/2026')
                ->where('proposal', null)
                ->where('closing.next', ['name' => 'Sprint 4', 'goal' => '', 'starts_on' => '2026-10-20', 'ends_on' => '2026-11-02'])
                ->has('closing.tasks', 1));
    }

    public function test_task_filters_and_search_by_key(): void
    {
        $aberta = $this->sprint($this->project);
        $dev = User::factory()->developer()->create();
        $todo = $this->etapa($this->project, StageRole::SPRINT);
        $this->tarefa($this->project, $todo, ['sprint_id' => $aberta->id, 'developer_id' => $dev->id, 'estimated_hours' => 8, 'title' => 'Login']);
        $semDev = $this->tarefa($this->project, $todo, ['sprint_id' => $aberta->id, 'title' => 'Relatório']);
        $key = "{$this->project->key_prefix}-{$semDev->number}";

        $this->actingAs($this->gestor)->get($this->url('/sprints?quick=no_developer'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('selected.tasks', 1)->where('selected.tasks.0.key', $key));
        $this->actingAs($this->gestor)->get($this->url("/sprints?search={$key}"))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('selected.tasks', 1)->where('selected.tasks.0.title', 'Relatório'));
        $this->actingAs($this->gestor)->get($this->url("/sprints?developer_id={$dev->id}"))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('selected.tasks', 1)->where('selected.tasks.0.title', 'Login'));
    }

    public function test_create_refuses_end_before_start_and_a_second_open_sprint(): void
    {
        $this->actingAs($this->gestor)->post($this->url('/sprints'), ['name' => 'Sprint 1', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-01'])
            ->assertSessionHasErrors(['ends_on' => 'O fim não pode ser antes do início']);

        $this->actingAs($this->gestor)->post($this->url('/sprints'), ['name' => 'Sprint 1', 'goal' => '', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'])
            ->assertRedirect($this->url('/sprints?sprint='.Sprint::query()->value('id')));

        $this->actingAs($this->gestor)->post($this->url('/sprints'), ['name' => 'Sprint 2', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'])
            ->assertSessionHas('error', 'A Sprint 1 está aberta neste projeto: encerre-a antes de criar outra.');
        $this->assertSame(1, Sprint::query()->count());
    }

    public function test_close_over_http_moves_tasks_and_redirects_to_the_next(): void
    {
        $aberta = $this->sprint($this->project, ['name' => 'Sprint 1']);
        $todo = $this->etapa($this->project, StageRole::SPRINT);
        $vai = $this->tarefa($this->project, $todo, ['sprint_id' => $aberta->id]);
        $volta = $this->tarefa($this->project, $todo, ['sprint_id' => $aberta->id]);

        $response = $this->actingAs($this->gestor)->post($this->url("/sprints/{$aberta->id}/encerrar"), [
            'destinations' => [$volta->id => 'backlog'],
            'create_next' => true,
            'next' => ['name' => 'Sprint 2', 'goal' => '', 'starts_on' => '2026-10-20', 'ends_on' => '2026-11-02'],
        ]);

        $nova = Sprint::query()->where('name', 'Sprint 2')->sole();
        $response->assertRedirect($this->url("/sprints?sprint={$nova->id}"))
            ->assertSessionHas('success', 'Sprint 1 encerrada. Sprint 2 aberta com 1 tarefas. 1 voltaram ao Backlog.');
        $this->assertSame($nova->id, $vai->fresh()->sprint_id);
        $this->assertNull($volta->fresh()->sprint_id);
    }

    public function test_close_validates_the_next_sprint_fields_and_destinations(): void
    {
        $aberta = $this->sprint($this->project);

        $this->actingAs($this->gestor)->post($this->url("/sprints/{$aberta->id}/encerrar"), [
            'destinations' => [1 => 'lixo'],
            'create_next' => true,
            'next' => ['name' => '', 'starts_on' => '2026-10-20', 'ends_on' => '2026-10-10'],
        ])->assertSessionHasErrors(['destinations.1', 'next.name', 'next.ends_on' => 'O fim não pode ser antes do início']);

        $this->assertNull($aberta->fresh()->closed_at);
    }

    public function test_reopen_with_another_open_sprint_is_a_toast_error(): void
    {
        $antiga = $this->sprint($this->project, ['name' => 'Sprint 4', 'closed_at' => now()]);
        $this->sprint($this->project, ['name' => 'Sprint 5']);

        $this->actingAs($this->gestor)->post($this->url("/sprints/{$antiga->id}/reabrir"))
            ->assertSessionHas('error', 'Encerre a Sprint 5 antes de reabrir a Sprint 4.');
    }

    public function test_putting_a_card_of_another_project_is_a_field_error(): void
    {
        $this->sprint($this->project);
        $alheio = $this->tarefa($this->projeto());

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas'), ['task_id' => $alheio->id])
            ->assertSessionHasErrors(['task_id' => 'O card não é deste projeto.']);
    }

    public function test_putting_a_card_without_open_sprint_creates_the_proposed_one(): void
    {
        $task = $this->tarefa($this->project);

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas'), [
            'task_id' => $task->id,
            'new_sprint' => ['name' => 'Sprint 1', 'goal' => '', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'],
        ])->assertSessionHas('success');

        $this->assertSame(Sprint::query()->sole()->id, $task->fresh()->sprint_id);
    }

    public function test_estimate_rejects_zero_and_accepts_empty_to_clear(): void
    {
        $task = $this->tarefa($this->project, null, ['estimated_hours' => 8]);

        $this->actingAs($this->gestor)->put($this->url("/tarefas/{$task->id}/horas-previstas"), ['estimated_hours' => 0])
            ->assertSessionHasErrors(['estimated_hours' => 'Informe horas maiores que zero']);
        $this->actingAs($this->gestor)->put($this->url("/tarefas/{$task->id}/horas-previstas"), ['estimated_hours' => null])
            ->assertSessionHas('success');

        $this->assertNull($task->fresh()->estimated_hours);
    }

    public function test_approving_without_proposal_is_a_toast_error(): void
    {
        $task = $this->tarefa($this->project);

        $this->actingAs($this->gestor)->post($this->url("/tarefas/{$task->id}/reestimativa/aprovar"))
            ->assertSessionHas('error', 'Não há reestimativa proposta nesta tarefa.');
    }
}
