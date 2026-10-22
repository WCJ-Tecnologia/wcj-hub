<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Services\SprintPlanningQuery;
use App\Domains\Projetos\Services\SprintTaskService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * *Montar sprint* do quadro (D-GER-35; F5 §01, §04): o gestor escolhe várias tarefas do Backlog e elas
 * entram juntas na sprint aberta, com o efeito do arrastar em cada uma (P45); sem sprint aberta, a
 * sprint proposta nasce na mesma ação (R5). O plano de cada card (dev, QA, horas previstas,
 * prioridade) é aplicado na mesma transação. Tudo ou nada, e só quem gere.
 */
class SprintEmLoteTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private const NOVA = ['name' => 'Sprint 1', 'goal' => '', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'];

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

    /** @return list<array{id: int}> */
    private function ids(int ...$ids): array
    {
        return array_map(fn (int $id) => ['id' => $id], $ids);
    }

    private function key(int $number): string
    {
        return "{$this->project->key_prefix}-{$number}";
    }

    public function test_batch_with_open_sprint_puts_every_task_in_to_do_like_the_drag(): void
    {
        $sprint = $this->sprint($this->project, ['name' => 'Sprint 3']);
        $fila = $this->etapa($this->project, StageRole::QUEUE);
        $todo = $this->etapa($this->project, StageRole::SPRINT);
        $ja = $this->tarefa($this->project, $todo, ['sprint_id' => $sprint->id, 'position' => 4]);
        $a = $this->tarefa($this->project);
        $b = $this->tarefa($this->project);

        $this->actingAs($this->gestor)->from($this->url('/quadro'))->post($this->url('/sprint-aberta/tarefas/lote'), ['tasks' => $this->ids($b->id, $a->id)])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->url('/quadro'))
            ->assertSessionHas('success', '2 tarefas entraram na Sprint 3.');

        foreach ([$a, $b] as $task) {
            $task->refresh();
            $this->assertSame([$sprint->id, $todo->id], [$task->sprint_id, $task->board_stage_id]);
            $movement = TaskStageMovement::query()->where('task_id', $task->id)->sole();
            $this->assertSame([$fila->id, $todo->id, null, $this->gestor->id], [$movement->from_board_stage_id, $movement->to_board_stage_id, $movement->rework_origin_id, $movement->moved_by]);
            $log = AuditLog::query()->where('auditable_type', 'task')->where('auditable_id', $task->id)->sole();
            $this->assertSame(['updated', 'success', $this->gestor->id, [null, $sprint->id]], [$log->action, $log->outcome, $log->user_id, $log->changes['sprint_id']]);
        }
        // Fim da coluna, pela ordem da chave (P13).
        $this->assertSame([$ja->position + 1, $ja->position + 2], [$a->position, $b->position]);
        $this->assertSame(1, Sprint::query()->count());
        $this->assertSame(0, AuditLog::query()->where('auditable_type', 'sprint')->count());
    }

    public function test_batch_without_open_sprint_creates_it_with_every_task_in_one_action(): void
    {
        $this->sprint($this->project, ['name' => 'Sprint 1', 'closed_at' => now()]);
        $tasks = [$this->tarefa($this->project), $this->tarefa($this->project), $this->tarefa($this->project)];

        $response = $this->actingAs($this->gestor)->from($this->url('/quadro'))->post($this->url('/sprint-aberta/tarefas/lote'), [
            'tasks' => $this->ids(...array_map(fn ($task) => $task->id, $tasks)),
            'new_sprint' => [...self::NOVA, 'name' => 'Sprint 2', 'goal' => 'Fechar o recurso'],
        ]);

        $nova = Sprint::query()->whereNull('closed_at')->sole();
        $response->assertRedirect($this->url('/quadro'))->assertSessionHas('success', 'Sprint 2 criada com 3 tarefas.');
        $this->assertSame(['Sprint 2', 'Fechar o recurso', '2026-10-06', '2026-10-19'], [$nova->name, $nova->goal, $nova->starts_on->toDateString(), $nova->ends_on->toDateString()]);
        foreach ($tasks as $task) {
            $this->assertSame($nova->id, $task->fresh()->sprint_id);
        }
        $log = AuditLog::query()->where('auditable_type', 'sprint')->sole();
        $this->assertSame(['created', $nova->id, $this->gestor->id], [$log->action, $log->auditable_id, $log->user_id]);
        $this->assertSame(3, AuditLog::query()->where('auditable_type', 'task')->where('action', 'updated')->count());
    }

    public function test_one_task_out_of_the_backlog_refuses_the_whole_batch_naming_each_one(): void
    {
        $closed = $this->sprint($this->project, ['name' => 'Sprint 1', 'closed_at' => now()]);
        $ok = $this->tarefa($this->project);
        $arquivada = $this->tarefa($this->project, null, ['archived_at' => now(), 'archive_reason' => 'não vai mais']);
        $emSprint = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $closed->id]);
        $fazendo = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT));

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), [
            'tasks' => $this->ids($fazendo->id, $ok->id, $emSprint->id, $arquivada->id),
            'new_sprint' => [...self::NOVA, 'name' => 'Sprint 2'],
        ])->assertSessionHasErrors(['tasks' => "Nada foi gravado: {$this->key($arquivada->number)} está arquivada; {$this->key($emSprint->number)} já está na Sprint 1; {$this->key($fazendo->number)} não está mais no Backlog."]);

        $this->assertSame(1, Sprint::query()->count());
        $this->assertNull($ok->fresh()->sprint_id);
        $this->assertSame(0, TaskStageMovement::query()->count());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->count());
    }

    public function test_task_of_another_project_is_refused_without_naming_it(): void
    {
        $this->sprint($this->project);
        $minha = $this->tarefa($this->project);
        $alheia = $this->tarefa($this->projeto());

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), ['tasks' => $this->ids($minha->id, $alheia->id)])
            ->assertSessionHasErrors(['tasks' => 'Alguma tarefa selecionada não é deste projeto.']);

        $this->assertNull($minha->fresh()->sprint_id);
        $this->assertNull($alheia->fresh()->sprint_id);
    }

    public function test_service_asserts_the_same_project_r15(): void
    {
        $alheia = $this->tarefa($this->projeto());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A tarefa não é deste projeto.');
        app(SprintTaskService::class)->addMany($this->project, [['id' => $alheia->id]], $this->gestor, self::NOVA);
    }

    public function test_payload_is_validated(): void
    {
        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), ['tasks' => []])
            ->assertSessionHasErrors(['tasks' => 'Selecione ao menos uma tarefa.']);

        $task = $this->tarefa($this->project);
        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), [
            'tasks' => $this->ids($task->id, $task->id),
            'new_sprint' => ['name' => '', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-01'],
        ])->assertSessionHasErrors(['tasks.0.id', 'new_sprint.name', 'new_sprint.ends_on' => 'O fim não pode ser antes do início']);

        $this->assertSame(0, Sprint::query()->count());
    }

    public function test_without_open_sprint_and_without_new_sprint_is_a_toast_error(): void
    {
        $task = $this->tarefa($this->project);

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), ['tasks' => $this->ids($task->id)])
            ->assertSessionHas('error', "O projeto {$this->project->key_prefix} não tem sprint aberta. Crie a sprint pela tela.");
        $this->assertNull($task->fresh()->sprint_id);
    }

    public function test_sprint_opened_meanwhile_receives_the_tasks_with_a_warning(): void
    {
        // A tela ainda mostrava os campos da nova, mas outra pessoa abriu a Sprint 1 antes (F5 §07).
        $aberta = $this->sprint($this->project, ['name' => 'Sprint 1']);
        $task = $this->tarefa($this->project);

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), ['tasks' => $this->ids($task->id), 'new_sprint' => [...self::NOVA, 'name' => 'Sprint 2']])
            ->assertSessionHas('warning', '1 tarefa entrou na Sprint 1, aberta agora por outra pessoa.');

        $this->assertSame(1, Sprint::query()->count());
        $this->assertSame($aberta->id, $task->fresh()->sprint_id);
    }

    public function test_repeated_sprint_name_comes_back_as_a_field_error_and_nothing_moves(): void
    {
        $this->sprint($this->project, ['name' => 'Sprint 1', 'closed_at' => now()]);
        $task = $this->tarefa($this->project);

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), ['tasks' => $this->ids($task->id), 'new_sprint' => self::NOVA])
            ->assertSessionHasErrors(['name' => 'Já existe a Sprint 1 neste projeto']);

        $this->assertNull($task->fresh()->sprint_id);
    }

    public function test_allocated_developer_and_outside_manager_get_403_with_one_denied_row(): void
    {
        $dev = User::factory()->developer()->create();
        $this->alocar($this->project, $dev);
        $task = $this->tarefa($this->project);

        $planned = ['tasks' => [['id' => $task->id, 'developer_id' => $dev->id, 'estimated_hours' => 8, 'priority' => 'urgent']], 'new_sprint' => self::NOVA];
        foreach ([$dev, User::factory()->projectManager()->create()] as $user) {
            foreach ([['tasks' => $this->ids($task->id)], $planned, ['tasks' => 'lixo']] as $payload) {
                $before = AuditLog::query()->max('id') ?? 0;
                $this->actingAs($user)->post($this->url('/sprint-aberta/tarefas/lote'), $payload)->assertForbidden();

                $rows = AuditLog::query()->where('id', '>', $before)->get();
                $this->assertCount(1, $rows);
                $this->assertSame(['denied', 'sprint', 'updated', $user->id], [$rows[0]->outcome, $rows[0]->auditable_type, $rows[0]->action, $rows[0]->user_id]);
            }
        }

        $this->assertNull($task->fresh()->sprint_id);
        $this->assertSame(0, Sprint::query()->count());
    }

    public function test_plan_applies_developer_qa_hours_and_priority_per_task(): void
    {
        $this->sprint($this->project, ['name' => 'Sprint 1', 'closed_at' => now()]);
        $dev = User::factory()->developer()->create(['daily_hours' => 8]);
        $qa = User::factory()->developer()->create();
        $this->alocar($this->project, $dev, $qa);
        $planejada = $this->tarefa($this->project);
        $intacta = $this->tarefa($this->project, null, ['estimated_hours' => 3, 'task_priority_id' => TaskPriority::LOW]);

        $this->actingAs($this->gestor)->from($this->url('/quadro'))->post($this->url('/sprint-aberta/tarefas/lote'), [
            'tasks' => [
                ['id' => $planejada->id, 'developer_id' => $dev->id, 'qa_id' => $qa->id, 'estimated_hours' => '12.5', 'priority' => 'urgent'],
                ['id' => $intacta->id],
            ],
            'new_sprint' => [...self::NOVA, 'name' => 'Sprint 2'],
        ])->assertSessionHasNoErrors()->assertSessionHas('success', 'Sprint 2 criada com 2 tarefas.');

        $planejada->refresh();
        $sprint = Sprint::query()->whereNull('closed_at')->sole();
        $this->assertSame(
            [$sprint->id, $dev->id, $qa->id, '12.50', TaskPriority::URGENT],
            [$planejada->sprint_id, $planejada->developer_id, $planejada->qa_id, $planejada->estimated_hours, $planejada->task_priority_id],
        );
        // Sem plano, só entra na sprint: horas e prioridade ficam como estavam.
        $intacta->refresh();
        $this->assertSame([$sprint->id, null, '3.00', TaskPriority::LOW], [$intacta->sprint_id, $intacta->developer_id, $intacta->estimated_hours, $intacta->task_priority_id]);

        // Auditoria por card, com o antes e o depois de cada mudança (G3).
        $logs = AuditLog::query()->where('auditable_type', 'task')->where('auditable_id', $planejada->id)->orderBy('id')->get();
        $this->assertSame([null, $sprint->id], $logs[0]->changes['sprint_id']);
        $this->assertSame([[null, $dev->id], [null, $qa->id], [TaskPriority::NORMAL, TaskPriority::URGENT]], [$logs[1]->changes['developer_id'], $logs[1]->changes['qa_id'], $logs[1]->changes['task_priority_id']]);
        $this->assertSame([null, '12.50'], $logs[2]->changes['estimated_hours']);
        $this->assertCount(3, $logs);
        $this->assertSame(1, AuditLog::query()->where('auditable_type', 'task')->where('auditable_id', $intacta->id)->count());
    }

    public function test_null_clears_developer_and_qa(): void
    {
        $this->sprint($this->project);
        $dev = User::factory()->developer()->create();
        $this->alocar($this->project, $dev);
        $task = $this->tarefa($this->project, null, ['developer_id' => $dev->id, 'qa_id' => $dev->id]);

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), ['tasks' => [['id' => $task->id, 'developer_id' => null, 'qa_id' => null]]])
            ->assertSessionHasNoErrors();

        $this->assertSame([null, null], [$task->fresh()->developer_id, $task->fresh()->qa_id]);
    }

    public function test_person_outside_the_project_is_refused_on_the_card_line_and_nothing_moves(): void
    {
        $alocado = User::factory()->developer()->create();
        $desativado = User::factory()->developer()->create(['deactivated_at' => now()]);
        $this->alocar($this->project, $alocado, $desativado);
        $deFora = User::factory()->developer()->create();
        $a = $this->tarefa($this->project);
        $b = $this->tarefa($this->project);

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), [
            'tasks' => [
                ['id' => $a->id, 'developer_id' => $alocado->id, 'qa_id' => $desativado->id],
                ['id' => $b->id, 'developer_id' => $deFora->id],
            ],
            'new_sprint' => self::NOVA,
        ])->assertSessionHasErrors([
            'tasks.0.qa_id' => 'O QA não está alocado neste projeto.',
            'tasks.1.developer_id' => 'O dev não está alocado neste projeto.',
        ]);

        $this->assertSame(0, Sprint::query()->count());
        $this->assertSame([null, null], [$a->fresh()->sprint_id, $a->fresh()->developer_id]);
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->count());
    }

    public function test_service_refuses_a_person_outside_the_project_and_undoes_the_whole_batch(): void
    {
        $deFora = User::factory()->developer()->create(['name' => 'Zé de Fora']);
        $a = $this->tarefa($this->project);
        $b = $this->tarefa($this->project);

        try {
            app(SprintTaskService::class)->addMany($this->project, [['id' => $a->id], ['id' => $b->id, 'developer_id' => $deFora->id]], $this->gestor, self::NOVA);
            $this->fail('Aceitou dev fora do projeto.');
        } catch (DomainException $e) {
            // Sem o nome: percorrendo ids, a recusa listaria todos os usuários (B9, SEC-24).
            $this->assertSame('A pessoa escolhida não está alocada neste projeto.', $e->getMessage());
        }

        // A sprint nova e a 1ª tarefa (já posta na sprint) voltam junto.
        $this->assertSame(0, Sprint::query()->count());
        $this->assertNull($a->fresh()->sprint_id);
        $this->assertSame(0, TaskStageMovement::query()->count());
    }

    public function test_plan_fields_are_validated_per_card(): void
    {
        $task = $this->tarefa($this->project);

        $this->actingAs($this->gestor)->post($this->url('/sprint-aberta/tarefas/lote'), [
            'tasks' => [['id' => $task->id, 'estimated_hours' => 0, 'priority' => 'altissima']],
            'new_sprint' => self::NOVA,
        ])->assertSessionHasErrors(['tasks.0.estimated_hours' => 'Informe horas maiores que zero', 'tasks.0.priority']);

        $this->assertNull($task->fresh()->sprint_id);
    }

    public function test_planning_query_lists_allocated_people_and_the_open_sprint_load(): void
    {
        $ana = User::factory()->developer()->create(['name' => 'Ana', 'daily_hours' => 8]);
        $bia = User::factory()->projectManager()->create(['name' => 'Bia', 'daily_hours' => null]);
        $saiu = User::factory()->developer()->create(['name' => 'Caio', 'deactivated_at' => now()]);
        $this->alocar($this->project, $bia, $ana, $saiu);
        User::factory()->developer()->create(['name' => 'Dora']);

        $semAberta = app(SprintPlanningQuery::class)->forProject($this->project);
        $this->assertSame([
            ['id' => $ana->id, 'name' => 'Ana', 'daily_hours' => 8.0, 'role' => 'developer'],
            ['id' => $bia->id, 'name' => 'Bia', 'daily_hours' => null, 'role' => 'project_manager'],
        ], $semAberta['people']);
        $this->assertNull($semAberta['open_sprint']);

        $aberta = $this->sprint($this->project, ['name' => 'Sprint 7']);
        $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $aberta->id, 'developer_id' => $ana->id, 'estimated_hours' => 10]);

        $comAberta = app(SprintPlanningQuery::class)->forProject($this->project)['open_sprint'];
        $this->assertSame(['id' => $aberta->id, 'name' => 'Sprint 7', 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'], array_diff_key($comAberta, ['capacity' => 1]));
        $this->assertSame(['id' => $ana->id, 'name' => 'Ana', 'daily_hours' => 8.0, 'remaining' => 10.0, 'capacity' => 80.0, 'difference' => 70.0], $comAberta['capacity']['developers'][0]);
    }
}
