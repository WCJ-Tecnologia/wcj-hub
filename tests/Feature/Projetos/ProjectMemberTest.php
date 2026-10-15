<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Pessoas do projeto (F2 §02 a §04; P5, C14, N11): alocar, tirar e o que a saída faz nas tarefas. */
class ProjectMemberTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
    }

    public function test_the_manager_allocates_people_and_they_see_the_project_at_once(): void
    {
        $dev = User::factory()->developer()->create();
        $outroGestor = User::factory()->projectManager()->create();

        $this->actingAs($dev)->get("/projetos/{$this->project->id}/configuracao")->assertForbidden();

        $this->actingAs($this->gestor)
            ->post("/projetos/{$this->project->id}/pessoas", ['user_ids' => [$dev->id, $outroGestor->id]])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame([$dev->id, $outroGestor->id], $this->project->members()->orderBy('id')->pluck('user_id')->all());
        $this->assertSame(2, AuditLog::query()->where('auditable_type', 'project_member')->where('action', 'created')->count());

        $this->actingAs($dev)->get("/projetos/{$this->project->id}/configuracao")->assertOk();
        // O gestor alocado gere como o responsável (P1, P5).
        $this->actingAs($outroGestor)->put("/projetos/{$this->project->id}/padroes", ['require_pause_reason' => false, 'sprint_days' => 5])->assertSessionHasNoErrors();
    }

    public function test_who_cannot_be_allocated_is_refused_and_nothing_changes(): void
    {
        $jaAlocado = User::factory()->developer()->create();
        $this->alocar($this->project, $jaAlocado);

        $recusados = [
            'comercial' => User::factory()->sales()->create()->id,
            'desativado' => User::factory()->developer()->inactive()->create()->id,
            'já alocado' => $jaAlocado->id,
            'inexistente' => 999999,
        ];

        foreach ($recusados as $caso => $id) {
            $this->actingAs($this->gestor)
                ->post("/projetos/{$this->project->id}/pessoas", ['user_ids' => [$id]])
                ->assertSessionHasErrors('user_ids.0', null, 'default');
            $this->assertSame(1, $this->project->members()->count(), $caso);
        }
        $this->actingAs($this->gestor)->post("/projetos/{$this->project->id}/pessoas", ['user_ids' => []])->assertSessionHasErrors('user_ids');

        $this->assertSame(1, $this->project->members()->count());
    }

    public function test_the_same_person_twice_in_one_request_is_refused(): void
    {
        $dev = User::factory()->developer()->create();

        $this->actingAs($this->gestor)
            ->post("/projetos/{$this->project->id}/pessoas", ['user_ids' => [$dev->id, $dev->id]])
            ->assertSessionHasErrors(['user_ids.0', 'user_ids.1']);

        $this->assertSame(0, $this->project->members()->count());
    }

    public function test_removing_a_person_clears_dev_and_qa_of_her_tasks_in_the_same_operation(): void
    {
        $dev = User::factory()->developer()->create();
        $outro = User::factory()->developer()->create();
        $this->alocar($this->project, $dev, $outro);
        $comoDev = $this->tarefa($this->project, null, ['developer_id' => $dev->id, 'qa_id' => $outro->id]);
        $comoQa = $this->tarefa($this->project, null, ['developer_id' => $outro->id, 'qa_id' => $dev->id]);
        $ambos = $this->tarefa($this->project, null, ['developer_id' => $dev->id, 'qa_id' => $dev->id]);
        // Tarefa de outro projeto em que a pessoa é dev: não se mexe (R15).
        $deFora = $this->tarefa($this->projeto(), null, ['developer_id' => $dev->id]);

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/pessoas/{$dev->id}")
            ->assertSessionHas('success', "{$dev->name} saiu do projeto. 2 tarefas ficaram sem dev e 2 sem QA.");

        $this->assertDatabaseMissing('project_members', ['project_id' => $this->project->id, 'user_id' => $dev->id]);
        $this->assertDatabaseHas('project_members', ['project_id' => $this->project->id, 'user_id' => $outro->id]);
        $this->assertSame([null, $outro->id], [$comoDev->fresh()->developer_id, $comoDev->fresh()->qa_id]);
        $this->assertSame([$outro->id, null], [$comoQa->fresh()->developer_id, $comoQa->fresh()->qa_id]);
        $this->assertSame([null, null], [$ambos->fresh()->developer_id, $ambos->fresh()->qa_id]);
        $this->assertSame($dev->id, $deFora->fresh()->developer_id);

        $this->assertDatabaseHas('audit_logs', ['action' => 'deleted', 'auditable_type' => 'project_member', 'user_id' => $this->gestor->id]);
        $this->assertSame(4, AuditLog::query()->where('auditable_type', 'task')->where('action', 'updated')->count(), 'cada tarefa que perdeu dev ou QA foi auditada');
        $this->actingAs($dev)->get("/projetos/{$this->project->id}/configuracao")->assertForbidden();
    }

    public function test_removing_who_is_not_in_the_project_changes_nothing(): void
    {
        $deFora = User::factory()->developer()->create();

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/pessoas/{$deFora->id}")
            ->assertNotFound();

        $this->assertSame(0, AuditLog::query()->where('auditable_type', 'project_member')->count());
    }

    public function test_the_people_tab_lists_members_with_counts_and_offers_only_who_can_enter(): void
    {
        $dev = User::factory()->developer()->create(['name' => 'Zé Dev']);
        $desativado = User::factory()->developer()->inactive()->create(['name' => 'Ana Fora']);
        $this->alocar($this->project, $dev, $desativado);
        $this->tarefa($this->project, null, ['developer_id' => $dev->id]);
        $this->tarefa($this->project, null, ['developer_id' => $dev->id, 'qa_id' => $dev->id]);
        $elegivel = User::factory()->developer()->create(['name' => 'Novo Dev']);
        User::factory()->sales()->create(['name' => 'Comercial']);
        User::factory()->developer()->inactive()->create(['name' => 'Inativo']);

        $this->actingAs($this->gestor)
            ->get("/projetos/{$this->project->id}/configuracao?aba=pessoas")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('projects/settings')
                ->where('filters.tab', 'pessoas')
                ->where('project.can.manage', true)
                ->where('members', fn ($members) => collect($members)->pluck('name')->all() === ['Ana Fora', 'Zé Dev'])
                ->where('members.1.tasks_as_dev', 2)
                ->where('members.1.tasks_as_qa', 1)
                ->where('members.0.active', false)
                ->where('options.people', fn ($people) => collect($people)->pluck('name')->contains('Novo Dev')
                    && ! collect($people)->pluck('name')->contains('Comercial')
                    && ! collect($people)->pluck('name')->contains('Inativo')
                    && ! collect($people)->pluck('id')->contains($dev->id)
                    && collect($people)->pluck('id')->contains($this->gestor->id)));

        $this->assertNotNull($elegivel);
    }
}
