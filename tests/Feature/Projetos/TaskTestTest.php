<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Models\ValidationType;
use App\Domains\Projetos\Services\TaskUsageQuery;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * A seção Teste da ficha (F4; escopo B4; spec b4-teste-e-qa), uma regra por teste, pela rota que a
 * tela chama: o teste cruzado (CRUZ-01, CRUZ-02; P34), o resultado de cada item do roteiro
 * (ROTEIRO-01, ROTEIRO-02; P36, P37), a evidência no item (EVID-01) e o que a exclusão de verdade
 * passa a respeitar (EXC-01; T1, P26). As negações de quem não vê o projeto: TaskTestPolicyTest.
 */
class TaskTestTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $dev;

    private User $outroDev;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        Storage::fake('local');
        Carbon::setTestNow('2026-10-23 17:30:00');
        $this->gestor = User::factory()->projectManager()->create(['name' => 'Ana Gestora']);
        $this->dev = User::factory()->developer()->create(['name' => 'Bruno Dev']);
        $this->outroDev = User::factory()->developer()->create(['name' => 'Carla Teste']);
        $this->project = $this->projeto(['manager_id' => $this->gestor->id, 'key_prefix' => 'SGM']);
        $this->alocar($this->project, $this->dev, $this->outroDev);
        $this->task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::TEST), [
            'title' => 'Login', 'developer_id' => $this->dev->id, 'sprint_id' => $this->sprint($this->project)->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function url(string $suffix = '', ?Task $task = null): string
    {
        $task ??= $this->task;

        return "/projetos/{$this->project->id}/cards/SGM-{$task->number}{$suffix}";
    }

    private function criterio(int $number, int $result = TestResult::PENDING, ?int $parentId = null): TaskAcceptanceCriterion
    {
        return $this->task->acceptanceCriteria()->create(['number' => $number, 'parent_id' => $parentId, 'description' => "CA {$number}", 'test_result_id' => $result]);
    }

    /** @return array<string, mixed> */
    private function ficha(User $as): array
    {
        $props = [];
        $this->actingAs($as)->get($this->url())->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$props) {
                $props = $page->component('projects/task')->toArray()['props']['task'];
            });

        return $props;
    }

    private function ultimaAuditoria(): AuditLog
    {
        return AuditLog::query()->latest('id')->firstOrFail();
    }

    /* ---------------------------------------------------------------- CRUZ-01: marcar */

    public function test_another_allocated_person_marks_the_cross_test_with_who_and_when(): void
    {
        $this->actingAs($this->outroDev)->post($this->url('/teste-cruzado'))
            ->assertRedirect()->assertSessionHas('success', 'Teste cruzado marcado.');

        $task = $this->task->fresh();
        $this->assertSame($this->outroDev->id, $task->cross_tested_by);
        $this->assertSame('2026-10-23 17:30:00', $task->cross_tested_at->toDateTimeString());

        $log = $this->ultimaAuditoria();
        $this->assertSame(['success', 'updated', 'task', $this->task->id, $this->outroDev->id], [$log->outcome, $log->action, $log->auditable_type, $log->auditable_id, $log->user_id]);
        $this->assertSame([null, $this->outroDev->id], $log->changes['cross_tested_by']);
        $this->assertNull($log->changes['cross_tested_at'][0]);
        $this->assertNotNull($log->changes['cross_tested_at'][1]);

        // F4 §01: "Testado por Fulano em DD/MM/AAAA às HH:MM", no fuso de São Paulo.
        $this->assertSame(['by' => ['id' => $this->outroDev->id, 'name' => 'Carla Teste'], 'at' => '2026-10-23 14:30'], $this->ficha($this->dev)['cross_tested']);
    }

    public function test_without_a_developer_any_allocated_person_marks(): void
    {
        $this->task->forceFill(['developer_id' => null])->save();

        $this->actingAs($this->dev)->post($this->url('/teste-cruzado'))->assertRedirect();

        $this->assertSame($this->dev->id, $this->task->fresh()->cross_tested_by);
    }

    public function test_the_developer_of_the_task_never_marks_even_when_managing_the_project(): void
    {
        $this->actingAs($this->dev)->post($this->url('/teste-cruzado'))->assertForbidden();
        $this->assertNull($this->task->fresh()->cross_tested_by);
        $this->assertNull($this->task->fresh()->cross_tested_at);

        $this->task->forceFill(['developer_id' => $this->gestor->id])->save();
        $this->actingAs($this->gestor)->post($this->url('/teste-cruzado'))->assertForbidden();
        $this->assertNull($this->task->fresh()->cross_tested_by);

        $admin = User::factory()->admin()->create();
        $this->task->forceFill(['developer_id' => $admin->id])->save();
        $this->actingAs($admin)->post($this->url('/teste-cruzado'))->assertForbidden();
        $this->assertNull($this->task->fresh()->cross_tested_by);
    }

    public function test_the_ability_hides_the_button_for_the_developer_only(): void
    {
        $this->assertFalse($this->ficha($this->dev)['abilities']['cross_test']);
        $this->assertTrue($this->ficha($this->outroDev)['abilities']['cross_test']);
        $this->assertTrue($this->ficha($this->gestor)['abilities']['cross_test']);
    }

    public function test_marking_again_is_refused_and_keeps_the_first_mark(): void
    {
        $this->task->forceFill(['cross_tested_by' => $this->outroDev->id, 'cross_tested_at' => '2026-10-22 12:00:00'])->save();

        $this->actingAs($this->gestor)->post($this->url('/teste-cruzado'))
            ->assertRedirect()->assertSessionHas('error', 'Teste cruzado já marcado por Carla Teste: desfaça antes.');

        $task = $this->task->fresh();
        $this->assertSame($this->outroDev->id, $task->cross_tested_by);
        $this->assertSame('2026-10-22 12:00:00', $task->cross_tested_at->toDateTimeString());
    }

    public function test_an_archived_card_is_not_marked(): void
    {
        $this->task->forceFill(['archived_at' => now(), 'archive_reason' => 'Parado', 'archived_by' => $this->gestor->id])->save();

        $this->actingAs($this->outroDev)->post($this->url('/teste-cruzado'))
            ->assertSessionHas('error', 'Card arquivado não muda: desarquive antes.');

        $this->assertNull($this->task->fresh()->cross_tested_by);
    }

    public function test_a_finished_project_still_takes_the_mark(): void
    {
        $this->project->forceFill(['finished_at' => now()])->save();

        $this->actingAs($this->outroDev)->post($this->url('/teste-cruzado'))->assertSessionHas('success');

        $this->assertSame($this->outroDev->id, $this->task->fresh()->cross_tested_by);
    }

    /* ---------------------------------------------------------------- CRUZ-02: desfazer */

    public function test_who_marked_undoes_and_the_undo_is_audited(): void
    {
        $this->task->forceFill(['cross_tested_by' => $this->outroDev->id, 'cross_tested_at' => now()])->save();
        $this->assertTrue($this->ficha($this->outroDev)['abilities']['undo_cross_test']);

        $this->actingAs($this->outroDev)->post($this->url('/teste-cruzado/desfazer'))
            ->assertSessionHas('success', 'Marca de teste cruzado desfeita.');

        $task = $this->task->fresh();
        $this->assertNull($task->cross_tested_by);
        $this->assertNull($task->cross_tested_at);
        $log = $this->ultimaAuditoria();
        $this->assertSame(['updated', [$this->outroDev->id, null]], [$log->action, $log->changes['cross_tested_by']]);
    }

    public function test_the_manager_and_the_admin_undo_someone_elses_mark(): void
    {
        foreach (['gestor' => $this->gestor, 'admin' => User::factory()->admin()->create()] as $papel => $user) {
            Task::query()->whereKey($this->task->id)->update(['cross_tested_by' => $this->outroDev->id, 'cross_tested_at' => now()]);

            $this->actingAs($user)->post($this->url('/teste-cruzado/desfazer'))->assertSessionHas('success');

            $this->assertNull($this->task->fresh()->cross_tested_by, "{$papel} desfaz");
            $this->assertNull($this->task->fresh()->cross_tested_at, "{$papel} desfaz");
        }
    }

    public function test_another_developer_does_not_undo_the_mark(): void
    {
        $terceiro = User::factory()->developer()->create();
        $this->alocar($this->project, $terceiro);
        $this->task->forceFill(['cross_tested_by' => $this->outroDev->id, 'cross_tested_at' => now()])->save();
        $this->assertFalse($this->ficha($terceiro)['abilities']['undo_cross_test']);

        $this->actingAs($terceiro)->post($this->url('/teste-cruzado/desfazer'))->assertForbidden();

        $this->assertSame($this->outroDev->id, $this->task->fresh()->cross_tested_by);
    }

    public function test_who_marked_and_left_the_project_no_longer_undoes(): void
    {
        $this->task->forceFill(['cross_tested_by' => $this->outroDev->id, 'cross_tested_at' => now()])->save();
        $this->project->members()->where('user_id', $this->outroDev->id)->delete();

        $this->actingAs($this->outroDev)->post($this->url('/teste-cruzado/desfazer'))->assertForbidden();

        $this->assertSame($this->outroDev->id, $this->task->fresh()->cross_tested_by);
    }

    public function test_undoing_without_a_mark_is_refused(): void
    {
        $this->actingAs($this->gestor)->post($this->url('/teste-cruzado/desfazer'))
            ->assertSessionHas('error', 'Este card não tem teste cruzado marcado.');
    }

    /* ---------------------------------------------------------------- ROTEIRO-01: resultado do item */

    public function test_marking_an_item_records_the_result_who_and_when(): void
    {
        $item = $this->criterio(1);

        $this->actingAs($this->outroDev)->post($this->url("/criterios/{$item->id}/resultado"), ['result' => 'approved'])->assertRedirect()->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame([TestResult::PASSED, $this->outroDev->id, '2026-10-23 17:30:00'], [$item->test_result_id, $item->marked_by, $item->marked_at->toDateTimeString()]);
        $log = $this->ultimaAuditoria();
        $this->assertSame(['updated', 'task_acceptance_criterion', $item->id], [$log->action, $log->auditable_type, $log->auditable_id]);
        $this->assertSame([TestResult::PENDING, TestResult::PASSED], $log->changes['test_result_id']);
        $this->assertSame([null, $this->outroDev->id], $log->changes['marked_by']);

        $criterio = $this->ficha($this->dev)['criteria'][0];
        $this->assertSame('approved', $criterio['result']);
        $this->assertSame(['by' => ['id' => $this->outroDev->id, 'name' => 'Carla Teste'], 'at' => '2026-10-23 14:30'], $criterio['marked']);
    }

    public function test_a_failed_item_does_not_move_the_card(): void
    {
        $item = $this->criterio(1);
        $stage = $this->task->board_stage_id;

        $this->actingAs($this->outroDev)->post($this->url("/criterios/{$item->id}/resultado"), ['result' => 'failed'])->assertSessionHasNoErrors();

        $this->assertSame(TestResult::FAILED, $item->fresh()->test_result_id);
        $this->assertSame($stage, $this->task->fresh()->board_stage_id);
        $this->assertSame(0, $this->task->movements()->count(), 'nenhum movimento novo');
    }

    public function test_pending_marked_by_hand_is_a_marking_too(): void
    {
        $item = $this->criterio(1, TestResult::FAILED);

        $this->actingAs($this->gestor)->post($this->url("/criterios/{$item->id}/resultado"), ['result' => 'pending'])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame([TestResult::PENDING, $this->gestor->id], [$item->test_result_id, $item->marked_by]);
        $this->assertNotNull($item->marked_at);
    }

    public function test_the_same_result_again_changes_nothing_and_writes_no_audit(): void
    {
        $item = $this->criterio(1, TestResult::PASSED);
        $item->forceFill(['marked_by' => $this->outroDev->id, 'marked_at' => '2026-10-22 12:00:00'])->save();
        $antes = AuditLog::query()->count();

        $this->actingAs($this->gestor)->post($this->url("/criterios/{$item->id}/resultado"), ['result' => 'approved'])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame([$this->outroDev->id, '2026-10-22 12:00:00'], [$item->marked_by, $item->marked_at->toDateTimeString()]);
        $this->assertSame($antes, AuditLog::query()->count());
    }

    public function test_the_subitem_is_marked_apart_from_the_parent(): void
    {
        $pai = $this->criterio(1, TestResult::PASSED);
        $sub = $this->criterio(1, TestResult::PENDING, $pai->id);

        $this->actingAs($this->outroDev)->post($this->url("/criterios/{$sub->id}/resultado"), ['result' => 'failed'])->assertSessionHasNoErrors();

        $this->assertSame(TestResult::FAILED, $sub->fresh()->test_result_id);
        $this->assertSame(TestResult::PASSED, $pai->fresh()->test_result_id);
    }

    public function test_an_unknown_result_is_refused_on_the_field(): void
    {
        $item = $this->criterio(1);

        $this->actingAs($this->outroDev)->post($this->url("/criterios/{$item->id}/resultado"), ['result' => 'passed'])
            ->assertSessionHasErrors(['result' => 'Escolha Aprovado, Reprovado ou A testar.']);

        $this->assertSame(TestResult::PENDING, $item->fresh()->test_result_id);
    }

    public function test_an_item_of_another_card_is_404(): void
    {
        $outro = $this->tarefa($this->project);
        $alheio = $outro->acceptanceCriteria()->create(['number' => 1, 'description' => 'Do outro', 'test_result_id' => TestResult::PENDING]);

        $this->actingAs($this->outroDev)->post($this->url("/criterios/{$alheio->id}/resultado"), ['result' => 'approved'])->assertNotFound();

        $this->assertSame(TestResult::PENDING, $alheio->fresh()->test_result_id);
    }

    public function test_an_item_of_an_archived_card_is_not_marked(): void
    {
        $item = $this->criterio(1);
        $this->task->forceFill(['archived_at' => now(), 'archive_reason' => 'Parado'])->save();

        $this->actingAs($this->outroDev)->post($this->url("/criterios/{$item->id}/resultado"), ['result' => 'approved'])
            ->assertSessionHas('error', 'Card arquivado não muda: desarquive antes.');

        $this->assertSame(TestResult::PENDING, $item->fresh()->test_result_id);
    }

    /* ---------------------------------------------------------------- ROTEIRO-02: volta para A testar */

    public function test_editing_a_tested_item_clears_the_last_marking(): void
    {
        $item = $this->criterio(1, TestResult::PASSED);
        $item->forceFill(['marked_by' => $this->outroDev->id, 'marked_at' => now()])->save();

        $this->actingAs($this->dev)->patch($this->url("/criterios/{$item->id}"), ['description' => 'Texto novo'])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame([TestResult::PENDING, null, null], [$item->test_result_id, $item->marked_by, $item->marked_at]);
        $this->assertNull($this->ficha($this->dev)['criteria'][0]['marked']);
    }

    public function test_rework_sends_every_item_back_to_pending_without_the_marking(): void
    {
        $a = $this->criterio(1, TestResult::PASSED);
        $b = $this->criterio(2, TestResult::FAILED);
        TaskAcceptanceCriterion::query()->update(['marked_by' => $this->outroDev->id, 'marked_at' => now()]);
        $this->task->forceFill(['cross_tested_by' => $this->outroDev->id, 'cross_tested_at' => now()])->save();

        $this->actingAs($this->outroDev)->post($this->url('/mover'), [
            'stage_id' => $this->etapa($this->project, StageRole::SPRINT)->id,
            'mistake' => false,
            'rework_origin_id' => ValidationType::QA,
            'rework_reason_id' => ReworkReason::query()->whereNull('slug')->value('id'),
        ])->assertSessionHasNoErrors();

        foreach ([$a, $b] as $item) {
            $item->refresh();
            $this->assertSame([TestResult::PENDING, null, null], [$item->test_result_id, $item->marked_by, $item->marked_at]);
        }
        $this->assertNull($this->task->fresh()->cross_tested_by);
    }

    public function test_a_return_marked_as_mistake_keeps_results_markings_and_cross_test(): void
    {
        $item = $this->criterio(1, TestResult::PASSED);
        $item->forceFill(['marked_by' => $this->outroDev->id, 'marked_at' => now()])->save();
        $this->task->forceFill(['cross_tested_by' => $this->outroDev->id, 'cross_tested_at' => now()])->save();

        $this->actingAs($this->outroDev)->post($this->url('/mover'), [
            'stage_id' => $this->etapa($this->project, StageRole::SPRINT)->id, 'mistake' => true,
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame([TestResult::PASSED, $this->outroDev->id], [$item->test_result_id, $item->marked_by]);
        $this->assertSame($this->outroDev->id, $this->task->fresh()->cross_tested_by);
    }

    /* ---------------------------------------------------------------- EVID-01: evidência no item */

    public function test_evidence_is_an_attachment_tied_to_the_item(): void
    {
        $item = $this->criterio(1);

        $this->actingAs($this->outroDev)->post($this->url('/anexos'), [
            'files' => [UploadedFile::fake()->image('print.png')], 'criterion_id' => $item->id,
        ])->assertSessionHasNoErrors();

        $anexo = $this->task->attachments()->sole();
        $this->assertSame([$item->id, $this->outroDev->id], [$anexo->task_acceptance_criterion_id, $anexo->user_id]);
        $this->assertSame($item->id, $this->ultimaAuditoria()->changes['task_acceptance_criterion_id'][1]);
        $this->assertSame($item->id, $this->ficha($this->dev)['attachments'][0]['criterion_id']);
    }

    public function test_evidence_on_an_item_of_another_card_is_refused_on_the_field(): void
    {
        $outro = $this->tarefa($this->project);
        $alheio = $outro->acceptanceCriteria()->create(['number' => 1, 'description' => 'Do outro', 'test_result_id' => TestResult::PENDING]);

        $this->actingAs($this->outroDev)->post($this->url('/anexos'), [
            'files' => [UploadedFile::fake()->image('print.png')], 'criterion_id' => $alheio->id,
        ])->assertSessionHasErrors(['criterion_id' => 'Escolha um critério deste card.']);

        $this->assertSame(0, $this->task->attachments()->count() + $outro->attachments()->count());
        $this->assertSame([], Storage::disk('local')->allFiles(), 'o arquivo gravado antes da recusa sai do disco');
    }

    public function test_removing_evidence_deletes_the_file_and_the_row(): void
    {
        $item = $this->criterio(1);
        $this->actingAs($this->outroDev)->post($this->url('/anexos'), ['files' => [UploadedFile::fake()->image('print.png')], 'criterion_id' => $item->id]);
        $anexo = $this->task->attachments()->sole();

        $this->actingAs($this->outroDev)->delete($this->url("/anexos/{$anexo->id}"))->assertSessionHasNoErrors();

        $this->assertSame(0, $item->evidences()->count());
        Storage::disk('local')->assertMissing($anexo->path);
    }

    /* ---------------------------------------------------------------- EXC-01: exclusão de verdade */

    public function test_a_pending_item_with_evidence_is_not_deleted(): void
    {
        $item = $this->criterio(3);
        Storage::disk('local')->put('tasks/e.png', 'png');
        $evidencia = $this->task->attachments()->create(['path' => 'tasks/e.png', 'original_name' => 'e.png', 'mime_type' => 'image/png', 'task_acceptance_criterion_id' => $item->id]);

        $this->actingAs($this->dev)->delete($this->url("/criterios/{$item->id}"))
            ->assertSessionHas('error', 'CA_3 já foi testado: edite em vez de excluir.');

        $this->assertNotNull($item->fresh());
        $this->assertNotNull($evidencia->fresh());
    }

    public function test_a_criterion_whose_pending_subitem_has_evidence_is_not_deleted(): void
    {
        $pai = $this->criterio(3);
        $sub = $this->criterio(1, TestResult::PENDING, $pai->id);
        Storage::disk('local')->put('tasks/e.png', 'png');
        $this->task->attachments()->create(['path' => 'tasks/e.png', 'original_name' => 'e.png', 'mime_type' => 'image/png', 'task_acceptance_criterion_id' => $sub->id]);

        $this->actingAs($this->dev)->delete($this->url("/criterios/{$pai->id}"))
            ->assertSessionHas('error', 'CA_3 já foi testado: edite em vez de excluir.');

        $this->assertNotNull($pai->fresh());
        $this->assertNotNull($sub->fresh());
    }

    public function test_a_card_with_a_marked_item_is_used(): void
    {
        $novo = $this->tarefa($this->project);
        $novo->movements()->create(['moved_by' => $this->gestor->id, 'to_board_stage_id' => $novo->board_stage_id]);
        $item = $novo->acceptanceCriteria()->create(['number' => 1, 'description' => 'CA', 'test_result_id' => TestResult::PENDING]);
        $usage = app(TaskUsageQuery::class);
        $this->assertTrue($usage->isUnused($novo), 'critério A testar sem marcação não é uso');

        $item->forceFill(['test_result_id' => TestResult::PASSED])->save();
        $this->assertFalse($usage->isUnused($novo), 'item com resultado é uso');

        $item->forceFill(['test_result_id' => TestResult::PENDING, 'marked_by' => $this->gestor->id, 'marked_at' => now()])->save();
        $this->assertFalse($usage->isUnused($novo), 'A testar marcado à mão é uso');

        $this->actingAs($this->gestor)->delete($this->url('', $novo))
            ->assertSessionHas('error', "SGM-{$novo->number} já foi usado: arquive em vez de excluir.");
        $this->assertNotNull($novo->fresh());
    }
}
