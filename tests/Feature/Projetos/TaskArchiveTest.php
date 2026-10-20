<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Notifications\TaskAssigned;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Tirar o card do quadro (spec b3-arquivar-e-avisos: EXC-01, EXC-02, ARQ-01; F3 §03 e §04; P26,
 * P57; banco.md tasks "tirar do quadro"), pelas rotas que a ficha e o quadro chamam: arquivar com
 * motivo, desarquivar para o Backlog, excluir só o card nunca usado e a lista de arquivados.
 */
class TaskArchiveTest extends TestCase
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
        $this->project = $this->projeto(['manager_id' => $this->gestor->id, 'key_prefix' => 'SGM']);
        $this->alocar($this->project, $this->dev);
    }

    private function card(Task $task, string $suffix = ''): string
    {
        return "/projetos/{$this->project->id}/cards/{$this->project->key_prefix}-{$task->number}{$suffix}";
    }

    private function arquivar(Task $task, mixed $reason = 'Duplicado do SGM-3'): TestResponse
    {
        return $this->actingAs($this->gestor)->post($this->card($task, '/arquivar'), ['archive_reason' => $reason]);
    }

    /** O card como o TaskService cria: no Backlog, com o movimento de criação (from nulo). */
    private function novo(array $attributes = []): Task
    {
        $task = $this->tarefa($this->project, null, $attributes);
        TaskStageMovement::query()->create(['task_id' => $task->id, 'moved_by' => $this->dev->id, 'from_board_stage_id' => null, 'to_board_stage_id' => $task->board_stage_id]);

        return $task;
    }

    /** @return array<string, mixed> */
    private function ficha(Task $task, ?User $as = null): array
    {
        return $this->actingAs($as ?? $this->gestor)->get($this->card($task))->assertOk()->viewData('page')['props']['task'];
    }

    /* ---------------------------------------------------------------- arquivar */

    public function test_archiving_records_reason_who_and_when_and_keeps_stage_sprint_and_children(): void
    {
        $sprint = $this->sprint($this->project);
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT), ['sprint_id' => $sprint->id, 'position' => 4]);
        $filho = $this->tarefa($this->project, null, ['parent_task_id' => $task->id]);

        $this->arquivar($task, '  Cliente desistiu  ')->assertSessionHasNoErrors()->assertSessionHas('success', 'SGM-1 arquivado. Ele volta pelo filtro Arquivados do quadro.');

        $task->refresh();
        $this->assertSame(['Cliente desistiu', $this->gestor->id], [$task->archive_reason, $task->archived_by]);
        $this->assertNotNull($task->archived_at);
        $this->assertSame([$this->etapa($this->project, StageRole::DEVELOPMENT)->id, $sprint->id, 4], [$task->board_stage_id, $task->sprint_id, $task->position]);
        $this->assertSame($task->id, $filho->fresh()->parent_task_id, 'arquivar o pai não mexe nos filhos (P57)');

        $changes = AuditLog::query()->where('action', 'archived')->sole()->changes;
        $this->assertEqualsCanonicalizing(['archived_at', 'archive_reason', 'archived_by'], array_keys($changes), 'só o arquivamento muda');
        $this->assertSame([[null, 'Cliente desistiu'], [null, $this->gestor->id]], [$changes['archive_reason'], $changes['archived_by']]);
        $this->assertNull($changes['archived_at'][0]);
        $this->assertNotNull($changes['archived_at'][1]);
    }

    public function test_the_archived_card_leaves_the_board_and_the_late_mark_but_the_card_opens(): void
    {
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT), ['due_on' => '2020-01-06']);
        $this->assertTrue($this->ficha($task)['late'], 'antes de arquivar, atrasado');

        $this->arquivar($task);

        $board = $this->actingAs($this->gestor)->get("/projetos/{$this->project->id}/quadro")->viewData('page')['props'];
        $this->assertSame([], $board['cards']);
        $ficha = $this->ficha($task, $this->dev);
        $this->assertFalse($ficha['late'], 'sai das contas de atraso (P26)');
        $this->assertSame(['Duplicado do SGM-3', $this->gestor->name], [$ficha['archived']['reason'], $ficha['archived']['by']['name']]);
    }

    public function test_the_reason_is_required_and_up_to_500_chars(): void
    {
        $task = $this->tarefa($this->project);

        $this->arquivar($task, '')->assertSessionHasErrors('archive_reason');
        $this->arquivar($task, '   ')->assertSessionHasErrors('archive_reason');
        $this->arquivar($task, str_repeat('a', 501))->assertSessionHasErrors('archive_reason');
        $this->arquivar($task, str_repeat('a', 500))->assertSessionHasNoErrors();

        $this->assertSame(500, mb_strlen($task->fresh()->archive_reason));
    }

    public function test_an_archived_card_is_not_archived_again(): void
    {
        $task = $this->tarefa($this->project);
        $this->arquivar($task, 'Primeiro');

        $this->arquivar($task, 'Segundo')->assertSessionHas('error', 'Card arquivado não muda: desarquive antes.');

        $this->assertSame('Primeiro', $task->fresh()->archive_reason);
        $this->assertSame(1, AuditLog::query()->where('action', 'archived')->count());
    }

    /* ---------------------------------------------------------------- desarquivar */

    public function test_unarchiving_clears_the_archive_and_the_sprint_and_goes_to_the_end_of_the_backlog_without_rework(): void
    {
        $sprint = $this->sprint($this->project);
        $code = $this->etapa($this->project, StageRole::CODE_REVIEW);
        $task = $this->tarefa($this->project, $code, ['sprint_id' => $sprint->id]);
        $this->tarefa($this->project, null, ['position' => 9]);
        $this->arquivar($task);

        $this->actingAs($this->gestor)->post($this->card($task, '/desarquivar'))->assertSessionHas('success', 'SGM-1 desarquivado: está no fim do Backlog.');

        $task->refresh();
        $queue = $this->etapa($this->project, StageRole::QUEUE);
        $this->assertSame([null, null, null, null, $queue->id, 10], [$task->archived_at, $task->archive_reason, $task->archived_by, $task->sprint_id, $task->board_stage_id, $task->position]);
        $movement = TaskStageMovement::query()->where('task_id', $task->id)->sole();
        $this->assertSame([$code->id, $queue->id, $this->gestor->id, null, null], [$movement->from_board_stage_id, $movement->to_board_stage_id, $movement->moved_by, $movement->rework_origin_id, $movement->rework_reason_id]);

        $changes = AuditLog::query()->where('action', 'unarchived')->sole()->changes;
        $this->assertSame(
            [[$code->id, $queue->id], [$sprint->id, null], ['Duplicado do SGM-3', null], [$this->gestor->id, null]],
            [$changes['board_stage_id'], $changes['sprint_id'], $changes['archive_reason'], $changes['archived_by']],
        );
    }

    public function test_a_card_archived_in_the_backlog_comes_back_to_the_end_without_a_new_movement(): void
    {
        $task = $this->tarefa($this->project, null, ['position' => 1]);
        $this->tarefa($this->project, null, ['position' => 2]);
        $this->arquivar($task);

        $this->actingAs($this->gestor)->post($this->card($task, '/desarquivar'))->assertSessionHasNoErrors();

        $this->assertSame(3, $task->fresh()->position);
        $this->assertSame(0, TaskStageMovement::query()->where('task_id', $task->id)->count());
    }

    public function test_a_card_archived_in_a_stage_removed_later_comes_back_to_the_backlog(): void
    {
        $removida = BoardStage::query()->create(['project_id' => $this->project->id, 'name' => 'Homologar', 'stage_role_id' => StageRole::TEST, 'position' => 99, 'archived_at' => now()]);
        $task = $this->tarefa($this->project, $removida, ['archived_at' => now(), 'archive_reason' => 'Antigo']);

        $this->actingAs($this->gestor)->post($this->card($task, '/desarquivar'))->assertSessionHasNoErrors();

        $this->assertSame($this->etapa($this->project, StageRole::QUEUE)->id, $task->fresh()->board_stage_id);
    }

    public function test_a_card_that_is_not_archived_is_not_unarchived(): void
    {
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT));

        $this->actingAs($this->gestor)->post($this->card($task, '/desarquivar'))->assertSessionHas('error', 'O card não está arquivado.');

        $this->assertSame($this->etapa($this->project, StageRole::DEVELOPMENT)->id, $task->fresh()->board_stage_id);
        $this->assertSame(0, AuditLog::query()->where('action', 'unarchived')->count());
    }

    /* ---------------------------------------------------------------- excluir */

    public function test_a_never_used_card_is_deleted_with_criteria_participants_and_the_creation_movement(): void
    {
        $pai = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT));
        $task = $this->novo(['parent_task_id' => $pai->id]);
        $task->acceptanceCriteria()->create(['number' => 1, 'description' => 'CA', 'test_result_id' => TestResult::PENDING]);
        $task->participants()->attach($this->dev->id);

        $this->actingAs($this->gestor)->delete($this->card($task))
            ->assertRedirect("/projetos/{$this->project->id}/quadro")
            ->assertSessionHas('success', 'SGM-2 excluído.');

        $this->assertNull(Task::query()->find($task->id));
        $this->assertSame(0, DB::table('task_acceptance_criteria')->count());
        $this->assertSame(0, DB::table('task_participants')->count());
        $this->assertSame(0, TaskStageMovement::query()->where('task_id', $task->id)->count());
        $this->assertNotNull($pai->fresh(), 'o pai fica');

        $audit = AuditLog::query()->where('action', 'deleted')->sole();
        $this->assertSame(['task', $task->id, $this->gestor->id], [$audit->auditable_type, $audit->auditable_id, $audit->user_id]);
        $this->assertSame([['SGM-2', null], [1, null], [$pai->id, null]], [$audit->changes['key'], $audit->changes['criteria_count'], $audit->changes['parent_task_id'] ?? null]);
    }

    public function test_the_key_of_a_deleted_card_is_not_given_again(): void
    {
        $this->actingAs($this->dev)->post("/projetos/{$this->project->id}/cards", ['title' => 'Primeiro']);
        $this->actingAs($this->dev)->post("/projetos/{$this->project->id}/cards", ['title' => 'Segundo']);
        $segundo = Task::query()->where('title', 'Segundo')->sole();

        $this->actingAs($this->gestor)->delete($this->card($segundo))->assertSessionHasNoErrors();
        $this->actingAs($this->dev)->post("/projetos/{$this->project->id}/cards", ['title' => 'Terceiro']);

        $this->assertSame(3, Task::query()->where('title', 'Terceiro')->value('number'));
    }

    public function test_a_never_used_card_that_was_archived_is_deleted_too(): void
    {
        $task = $this->novo(['archived_at' => now(), 'archive_reason' => 'Engano']);

        $this->actingAs($this->gestor)->delete($this->card($task))->assertSessionHasNoErrors();

        $this->assertNull(Task::query()->find($task->id));
    }

    /** @return array<string, array{Closure(self, Task): void}> */
    public static function usos(): array
    {
        return [
            'segundo movimento' => [fn (self $t, Task $task) => TaskStageMovement::query()->create(['task_id' => $task->id, 'moved_by' => $t->dev->id, 'from_board_stage_id' => $task->board_stage_id, 'to_board_stage_id' => $t->etapa($t->project, StageRole::SPRINT)->id])],
            'horas lançadas' => [fn (self $t, Task $task) => $task->timeEntries()->create(['user_id' => $t->dev->id, 'worked_on' => '2026-10-19', 'hours' => 1])],
            'comentário' => [fn (self $t, Task $task) => $task->comments()->create(['user_id' => $t->dev->id, 'body' => 'Oi'])],
            'anexo' => [fn (self $t, Task $task) => $task->attachments()->create(['path' => 'tasks/x.pdf', 'original_name' => 'x.pdf', 'mime_type' => 'application/pdf'])],
            'link de PR' => [fn (self $t, Task $task) => $task->pullRequests()->create(['url' => 'https://github.com/x/y/pull/1'])],
            'teste cruzado' => [fn (self $t, Task $task) => $task->forceFill(['cross_tested_by' => $t->dev->id, 'cross_tested_at' => now()])->save()],
            'na sprint' => [fn (self $t, Task $task) => $task->forceFill(['sprint_id' => $t->sprint($t->project)->id])->save()],
            'card filho' => [fn (self $t, Task $task) => $t->tarefa($t->project, null, ['parent_task_id' => $task->id])],
            'bug de produção' => [fn (self $t, Task $task) => $t->tarefa($t->project, null, ['origin_task_id' => $task->id])],
        ];
    }

    /** @param  Closure(self, Task): void  $usar */
    #[DataProvider('usos')]
    public function test_a_used_card_is_not_deleted_and_nothing_changes(Closure $usar): void
    {
        $task = $this->novo();
        $usar($this, $task);
        $this->assertFalse($this->ficha($task)['abilities']['delete'], 'a ficha esconde o Excluir');

        $this->actingAs($this->gestor)->delete($this->card($task))->assertSessionHas('error', 'SGM-1 já foi usado: arquive em vez de excluir.');

        $this->assertNotNull(Task::query()->find($task->id));
        $this->assertSame(1, TaskStageMovement::query()->where('task_id', $task->id)->whereNull('from_board_stage_id')->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'deleted')->count());
    }

    public function test_the_detail_shows_delete_only_to_who_manages_and_only_on_the_never_used_card(): void
    {
        $task = $this->novo();

        $this->assertSame([true, true], [$this->ficha($task)['abilities']['archive'], $this->ficha($task)['abilities']['delete']]);
        $this->assertSame([false, false], [$this->ficha($task, $this->dev)['abilities']['archive'], $this->ficha($task, $this->dev)['abilities']['delete']]);
    }

    /* ---------------------------------------------------------------- avisos do card excluído */

    public function test_the_notices_of_a_deleted_card_stay_without_the_link_and_the_others_keep_it(): void
    {
        $task = $this->novo();
        $outro = $this->novo();
        $this->dev->notify(new TaskAssigned($task->load('project'), $this->gestor, 'dev'));
        $this->dev->notify(new TaskAssigned($outro->load('project'), $this->gestor, 'dev'));

        $this->actingAs($this->gestor)->delete($this->card($task))->assertSessionHasNoErrors();

        $data = DatabaseNotification::query()->orderBy('created_at')->get()->keyBy(fn (DatabaseNotification $n) => $n->data['key'])->map->data;
        $this->assertNull($data['SGM-1']['url']);
        $this->assertStringContainsString('Você é o dev de SGM-1', $data['SGM-1']['text']);
        $this->assertSame($outro->url(), $data['SGM-2']['url']);
    }

    /* ---------------------------------------------------------------- lista de arquivados */

    public function test_the_archived_list_has_the_columns_of_f3_newest_first_and_the_board_filters_apply(): void
    {
        $removida = BoardStage::query()->create(['project_id' => $this->project->id, 'name' => 'Homologar', 'stage_role_id' => StageRole::TEST, 'position' => 99, 'archived_at' => now()]);
        $velho = $this->tarefa($this->project, $removida, ['title' => 'Relatório antigo', 'archived_at' => '2026-10-01 12:00:00', 'archive_reason' => 'Fora do escopo']);
        $novo = $this->tarefa($this->project, null, ['title' => 'Tela de login', 'archived_at' => '2026-10-19 15:30:00', 'archive_reason' => 'Duplicado']);
        $this->tarefa($this->project);

        $lista = fn (array $query = []) => $this->actingAs($this->dev)->get("/projetos/{$this->project->id}/quadro?".http_build_query(['archived' => 1, ...$query]))->viewData('page')['props']['archived'];

        $rows = $lista();
        $this->assertSame(['SGM-2', 'SGM-1'], array_column($rows, 'key'));
        $this->assertSame(
            [$novo->id, 'Tela de login', 'Melhoria', '2026-10-19 12:30', 'Duplicado', $novo->url()],
            [$rows[0]['id'], $rows[0]['title'], $rows[0]['type']['label'], $rows[0]['archived_at'], $rows[0]['archive_reason'], $rows[0]['url']],
        );
        $this->assertSame('SGM-1', $rows[1]['key'], 'o card de etapa removida também aparece');
        $this->assertSame(['SGM-1'], array_column($lista(['search' => 'antigo']), 'key'), 'a busca vale na lista');
        $this->assertSame($velho->id, $lista(['search' => 'antigo'])[0]['id']);
    }
}
