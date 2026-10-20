<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Models\ValidationType;
use App\Domains\Projetos\Notifications\TaskReturned;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * As regras do card no quadro (F3 §01 e §04; escopo B3, PR 1), uma por teste, pela rota que a tela
 * chama: criar rápido (P7, P16, P17, T4), mover (P13, P14, P15, P28, P39, P40, P45, R5),
 * etiquetas (P18, P19), participante (P58) e card de suporte (P52). Nada trava o fluxo por falta
 * de dev, horas, PR ou parecer (premissa).
 */
class TaskServiceTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create(['daily_hours' => 8]);
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
        $this->alocar($this->project, $this->dev);
    }

    private function url(string $suffix = ''): string
    {
        return "/projetos/{$this->project->id}{$suffix}";
    }

    private function card(Task $task, string $suffix = ''): string
    {
        return $this->url("/cards/{$this->project->key_prefix}-{$task->number}{$suffix}");
    }

    /** @param  array<string, mixed>  $extra */
    private function mover(Task $task, int $role, array $extra = [], ?User $by = null): TestResponse
    {
        return $this->actingAs($by ?? $this->gestor)->post($this->card($task, '/mover'), ['stage_id' => $this->etapa($this->project, $role)->id, ...$extra]);
    }

    private function emDesenvolvimento(array $attributes = []): Task
    {
        return $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT), ['sprint_id' => $this->sprint($this->project)->id, ...$attributes]);
    }

    /* ---------------------------------------------------------------- criar */

    public function test_quick_card_is_born_in_the_backlog_with_only_the_title_and_the_next_key(): void
    {
        $this->project->forceFill(['last_task_number' => 16])->save();

        $this->actingAs($this->dev)->post($this->url('/cards'), ['title' => '  Tela de login  '])->assertSessionHasNoErrors()->assertRedirect();

        $task = Task::query()->sole();
        $this->assertSame([17, 'Tela de login', null, null, null, null], [$task->number, $task->title, $task->task_type_id, $task->task_priority_id, $task->project_area_id, $task->sprint_id]);
        $this->assertSame(StageRole::QUEUE, $task->boardStage->stage_role_id);
        $movement = TaskStageMovement::query()->sole();
        $this->assertSame([null, $task->board_stage_id, $this->dev->id, null], [$movement->from_board_stage_id, $movement->to_board_stage_id, $movement->moved_by, $movement->ai_authorization_id]);
        $this->assertSame(['created', 'success'], AuditLog::query()->where('auditable_type', 'task')->get(['action', 'outcome'])->map->only('action', 'outcome')->flatten()->all());
    }

    public function test_a_new_card_goes_to_the_end_of_the_manual_order(): void
    {
        $this->tarefa($this->project, null, ['position' => 7]);

        $this->actingAs($this->dev)->post($this->url('/cards'), ['title' => 'Novo']);

        $this->assertSame(8, Task::query()->where('title', 'Novo')->value('position'));
    }

    public function test_a_finished_project_takes_no_new_card(): void
    {
        $this->project->update(['finished_at' => now()]);

        $this->actingAs($this->gestor)->post($this->url('/cards'), ['title' => 'Novo'])->assertSessionHas('error', 'Projeto encerrado não recebe card novo.');

        $this->assertSame(0, Task::query()->count());
        $this->assertSame(0, (int) $this->project->fresh()->last_task_number);
    }

    public function test_the_title_is_required_and_up_to_200_chars(): void
    {
        $this->actingAs($this->dev)->post($this->url('/cards'), ['title' => ''])->assertSessionHasErrors('title');
        $this->actingAs($this->dev)->post($this->url('/cards'), ['title' => str_repeat('a', 201)])->assertSessionHasErrors('title');
        $this->assertSame(0, Task::query()->count());
    }

    /* ---------------------------------------------------------------- mover */

    public function test_moving_records_who_when_from_where_and_to_where_without_asking_for_dev_hours_or_pr(): void
    {
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT));

        $this->mover($task, StageRole::CODE_REVIEW, by: $this->dev)->assertSessionHasNoErrors();

        $movement = TaskStageMovement::query()->sole();
        $this->assertSame([$this->etapa($this->project, StageRole::SPRINT)->id, $this->etapa($this->project, StageRole::CODE_REVIEW)->id, $this->dev->id],
            [$movement->from_board_stage_id, $movement->to_board_stage_id, $movement->moved_by]);
        $this->assertSame('moved', AuditLog::query()->where('auditable_type', 'task')->value('action'));
    }

    public function test_from_backlog_to_doing_puts_on_the_open_sprint_with_the_two_movements(): void
    {
        $sprint = $this->sprint($this->project);
        $task = $this->tarefa($this->project);

        $this->mover($task, StageRole::DEVELOPMENT)->assertSessionHasNoErrors();

        $this->assertSame([$sprint->id, StageRole::DEVELOPMENT], [$task->fresh()->sprint_id, $task->fresh()->boardStage->stage_role_id]);
        $this->assertSame(
            [[StageRole::QUEUE, StageRole::SPRINT], [StageRole::SPRINT, StageRole::DEVELOPMENT]],
            TaskStageMovement::query()->orderBy('id')->with('fromBoardStage', 'toBoardStage')->get()
                ->map(fn ($m) => [$m->fromBoardStage->stage_role_id, $m->toBoardStage->stage_role_id])->all(),
        );
    }

    public function test_without_open_sprint_the_drag_needs_the_new_sprint_and_creates_it_in_the_same_transaction(): void
    {
        $task = $this->tarefa($this->project);

        $this->mover($task, StageRole::SPRINT)->assertSessionHas('error');
        $this->assertSame(StageRole::QUEUE, $task->fresh()->boardStage->stage_role_id);

        $this->mover($task, StageRole::SPRINT, ['new_sprint' => ['name' => 'Sprint 1', 'goal' => '', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-30']])
            ->assertSessionHasNoErrors();

        $sprint = Sprint::query()->sole();
        $this->assertSame(['Sprint 1', null], [$sprint->name, $sprint->closed_at]);
        $this->assertSame($sprint->id, $task->fresh()->sprint_id);
    }

    public function test_an_invalid_new_sprint_comes_back_with_the_field_errors_and_nothing_moves(): void
    {
        $task = $this->tarefa($this->project);

        $this->mover($task, StageRole::SPRINT, ['new_sprint' => ['name' => '', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-01']])
            ->assertSessionHasErrors(['new_sprint.name', 'new_sprint.ends_on']);

        $this->assertSame(0, Sprint::query()->count());
        $this->assertSame(StageRole::QUEUE, $task->fresh()->boardStage->stage_role_id);
    }

    public function test_back_to_the_backlog_leaves_the_sprint_and_is_not_rework(): void
    {
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $this->sprint($this->project)->id]);

        $this->mover($task, StageRole::QUEUE)->assertSessionHasNoErrors();

        $this->assertSame([null, StageRole::QUEUE], [$task->fresh()->sprint_id, $task->fresh()->boardStage->stage_role_id]);
        $this->assertSame(0, TaskStageMovement::query()->whereNotNull('rework_origin_id')->count());
    }

    public function test_pause_asks_for_the_reason_when_the_project_requires_it(): void
    {
        $task = $this->emDesenvolvimento();

        $this->mover($task, StageRole::PAUSED, by: $this->dev)->assertSessionHasErrors(['pause_reason' => 'Informe o motivo da pausa.']);
        $this->assertSame(StageRole::DEVELOPMENT, $task->fresh()->boardStage->stage_role_id);

        $this->mover($task, StageRole::PAUSED, ['pause_reason' => 'Esperando o cliente'], $this->dev)->assertSessionHasNoErrors();
        $this->assertSame('Esperando o cliente', TaskStageMovement::query()->latest('id')->value('reason_text'));
    }

    public function test_pause_without_reason_when_the_project_does_not_require_it(): void
    {
        $this->project->update(['require_pause_reason' => false]);
        $task = $this->emDesenvolvimento();

        $this->mover($task, StageRole::PAUSED, by: $this->dev)->assertSessionHasNoErrors();

        $this->assertSame(StageRole::PAUSED, $task->fresh()->boardStage->stage_role_id);
    }

    public function test_rework_asks_origin_and_reason_and_the_other_reason_asks_for_text(): void
    {
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::TEST));
        $outro = ReworkReason::query()->where('slug', ReworkReason::OTHER)->value('id');

        $this->mover($task, StageRole::DEVELOPMENT, by: $this->dev)->assertSessionHasErrors(['rework_origin_id', 'rework_reason_id']);
        $this->mover($task, StageRole::DEVELOPMENT, ['rework_origin_id' => ValidationType::QA, 'rework_reason_id' => $outro], $this->dev)
            ->assertSessionHasErrors(['rework_reason_text' => 'Descreva o motivo.']);

        $this->assertSame(StageRole::TEST, $task->fresh()->boardStage->stage_role_id);
        $this->assertSame(0, TaskStageMovement::query()->count());
    }

    public function test_rework_counts_clears_cross_test_resets_criteria_and_warns_the_developer(): void
    {
        Notification::fake();
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DONE), [
            'developer_id' => $this->dev->id, 'cross_tested_by' => $this->gestor->id, 'cross_tested_at' => now(),
        ]);
        $criterio = $task->acceptanceCriteria()->create(['number' => 1, 'description' => 'Login', 'test_result_id' => TestResult::PASSED]);
        $motivo = ReworkReason::query()->whereNull('slug')->value('id');

        $this->mover($task, StageRole::DEVELOPMENT, ['rework_origin_id' => ValidationType::QA, 'rework_reason_id' => $motivo])->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame([null, null], [$task->cross_tested_by, $task->cross_tested_at]);
        $this->assertSame(TestResult::PENDING, $criterio->fresh()->test_result_id);
        $volta = TaskStageMovement::query()->latest('id')->first();
        $this->assertSame([ValidationType::QA, $motivo], [$volta->rework_origin_id, $volta->rework_reason_id]);
        Notification::assertSentTo($this->dev, TaskReturned::class, function (TaskReturned $notice) use ($task) {
            $data = $notice->toArray($this->dev);

            return str_starts_with($data['text'], "{$this->project->key_prefix}-{$task->number} — {$task->title} voltou para Doing: origem QA")
                && str_ends_with($data['text'], "por {$this->gestor->name}.")
                && $notice->via($this->dev) === ['database', 'mail'];
        });
    }

    public function test_a_mistake_records_the_movement_without_rework_nor_notice(): void
    {
        Notification::fake();
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::CODE_REVIEW), ['developer_id' => $this->dev->id, 'cross_tested_by' => $this->gestor->id, 'cross_tested_at' => now()]);

        $this->mover($task, StageRole::SPRINT, ['mistake' => true])->assertSessionHasNoErrors();

        $this->assertSame(StageRole::SPRINT, $task->fresh()->boardStage->stage_role_id);
        $this->assertNull(TaskStageMovement::query()->latest('id')->value('rework_origin_id'));
        $this->assertNotNull($task->fresh()->cross_tested_by, 'engano não limpa o teste cruzado');
        Notification::assertNothingSent();
    }

    public function test_the_developer_who_returns_his_own_card_is_not_warned(): void
    {
        Notification::fake();
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::TEST), ['developer_id' => $this->dev->id]);

        $this->mover($task, StageRole::DEVELOPMENT, ['rework_origin_id' => ValidationType::CROSS_TEST, 'rework_reason_id' => ReworkReason::query()->whereNull('slug')->value('id')], $this->dev)
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    /** CR-1-06: o dev desativado segue no card (G2), mas o e-mail não vai para quem saiu. */
    public function test_a_deactivated_developer_is_not_warned_about_the_return(): void
    {
        Notification::fake();
        $this->dev->forceFill(['deactivated_at' => now()])->save();
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::TEST), ['developer_id' => $this->dev->id]);

        $this->mover($task, StageRole::DEVELOPMENT, ['rework_origin_id' => ValidationType::QA, 'rework_reason_id' => ReworkReason::query()->whereNull('slug')->value('id')])
            ->assertSessionHasNoErrors();

        $this->assertSame(StageRole::DEVELOPMENT, $task->fresh()->boardStage->stage_role_id, 'o movimento acontece');
        Notification::assertNothingSent();
    }

    public function test_first_entry_in_development_sets_the_due_date(): void
    {
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['developer_id' => $this->dev->id, 'estimated_hours' => 16]);

        $this->mover($task, StageRole::DEVELOPMENT)->assertSessionHasNoErrors();

        $this->assertNotNull($task->fresh()->due_on);
    }

    public function test_an_archived_card_does_not_move(): void
    {
        $task = $this->emDesenvolvimento(['archived_at' => now(), 'archive_reason' => 'Duplicado']);

        $this->mover($task, StageRole::CODE_REVIEW)->assertSessionHas('error', 'Card arquivado não muda: desarquive antes.');

        $this->assertSame(StageRole::DEVELOPMENT, $task->fresh()->boardStage->stage_role_id);
    }

    public function test_a_stage_of_another_project_is_refused(): void
    {
        $task = $this->emDesenvolvimento();
        $outra = $this->etapa($this->projeto(), StageRole::CODE_REVIEW);

        $this->actingAs($this->gestor)->post($this->card($task, '/mover'), ['stage_id' => $outra->id])->assertSessionHasErrors('stage_id');

        $this->assertSame(StageRole::DEVELOPMENT, $task->fresh()->boardStage->stage_role_id);
    }

    public function test_the_card_falls_where_it_was_dropped_and_hidden_cards_keep_their_place(): void
    {
        $review = $this->etapa($this->project, StageRole::CODE_REVIEW);
        [$a, $b, $c] = [
            $this->tarefa($this->project, $review, ['position' => 1]),
            $this->tarefa($this->project, $review, ['position' => 2]),
            $this->tarefa($this->project, $review, ['position' => 3]),
        ];
        $task = $this->emDesenvolvimento();

        // A tela filtrava o $a: a ordem que ela manda tem só os visíveis, com o card solto entre $b e $c.
        $this->mover($task, StageRole::CODE_REVIEW, ['sequence' => [$b->id, $task->id, $c->id]])->assertSessionHasNoErrors();

        $this->assertSame([$a->id, $b->id, $task->id, $c->id], Task::query()->where('board_stage_id', $review->id)->orderBy('position')->pluck('id')->all());
        $this->assertSame([1, 2, 3, 4], Task::query()->where('board_stage_id', $review->id)->orderBy('position')->pluck('position')->all());
    }

    public function test_reordering_in_the_same_column_records_no_movement(): void
    {
        $sprintStage = $this->etapa($this->project, StageRole::SPRINT);
        [$a, $b] = [$this->tarefa($this->project, $sprintStage, ['position' => 1]), $this->tarefa($this->project, $sprintStage, ['position' => 2])];

        $this->mover($b, StageRole::SPRINT, ['sequence' => [$b->id, $a->id]], $this->dev)->assertSessionHasNoErrors();

        $this->assertSame([2, 1], [$a->fresh()->position, $b->fresh()->position]);
        $this->assertSame(0, TaskStageMovement::query()->count());
    }

    /* ---------------------------------------------------------------- etiquetas e participante */

    public function test_tags_take_only_active_items_of_the_same_project(): void
    {
        $task = $this->emDesenvolvimento();
        $areaDeOutro = $this->projeto()->areas()->create(['name' => 'Outra']);
        $desativada = $this->project->areas()->create(['name' => 'Velha']);
        $desativada->forceFill(['deactivated_at' => now()])->save();
        $tipoDeOutro = $this->tipo($this->projeto(), 'development');

        $this->actingAs($this->dev)->patch($this->card($task), ['area_id' => $areaDeOutro->id])->assertSessionHasErrors('area_id');
        $this->actingAs($this->dev)->patch($this->card($task), ['area_id' => $desativada->id])->assertSessionHasErrors('area_id');
        $this->actingAs($this->dev)->patch($this->card($task), ['type_id' => $tipoDeOutro])->assertSessionHasErrors('type_id');

        $area = $this->project->areas()->create(['name' => 'Financeiro']);
        $this->actingAs($this->dev)->patch($this->card($task), ['area_id' => $area->id, 'type_id' => $this->tipo($this->project, 'development'), 'priority' => 'urgent'])
            ->assertSessionHasNoErrors();
        $this->assertSame([$area->id, $this->tipo($this->project, 'development'), 'urgent'], [$task->fresh()->project_area_id, $task->fresh()->task_type_id, $task->fresh()->taskPriority->slug]);

        $this->actingAs($this->dev)->patch($this->card($task), ['area_id' => null])->assertSessionHasNoErrors();
        $this->assertNull($task->fresh()->project_area_id);
        $this->actingAs($this->dev)->patch($this->card($task), ['priority' => null])->assertSessionHasErrors('priority');
    }

    public function test_support_type_by_the_tag_needs_the_request_fields(): void
    {
        $task = $this->emDesenvolvimento();

        $this->actingAs($this->dev)->patch($this->card($task), ['type_id' => $this->tipo($this->project, 'support')])->assertSessionHasErrors('type_id');

        $this->assertSame($this->tipo($this->project, 'improvement'), $task->fresh()->task_type_id);
    }

    public function test_participants_are_allocated_people_other_than_dev_and_qa(): void
    {
        $task = $this->emDesenvolvimento(['developer_id' => $this->dev->id]);
        $fora = User::factory()->developer()->create();
        $qa = User::factory()->developer()->create();
        $this->alocar($this->project, $qa);

        $this->actingAs($this->dev)->post($this->card($task, '/participantes'), ['user_id' => $fora->id, 'add' => true])->assertSessionHasErrors('user_id');
        $this->actingAs($this->dev)->post($this->card($task, '/participantes'), ['user_id' => $this->dev->id, 'add' => true])->assertSessionHasErrors('user_id');
        $this->actingAs($this->dev)->post($this->card($task, '/participantes'), ['user_id' => $qa->id, 'add' => true])->assertSessionHasNoErrors();
        $this->actingAs($this->dev)->post($this->card($task, '/participantes'), ['user_id' => $qa->id, 'add' => true])->assertSessionHasNoErrors();
        $this->assertSame([$qa->id], $task->fresh()->participants->pluck('id')->all());

        $this->actingAs($this->dev)->post($this->card($task, '/participantes'), ['user_id' => $qa->id, 'add' => false])->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('task_participants')->count());
    }

    /* ---------------------------------------------------------------- card de suporte */

    public function test_support_card_is_born_in_support_out_of_sprint_with_who_asked(): void
    {
        $contato = Contact::factory()->create(['client_id' => $this->project->client_id]);
        $this->sprint($this->project);

        $this->actingAs($this->gestor)->post($this->url('/cards/suporte'), [
            'title' => 'Relatório não abre', 'requester_contact_id' => $contato->id, 'contact_channel_id' => 1,
            'requested_at' => '2026-10-15 09:30', 'priority' => 'high',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $task = Task::query()->sole();
        $this->assertSame([StageRole::SUPPORT, null, 'support', 'high', $contato->id], [
            $task->boardStage->stage_role_id, $task->sprint_id, $task->taskType->slug, $task->taskPriority->slug, $task->requester_contact_id,
        ]);
        $this->assertSame('2026-10-15 12:30:00', $task->requested_at->toDateTimeString(), 'quando, gravado em UTC');
    }

    public function test_support_card_takes_only_an_active_contact_of_the_project_client(): void
    {
        $deOutroCliente = Contact::factory()->create();

        $this->actingAs($this->gestor)->post($this->url('/cards/suporte'), [
            'title' => 'X', 'requester_contact_id' => $deOutroCliente->id, 'contact_channel_id' => 1, 'requested_at' => '2026-10-15 09:30', 'priority' => 'normal',
        ])->assertSessionHasErrors('requester_contact_id');

        $this->assertSame(0, Task::query()->count());
    }
}
