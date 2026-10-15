<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Services\BoardStageService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Etapas do quadro (F2 §02 a §04; P8, P10–P12, P43, P45): criar, renomear, trocar papel, reordenar e remover. */
class BoardStageTest extends TestCase
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

    /** @return list<string> */
    private function ordem(): array
    {
        return $this->project->boardStages()->whereNull('archived_at')->orderBy('position')->pluck('name')->all();
    }

    private function movimento(BoardStage $to, ?BoardStage $from = null): void
    {
        $task = $this->tarefa($this->project, $to);
        TaskStageMovement::query()->create(['task_id' => $task->id, 'moved_by' => $this->gestor->id, 'from_board_stage_id' => $from?->id, 'to_board_stage_id' => $to->id]);
    }

    public function test_a_new_stage_goes_to_the_end_with_a_repeatable_role_only(): void
    {
        $this->actingAs($this->gestor)
            ->post("/projetos/{$this->project->id}/etapas", ['name' => ' Homologação ', 'stage_role_id' => StageRole::TEST])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $nova = $this->project->boardStages()->where('name', 'Homologação')->sole();
        $this->assertSame(9, $nova->position);
        $this->assertSame(StageRole::TEST, $nova->stage_role_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'created', 'auditable_type' => 'board_stage', 'auditable_id' => $nova->id]);

        foreach ([StageRole::QUEUE, StageRole::SPRINT, StageRole::SUPPORT, 99] as $papel) {
            $this->actingAs($this->gestor)
                ->post("/projetos/{$this->project->id}/etapas", ['name' => 'Outra', 'stage_role_id' => $papel])
                ->assertSessionHasErrors('stage_role_id');
        }
        $this->actingAs($this->gestor)->post("/projetos/{$this->project->id}/etapas", ['name' => str_repeat('a', 61), 'stage_role_id' => StageRole::DONE])->assertSessionHasErrors('name');
        $this->assertSame(9, $this->project->boardStages()->count());
    }

    public function test_renaming_is_always_allowed_and_the_role_changes_only_before_any_card_passes(): void
    {
        $doing = $this->etapa($this->project, StageRole::DEVELOPMENT);
        $done = $this->etapa($this->project, StageRole::DONE);
        $this->movimento($done);

        // Doing sem histórico: renomeia e vira Teste (há outra Desenvolvimento? não: é a única, então o papel não sai).
        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/etapas/{$doing->id}", ['name' => 'Em andamento', 'stage_role_id' => StageRole::TEST])
            ->assertSessionHas('error', BoardStageService::LAST_DEVELOPMENT);
        $this->assertSame(['Doing', StageRole::DEVELOPMENT], [$doing->fresh()->name, $doing->fresh()->stage_role_id]);

        // Com outra etapa de Desenvolvimento, o papel do Doing pode mudar.
        $this->project->boardStages()->create(['stage_role_id' => StageRole::DEVELOPMENT, 'name' => 'Dev 2', 'position' => 9]);
        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/etapas/{$doing->id}", ['name' => 'Em andamento', 'stage_role_id' => StageRole::TEST])
            ->assertSessionHas('success');
        $this->assertSame(['Em andamento', StageRole::TEST], [$doing->fresh()->name, $doing->fresh()->stage_role_id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'updated', 'auditable_type' => 'board_stage', 'auditable_id' => $doing->id]);

        // Done já recebeu um card: renomeia, mas o papel não muda (P8, P11).
        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/etapas/{$done->id}", ['name' => 'Concluído'])
            ->assertSessionHas('success');
        $this->assertSame('Concluído', $done->fresh()->name);
        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/etapas/{$done->id}", ['name' => 'Concluído', 'stage_role_id' => StageRole::TEST])
            ->assertSessionHas('error', BoardStageService::ROLE_LOCKED_BY_HISTORY);
        $this->assertSame(StageRole::DONE, $done->fresh()->stage_role_id);
    }

    public function test_queue_sprint_and_support_rename_but_never_change_role_nor_leave(): void
    {
        $backlog = $this->etapa($this->project, StageRole::QUEUE);

        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/etapas/{$backlog->id}", ['name' => 'Fila'])
            ->assertSessionHas('success');
        $this->assertSame('Fila', $backlog->fresh()->name);

        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/etapas/{$backlog->id}", ['name' => 'Fila', 'stage_role_id' => StageRole::DONE])
            ->assertSessionHas('error', BoardStageService::SINGLE_ROLE_LOCKED);
        $this->assertSame(StageRole::QUEUE, $backlog->fresh()->stage_role_id);

        foreach ([StageRole::QUEUE, StageRole::SPRINT, StageRole::SUPPORT] as $papel) {
            $etapa = $this->etapa($this->project, $papel);
            $this->actingAs($this->gestor)
                ->delete("/projetos/{$this->project->id}/etapas/{$etapa->id}")
                ->assertSessionHas('error', BoardStageService::SINGLE_ROLE_LOCKED);
            $this->assertNull($etapa->fresh()->archived_at);
        }
    }

    public function test_reordering_takes_the_whole_board_including_queue_sprint_and_support(): void
    {
        $ids = $this->project->boardStages()->orderBy('position')->pluck('id')->all();
        $nova = [...array_slice($ids, 1), $ids[0]]; // Support vai para o fim

        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/etapas/ordem", ['ids' => $nova])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(['Backlog', 'To do', 'Paused', 'Doing', 'Code-review', 'In test', 'Done', 'Support'], $this->ordem());
        $log = AuditLog::query()->where('auditable_type', 'project')->where('action', 'updated')->sole();
        $this->assertSame('Support', $log->changes['stage_order'][0][0]);
        $this->assertSame('Support', $log->changes['stage_order'][1][7]);

        foreach ([array_slice($nova, 1), [...$nova, $nova[0]], [...array_slice($nova, 1), 999999]] as $errada) {
            $this->actingAs($this->gestor)
                ->put("/projetos/{$this->project->id}/etapas/ordem", ['ids' => $errada])
                ->assertSessionHasErrors('ids');
        }
        $this->assertSame(['Backlog', 'To do', 'Paused', 'Doing', 'Code-review', 'In test', 'Done', 'Support'], $this->ordem());
    }

    public function test_removing_a_stage_without_cards_archives_it_and_it_leaves_the_list(): void
    {
        $paused = $this->etapa($this->project, StageRole::PAUSED);

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/etapas/{$paused->id}")
            ->assertSessionHas('success', 'Etapa Paused removida do quadro.');

        $this->assertNotNull($paused->fresh()->archived_at);
        $this->assertNotContains('Paused', $this->ordem());
        $this->assertDatabaseHas('audit_logs', ['action' => 'updated', 'auditable_type' => 'board_stage', 'auditable_id' => $paused->id]);

        $this->actingAs($this->gestor)
            ->get("/projetos/{$this->project->id}/configuracao?aba=etapas")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stages', fn ($stages) => collect($stages)->pluck('name')->all() === ['Support', 'Backlog', 'To do', 'Doing', 'Code-review', 'In test', 'Done']));

        // A etapa removida não volta pela edição (F2 §10.10).
        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/etapas/{$paused->id}", ['name' => 'Volta'])
            ->assertSessionHas('error', BoardStageService::STAGE_ARCHIVED);
    }

    public function test_removing_a_stage_with_cards_asks_the_destination_and_moves_each_card_with_a_movement(): void
    {
        $inTest = $this->etapa($this->project, StageRole::TEST);
        $backlog = $this->etapa($this->project, StageRole::QUEUE);
        $sprint = DB::table('sprints')->insertGetId(['project_id' => $this->project->id, 'name' => 'S1', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19', 'created_at' => now(), 'updated_at' => now()]);
        $a = $this->tarefa($this->project, $inTest, ['sprint_id' => $sprint]);
        $b = $this->tarefa($this->project, $inTest, ['sprint_id' => $sprint]);
        $arquivada = $this->tarefa($this->project, $inTest, ['archived_at' => now(), 'archive_reason' => 'não vai mais']);

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/etapas/{$inTest->id}")
            ->assertSessionHasErrors('destination_id');
        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/etapas/{$inTest->id}", ['destination_id' => $inTest->id])
            ->assertSessionHasErrors('destination_id');
        $deFora = $this->etapa($this->projeto(), StageRole::DONE);
        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/etapas/{$inTest->id}", ['destination_id' => $deFora->id])
            ->assertSessionHasErrors('destination_id');
        $this->assertNull($inTest->fresh()->archived_at);
        $this->assertSame($inTest->id, $a->fresh()->board_stage_id);

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/etapas/{$inTest->id}", ['destination_id' => $backlog->id])
            ->assertSessionHas('success', 'Etapa In test removida do quadro. 2 cards foram movidos.');

        $this->assertNotNull($inTest->fresh()->archived_at);
        foreach ([$a, $b] as $task) {
            $fresh = $task->fresh();
            $this->assertSame($backlog->id, $fresh->board_stage_id);
            $this->assertNull($fresh->sprint_id, 'destino de papel fila tira o card da sprint (P45)');
            $this->assertDatabaseHas('task_stage_movements', [
                'task_id' => $task->id, 'moved_by' => $this->gestor->id, 'from_board_stage_id' => $inTest->id, 'to_board_stage_id' => $backlog->id, 'rework_origin_id' => null,
            ]);
            $this->assertDatabaseHas('audit_logs', ['action' => 'updated', 'auditable_type' => 'task', 'auditable_id' => $task->id]);
        }
        // Card arquivado não se mexe (P26).
        $this->assertSame($inTest->id, $arquivada->fresh()->board_stage_id);
    }

    public function test_moving_to_a_non_queue_destination_keeps_the_sprint(): void
    {
        $done = $this->etapa($this->project, StageRole::DONE);
        $review = $this->etapa($this->project, StageRole::CODE_REVIEW);
        $sprint = DB::table('sprints')->insertGetId(['project_id' => $this->project->id, 'name' => 'S1', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19', 'created_at' => now(), 'updated_at' => now()]);
        $task = $this->tarefa($this->project, $done, ['sprint_id' => $sprint]);

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/etapas/{$done->id}", ['destination_id' => $review->id])
            ->assertSessionHas('success');

        $this->assertSame([$review->id, $sprint], [$task->fresh()->board_stage_id, $task->fresh()->sprint_id]);
    }

    public function test_the_last_development_stage_cannot_be_removed(): void
    {
        $doing = $this->etapa($this->project, StageRole::DEVELOPMENT);

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/etapas/{$doing->id}")
            ->assertSessionHas('error', BoardStageService::LAST_DEVELOPMENT);
        $this->assertNull($doing->fresh()->archived_at);

        $this->project->boardStages()->create(['stage_role_id' => StageRole::DEVELOPMENT, 'name' => 'Dev 2', 'position' => 9]);
        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/etapas/{$doing->id}")
            ->assertSessionHas('success');
        $this->assertNotNull($doing->fresh()->archived_at);
    }

    public function test_a_stage_of_another_project_is_not_found_through_this_project(): void
    {
        $deFora = $this->etapa($this->projeto(), StageRole::PAUSED);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/projetos/{$this->project->id}/etapas/{$deFora->id}", ['name' => 'Invasão'])
            ->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())
            ->delete("/projetos/{$this->project->id}/etapas/{$deFora->id}")
            ->assertNotFound();

        $this->assertSame(['Paused', null], [$deFora->fresh()->name, $deFora->fresh()->archived_at]);
    }

    public function test_the_stages_tab_shows_cards_per_stage_and_what_is_locked(): void
    {
        $doing = $this->etapa($this->project, StageRole::DEVELOPMENT);
        $backlog = $this->etapa($this->project, StageRole::QUEUE);
        $this->tarefa($this->project, $doing);
        $this->movimento($doing, $backlog);
        $this->tarefa($this->project, $doing, ['archived_at' => now(), 'archive_reason' => 'x']);

        $this->actingAs($this->gestor)
            ->get("/projetos/{$this->project->id}/configuracao?aba=etapas")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stages', function ($stages) use ($doing, $backlog) {
                    $byId = collect($stages)->keyBy('id');

                    return $byId[$doing->id]['cards'] === 2
                        && $byId[$doing->id]['role_locked'] === true && $byId[$doing->id]['removable'] === true
                        && $byId[$backlog->id]['cards'] === 0
                        && $byId[$backlog->id]['role_locked'] === true && $byId[$backlog->id]['removable'] === false
                        && collect($stages)->firstWhere('role_slug', 'done')['role_locked'] === false;
                })
                ->where('options.stage_roles', fn ($roles) => collect($roles)->pluck('id')->sort()->values()->all() === [3, 4, 5, 6, 7]));
    }
}
