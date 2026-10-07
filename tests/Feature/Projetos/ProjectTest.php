<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Services\ProjectService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Cadastro do projeto (F2 §01 a §04; P1–P3, P7, P10, G2). */
class ProjectTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'client_id' => Client::factory()->create()->id,
            'manager_id' => User::factory()->projectManager()->create()->id,
            'key_prefix' => 'SGM',
            'name' => 'Gestão de multas',
            'type' => 'delivery',
            'starts_on' => '2026-10-06',
            'due_on' => '2026-12-18',
            ...$overrides,
        ];
    }

    public function test_project_manager_creates_a_project_with_the_default_board_and_defaults(): void
    {
        $gestor = User::factory()->projectManager()->create();

        $this->actingAs($gestor)
            ->post('/projetos', $this->payload(['manager_id' => $gestor->id, 'key_prefix' => '  sgm ']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $project = Project::query()->sole();
        $this->assertSame('SGM', $project->key_prefix);
        $this->assertNull($project->finished_at);
        $this->assertTrue($project->require_pause_reason);
        $this->assertSame(10, $project->sprint_days);
        $this->assertSame(0, $project->members()->count());
        $this->assertSame(0, $project->areas()->count());
        $this->assertSame(
            [
                ['Backlog', StageRole::QUEUE, 1], ['To do', StageRole::SPRINT, 2], ['Doing', StageRole::DEVELOPMENT, 3],
                ['Paused', StageRole::PAUSED, 4], ['Code-review', StageRole::CODE_REVIEW, 5], ['In test', StageRole::TEST, 6],
                ['Done', StageRole::DONE, 7], ['Support', StageRole::SUPPORT, 8],
            ],
            $project->boardStages()->orderBy('position')->get()->map(fn ($s) => [$s->name, $s->stage_role_id, $s->position])->all(),
        );

        $log = AuditLog::query()->where('auditable_type', 'project')->sole();
        $this->assertSame(['created', 'success', $gestor->id], [$log->action, $log->outcome, $log->user_id]);
        $this->assertSame([null, 'SGM'], $log->changes['key_prefix']);
        $this->assertSame([null, ['Backlog', 'To do', 'Doing', 'Paused', 'Code-review', 'In test', 'Done', 'Support']], $log->changes['board_stages']);
    }

    public function test_a_repeated_prefix_is_refused_regardless_of_case_naming_the_owner(): void
    {
        $this->projeto(['key_prefix' => 'SGM', 'name' => 'Gestão de multas']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/projetos', $this->payload(['key_prefix' => 'sgm', 'name' => 'Outro']))
            ->assertSessionHasErrors(['key_prefix' => 'O prefixo SGM já é do projeto Gestão de multas.']);

        $this->assertSame(1, Project::query()->count());
    }

    public function test_the_database_unique_refuses_a_repeated_prefix_that_slipped_past_validation(): void
    {
        $this->projeto(['key_prefix' => 'SGM', 'name' => 'Gestão de multas']);
        $this->actingAs(User::factory()->admin()->create());

        try {
            app(ProjectService::class)->create([
                'client_id' => Client::factory()->create()->id,
                'manager_id' => User::factory()->projectManager()->create()->id,
                'key_prefix' => 'SGM',
                'name' => 'Corrida',
                'starts_on' => '2026-10-06',
                'due_on' => null,
            ]);
            $this->fail('O prefixo repetido passou.');
        } catch (ValidationException $e) {
            $this->assertSame(['O prefixo SGM já é do projeto Gestão de multas.'], $e->errors()['key_prefix']);
        }

        $this->assertSame(1, Project::query()->count());
        $this->assertSame(8, DB::table('board_stages')->count(), 'o quadro do projeto recusado não ficou');
    }

    public function test_an_invalid_prefix_is_refused(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['SG M', 'ÇAB', 'ABCDEFGHIJK', ''] as $prefix) {
            $this->actingAs($admin)
                ->post('/projetos', $this->payload(['key_prefix' => $prefix]))
                ->assertSessionHasErrors('key_prefix');
        }

        $this->assertSame(0, Project::query()->count());
    }

    public function test_a_delivery_project_needs_a_due_date_not_before_the_start(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/projetos', $this->payload(['due_on' => '']))
            ->assertSessionHasErrors('due_on');

        $this->actingAs($admin)
            ->post('/projetos', $this->payload(['due_on' => '2026-10-05']))
            ->assertSessionHasErrors(['due_on' => 'A entrega não pode ser antes do início.']);

        $this->assertSame(0, Project::query()->count());
    }

    public function test_a_continuous_product_is_saved_without_a_due_date_even_if_one_is_sent(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/projetos', $this->payload(['type' => 'continuous', 'due_on' => '2026-12-18']))
            ->assertSessionHasNoErrors();

        $this->assertNull(Project::query()->sole()->due_on);
    }

    public function test_an_inactive_client_and_a_manager_without_the_role_are_refused(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/projetos', $this->payload(['client_id' => Client::factory()->inactive()->create()->id]))
            ->assertSessionHasErrors('client_id');

        $this->actingAs($admin)
            ->post('/projetos', $this->payload(['manager_id' => User::factory()->developer()->create()->id]))
            ->assertSessionHasErrors('manager_id');

        $this->actingAs($admin)
            ->post('/projetos', $this->payload(['manager_id' => User::factory()->projectManager()->inactive()->create()->id]))
            ->assertSessionHasErrors('manager_id');

        $this->assertSame(0, Project::query()->count());
    }

    public function test_editing_keeps_the_client_and_saves_the_rest_with_audit(): void
    {
        $project = $this->projeto(['key_prefix' => 'SGM', 'name' => 'Multas']);
        $novoGestor = User::factory()->admin()->create();
        $clienteOriginal = $project->client_id;

        $this->actingAs($novoGestor)
            ->put("/projetos/{$project->id}", $this->payload([
                'client_id' => Client::factory()->create()->id,
                'manager_id' => $novoGestor->id,
                'key_prefix' => 'GDM',
                'name' => 'Gestão de multas',
            ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $project->refresh();
        $this->assertSame($clienteOriginal, $project->client_id);
        $this->assertSame(['GDM', 'Gestão de multas', $novoGestor->id], [$project->key_prefix, $project->name, $project->manager_id]);
        $this->assertSame('2026-12-18', $project->due_on->toDateString());

        $log = AuditLog::query()->where('auditable_type', 'project')->where('action', 'updated')->sole();
        $this->assertSame(['SGM', 'GDM'], $log->changes['key_prefix']);
        $this->assertArrayNotHasKey('client_id', $log->changes);
    }

    public function test_the_prefix_freezes_after_the_first_task(): void
    {
        $project = $this->projeto(['key_prefix' => 'SGM']);
        $admin = User::factory()->admin()->create();
        $this->tarefa($project);
        $payload = $this->payload(['manager_id' => $project->manager_id, 'key_prefix' => 'NOVO', 'name' => 'Renomeado']);

        $this->actingAs($admin)
            ->put("/projetos/{$project->id}", $payload)
            ->assertSessionHasErrors(['key_prefix' => 'O prefixo não muda depois da primeira tarefa do projeto.']);

        $project->refresh();
        $this->assertSame('SGM', $project->key_prefix);
        $this->assertNotSame('Renomeado', $project->name, 'nada da edição recusada foi gravado');
    }

    public function test_the_prefix_still_changes_while_the_project_has_no_task(): void
    {
        $project = $this->projeto(['key_prefix' => 'SGM']);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/projetos/{$project->id}", $this->payload(['manager_id' => $project->manager_id, 'key_prefix' => 'NOVO']))
            ->assertSessionHasNoErrors();

        $this->assertSame('NOVO', $project->refresh()->key_prefix);
    }

    public function test_editing_keeps_an_inactive_current_manager_without_refusing(): void
    {
        $responsavel = User::factory()->projectManager()->inactive()->create();
        $project = $this->projeto(['manager_id' => $responsavel->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/projetos/{$project->id}", $this->payload(['manager_id' => $responsavel->id, 'key_prefix' => $project->key_prefix, 'name' => 'Novo nome']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Novo nome', $project->refresh()->name);
    }

    public function test_finishing_and_reopening_keep_the_tasks_and_a_second_finish_changes_nothing(): void
    {
        $project = $this->projeto();
        $doing = $this->etapa($project, StageRole::DEVELOPMENT);
        $task = $this->tarefa($project, $doing);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/projetos/{$project->id}/encerrar")->assertSessionHas('success');
        $finishedAt = $project->refresh()->finished_at;
        $this->assertNotNull($finishedAt);
        $this->assertSame($doing->id, $task->refresh()->board_stage_id);

        $this->travel(5)->minutes();
        $this->actingAs($admin)->post("/projetos/{$project->id}/encerrar");
        $this->assertEquals($finishedAt, $project->refresh()->finished_at);
        $this->assertSame(1, AuditLog::query()->where('action', 'finished')->count());

        $this->actingAs($admin)->post("/projetos/{$project->id}/reabrir")->assertSessionHas('success');
        $this->assertNull($project->refresh()->finished_at);
        $this->assertSame(1, AuditLog::query()->where('action', 'reopened')->count());
    }

    public function test_admin_deletes_a_never_used_project_with_its_board_areas_and_members(): void
    {
        $project = $this->projeto();
        $this->alocar($project, User::factory()->developer()->create());
        $project->areas()->create(['name' => 'Admin']);

        $this->actingAs(User::factory()->admin()->create())
            ->delete("/projetos/{$project->id}")
            ->assertSessionHas('success');

        $this->assertModelMissing($project);
        $this->assertSame(0, DB::table('board_stages')->count());
        $this->assertSame(0, DB::table('project_members')->count());
        $this->assertSame(0, DB::table('project_areas')->count());
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'project', 'auditable_id' => $project->id, 'action' => 'deleted']);
    }

    public function test_a_used_project_is_not_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $comTarefa = $this->projeto();
        $this->tarefa($comTarefa);
        $comSprint = $this->projeto();
        $comSprint->sprints()->create(['name' => 'Sprint 1', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19']);
        $comPacote = $this->projeto();
        $comPacote->packages()->create(['name' => 'v1.0']);

        foreach ([$comTarefa, $comSprint, $comPacote] as $project) {
            $this->actingAs($admin)
                ->delete("/projetos/{$project->id}")
                ->assertSessionHas('error', 'Projeto já usado: encerre em vez de excluir.');

            $this->assertModelExists($project);
            $this->assertSame(8, $project->boardStages()->count());
        }

        $this->assertSame(0, AuditLog::query()->where('action', 'deleted')->count());
    }
}
