<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Services\SprintService;
use App\Domains\Projetos\Services\SprintTaskService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** SprintService (F5 §01, §04; P45, P46, P50, P51, P52): criar, pôr, tirar, encerrar, reabrir e excluir. */
class SprintServiceTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private SprintService $sprints;

    private Project $project;

    private User $gestor;

    private const SPRINT = ['name' => 'Sprint 1', 'goal' => 'Login novo', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->sprints = app(SprintService::class);
        $this->gestor = User::factory()->projectManager()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id, 'sprint_days' => 10]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_proposal_is_sprint_n_from_today_with_the_project_duration(): void
    {
        Carbon::setTestNow('2026-10-06 12:00:00');
        $this->sprint($this->project, ['closed_at' => now()]);

        $this->assertSame(
            ['name' => 'Sprint 2', 'goal' => '', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'],
            $this->sprints->proposal($this->project),
        );
    }

    public function test_creates_one_open_sprint_and_refuses_a_second(): void
    {
        $sprint = $this->sprints->create($this->project, self::SPRINT, $this->gestor);

        $this->assertNull($sprint->closed_at);
        $this->assertSame('Login novo', $sprint->goal);
        $this->assertSame(1, AuditLog::query()->where('auditable_type', 'sprint')->where('action', 'created')->count());

        try {
            $this->sprints->create($this->project, [...self::SPRINT, 'name' => 'Sprint 2'], $this->gestor);
            $this->fail('Criou a segunda sprint aberta.');
        } catch (DomainException $e) {
            $this->assertSame('A Sprint 1 está aberta neste projeto: encerre-a antes de criar outra.', $e->getMessage());
        }
        $this->assertSame(1, $this->project->sprints()->count());
    }

    public function test_repeated_name_in_the_project_is_a_field_error(): void
    {
        $this->sprint($this->project, ['name' => 'Sprint 4', 'closed_at' => now()]);

        try {
            $this->sprints->create($this->project, [...self::SPRINT, 'name' => 'Sprint 4'], $this->gestor);
            $this->fail('Gravou o nome repetido.');
        } catch (ValidationException $e) {
            $this->assertSame(['name' => ['Já existe a Sprint 4 neste projeto']], $e->errors());
        }

        // Em outro projeto o nome pode repetir.
        $outro = $this->projeto();
        $this->assertSame('Sprint 4', $this->sprints->create($outro, [...self::SPRINT, 'name' => 'Sprint 4'], $this->gestor)->name);
    }

    public function test_empty_goal_is_saved_as_null(): void
    {
        $this->assertNull($this->sprints->create($this->project, [...self::SPRINT, 'goal' => '  '], $this->gestor)->goal);
    }

    public function test_update_changes_name_goal_and_dates_open_or_closed(): void
    {
        $sprint = $this->sprint($this->project, ['closed_at' => now()]);

        $this->sprints->update($sprint, ['name' => 'Fase 1', 'goal' => null, 'starts_on' => '2026-10-07', 'ends_on' => '2026-10-20'], $this->gestor);

        $sprint->refresh();
        $this->assertSame(['Fase 1', '2026-10-07', '2026-10-20'], [$sprint->name, $sprint->starts_on->toDateString(), $sprint->ends_on->toDateString()]);
        $log = AuditLog::query()->where('auditable_type', 'sprint')->where('action', 'updated')->sole();
        $this->assertSame(['Sprint 1', 'Fase 1'], $log->changes['name']);
    }

    public function test_adding_a_backlog_card_puts_it_in_the_open_sprint_and_in_to_do(): void
    {
        $sprint = $this->sprint($this->project);
        $task = $this->tarefa($this->project);

        $result = app(SprintTaskService::class)->addTask($task, $this->gestor);

        $task->refresh();
        $this->assertTrue($result['sprint']->is($sprint));
        $this->assertFalse($result['created']);
        $this->assertSame($sprint->id, $task->sprint_id);
        $this->assertSame($this->etapa($this->project, StageRole::SPRINT)->id, $task->board_stage_id);
        $movement = TaskStageMovement::query()->where('task_id', $task->id)->sole();
        $this->assertSame([$this->etapa($this->project, StageRole::QUEUE)->id, $task->board_stage_id, null], [$movement->from_board_stage_id, $movement->to_board_stage_id, $movement->rework_origin_id]);
        $this->assertSame($this->gestor->id, $movement->moved_by);
        $this->assertSame([null, $sprint->id], AuditLog::query()->where('auditable_type', 'task')->sole()->changes['sprint_id']);
    }

    public function test_without_open_sprint_the_proposed_one_is_created_with_the_card(): void
    {
        $task = $this->tarefa($this->project);

        $result = app(SprintTaskService::class)->addTask($task, $this->gestor, self::SPRINT);

        $this->assertTrue($result['created']);
        $this->assertSame('Sprint 1', $result['sprint']->name);
        $this->assertSame($result['sprint']->id, $task->fresh()->sprint_id);
    }

    public function test_without_open_sprint_and_without_proposal_it_refuses(): void
    {
        $task = $this->tarefa($this->project);

        try {
            app(SprintTaskService::class)->addTask($task, $this->gestor);
            $this->fail('Pôs o card sem sprint.');
        } catch (DomainException $e) {
            $this->assertSame("O projeto {$this->project->key_prefix} não tem sprint aberta. Crie a sprint pela tela.", $e->getMessage());
        }
        $this->assertNull($task->fresh()->sprint_id);
        $this->assertSame(0, Sprint::query()->count());
    }

    public function test_proposal_sent_when_a_sprint_is_already_open_joins_it(): void
    {
        $aberta = $this->sprint($this->project, ['name' => 'Sprint 5']);
        $task = $this->tarefa($this->project);

        $result = app(SprintTaskService::class)->addTask($task, $this->gestor, [...self::SPRINT, 'name' => 'Sprint 6']);

        $this->assertTrue($result['joined_existing']);
        $this->assertTrue($result['sprint']->is($aberta));
        $this->assertSame(1, Sprint::query()->count());
    }

    public function test_support_card_is_only_linked_and_unlinked_without_changing_column(): void
    {
        $sprint = $this->sprint($this->project);
        $support = $this->etapa($this->project, StageRole::SUPPORT);
        $task = $this->tarefa($this->project, $support, ['task_type_id' => $this->tipo($this->project, TaskType::SUPPORT)]);

        app(SprintTaskService::class)->addTask($task, $this->gestor);
        $this->assertSame([$sprint->id, $support->id], [$task->fresh()->sprint_id, $task->fresh()->board_stage_id]);

        app(SprintTaskService::class)->removeTask($task, $this->gestor);
        $this->assertSame([null, $support->id], [$task->fresh()->sprint_id, $task->fresh()->board_stage_id]);
        $this->assertSame(0, TaskStageMovement::query()->count());
    }

    public function test_removing_a_card_sends_it_back_to_backlog_without_rework(): void
    {
        $sprint = $this->sprint($this->project);
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT), ['sprint_id' => $sprint->id]);

        app(SprintTaskService::class)->removeTask($task, $this->gestor);

        $task->refresh();
        $this->assertNull($task->sprint_id);
        $this->assertSame($this->etapa($this->project, StageRole::QUEUE)->id, $task->board_stage_id);
        $this->assertNull(TaskStageMovement::query()->sole()->rework_origin_id);
    }

    public function test_archived_card_is_not_put_in_the_sprint(): void
    {
        $this->sprint($this->project);
        $task = $this->tarefa($this->project, null, ['archived_at' => now(), 'archive_reason' => 'Duplicado']);

        $this->expectException(DomainException::class);
        app(SprintTaskService::class)->addTask($task, $this->gestor);
    }

    public function test_card_of_another_project_is_refused(): void
    {
        $this->sprint($this->project);
        $task = $this->tarefa($this->project);
        $outro = $this->projeto();
        $task->project_id = $outro->id; // a instância que chegaria por uma rota mal resolvida

        $this->expectException(DomainException::class);
        app(SprintTaskService::class)->addTask($task, $this->gestor);
    }

    public function test_closing_sends_each_pending_card_where_the_manager_chose_and_creates_the_next(): void
    {
        $sprint = $this->sprint($this->project, ['name' => 'Sprint 4']);
        $done = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DONE), ['sprint_id' => $sprint->id]);
        $doing = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT), ['sprint_id' => $sprint->id]);
        $todo = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $sprint->id]);
        $semEscolha = $this->tarefa($this->project, $this->etapa($this->project, StageRole::TEST), ['sprint_id' => $sprint->id]);
        $arquivada = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $sprint->id, 'archived_at' => now(), 'archive_reason' => 'x']);

        $next = $this->sprints->nextProposal($sprint);
        $this->assertSame(['name' => 'Sprint 2', 'goal' => '', 'starts_on' => '2026-10-20', 'ends_on' => '2026-11-02'], $next);

        $result = $this->sprints->close($sprint, [$doing->id => 'next', $todo->id => 'backlog'], [...$next, 'name' => 'Sprint 5'], $this->gestor);

        $this->assertNotNull($sprint->fresh()->closed_at);
        $nova = $result['next'];
        $this->assertSame(['Sprint 5', '2026-10-20', null], [$nova->name, $nova->starts_on->toDateString(), $nova->closed_at]);
        $this->assertSame([2, 1], [$result['next_count'], $result['backlog_count']]);
        // P51: o que chegou a Done fica na sprint em que foi feito.
        $this->assertSame($sprint->id, $done->fresh()->sprint_id);
        $this->assertSame($nova->id, $doing->fresh()->sprint_id);
        $this->assertSame($this->etapa($this->project, StageRole::DEVELOPMENT)->id, $doing->fresh()->board_stage_id);
        $this->assertSame($nova->id, $semEscolha->fresh()->sprint_id);
        $this->assertNull($todo->fresh()->sprint_id);
        $this->assertSame($this->etapa($this->project, StageRole::QUEUE)->id, $todo->fresh()->board_stage_id);
        $this->assertSame($sprint->id, $arquivada->fresh()->sprint_id);
        $this->assertSame(1, AuditLog::query()->where('auditable_type', 'sprint')->where('action', 'finished')->count());
        $this->assertSame(1, TaskStageMovement::query()->where('task_id', $todo->id)->count());
    }

    public function test_closing_with_cards_going_next_requires_the_next_sprint(): void
    {
        $sprint = $this->sprint($this->project);
        $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $sprint->id]);

        try {
            $this->sprints->close($sprint, [], null, $this->gestor);
            $this->fail('Encerrou sem próxima sprint.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('next', $e->errors());
        }
        $this->assertNull($sprint->fresh()->closed_at);
    }

    public function test_closing_without_pending_cards_may_skip_the_next_sprint(): void
    {
        $sprint = $this->sprint($this->project);
        $this->tarefa($this->project, $this->etapa($this->project, StageRole::DONE), ['sprint_id' => $sprint->id]);

        $result = $this->sprints->close($sprint, [], null, $this->gestor);

        $this->assertNull($result['next']);
        $this->assertSame(1, Sprint::query()->count());
    }

    public function test_closing_a_closed_sprint_is_refused(): void
    {
        $sprint = $this->sprint($this->project, ['closed_at' => now()]);

        $this->expectExceptionMessage('A Sprint 1 já foi encerrada.');
        $this->sprints->close($sprint, [], null, $this->gestor);
    }

    public function test_support_card_sent_to_backlog_on_close_stays_in_its_column(): void
    {
        $sprint = $this->sprint($this->project);
        $support = $this->etapa($this->project, StageRole::SUPPORT);
        $task = $this->tarefa($this->project, $support, ['sprint_id' => $sprint->id, 'task_type_id' => $this->tipo($this->project, TaskType::SUPPORT)]);

        $this->sprints->close($sprint, [$task->id => 'backlog'], null, $this->gestor);

        $this->assertSame([null, $support->id], [$task->fresh()->sprint_id, $task->fresh()->board_stage_id]);
    }

    public function test_reopen_only_without_another_open_sprint_and_without_moving_cards(): void
    {
        $antiga = $this->sprint($this->project, ['name' => 'Sprint 4', 'closed_at' => now()]);
        $aberta = $this->sprint($this->project, ['name' => 'Sprint 5']);
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $aberta->id]);

        try {
            $this->sprints->reopen($antiga, $this->gestor);
            $this->fail('Reabriu com outra aberta.');
        } catch (DomainException $e) {
            $this->assertSame('Encerre a Sprint 5 antes de reabrir a Sprint 4.', $e->getMessage());
        }

        $aberta->forceFill(['closed_at' => now()])->save();
        $this->sprints->reopen($antiga, $this->gestor);

        $this->assertNull($antiga->fresh()->closed_at);
        $this->assertSame($aberta->id, $task->fresh()->sprint_id);
        $this->assertSame(1, AuditLog::query()->where('auditable_type', 'sprint')->where('action', 'reopened')->count());
    }

    public function test_delete_only_the_open_sprint_without_cards(): void
    {
        $fechada = $this->sprint($this->project, ['closed_at' => now()]);
        try {
            $this->sprints->delete($fechada, $this->gestor);
            $this->fail('Excluiu a encerrada.');
        } catch (DomainException $e) {
            $this->assertSame('Só se exclui a sprint aberta.', $e->getMessage());
        }

        $aberta = $this->sprint($this->project, ['name' => 'Sprint 2']);
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $aberta->id]);
        try {
            $this->sprints->delete($aberta, $this->gestor);
            $this->fail('Excluiu a sprint com tarefa.');
        } catch (DomainException $e) {
            $this->assertSame('A Sprint 2 tem tarefas: não dá para excluir.', $e->getMessage());
        }

        app(SprintTaskService::class)->removeTask($task, $this->gestor);
        $this->sprints->delete($aberta, $this->gestor);

        $this->assertNull(Sprint::query()->find($aberta->id));
        $log = AuditLog::query()->where('auditable_type', 'sprint')->where('action', 'deleted')->sole();
        $this->assertSame(['Sprint 2', null], $log->changes['name']);
        $this->assertTrue(Sprint::query()->whereKey($fechada->id)->exists());
    }

    public function test_cards_moved_into_a_column_go_to_its_end(): void
    {
        $this->sprint($this->project);
        $todo = $this->etapa($this->project, StageRole::SPRINT);
        $this->tarefa($this->project, $todo, ['position' => 7]);
        $task = $this->tarefa($this->project);

        app(SprintTaskService::class)->addTask($task, $this->gestor);

        $this->assertSame(8, $task->fresh()->position);
        $this->assertInstanceOf(Task::class, $task);
    }
}
