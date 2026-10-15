<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\ValidationType;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * O quadro como a tela recebe (F3 §03, §05 e §06; contrato `types/quadro.ts`): o card com as
 * zonas, os filtros do servidor, a coluna de pronto limitada (P13), o progresso do card pai
 * (P57), o atraso descontada a pausa (P53, R16) e o motivo da pausa (P15).
 */
class BoardTest extends TestCase
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
        $this->dev = User::factory()->developer()->create(['name' => 'Bruno Dev']);
        $this->project = $this->projeto(['manager_id' => $this->gestor->id, 'key_prefix' => 'SGM']);
        $this->alocar($this->project, $this->dev);
    }

    /**
     * As props da página, como a tela recebe.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function quadro(array $query = [], ?User $as = null): array
    {
        $props = [];
        $this->actingAs($as ?? $this->gestor)
            ->get("/projetos/{$this->project->id}/quadro?".http_build_query($query))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$props) {
                $props = $page->component('projects/board')->toArray()['props'];
            });

        return $props;
    }

    /** @return list<string> */
    private function chaves(array $query = [], ?User $as = null): array
    {
        $keys = collect($this->quadro($query, $as)['cards'])->pluck('key')->all();
        sort($keys);

        return $keys;
    }

    public function test_the_card_carries_every_zone_of_the_contract(): void
    {
        $area = $this->project->areas()->create(['name' => 'Financeiro', 'color' => 'green']);
        $pai = $this->tarefa($this->project, null, ['title' => 'Épico']);
        $contato = Contact::factory()->create(['client_id' => $this->project->client_id, 'name' => 'Marta']);
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SUPPORT), [
            'title' => 'Relatório', 'project_area_id' => $area->id, 'task_type_id' => $this->tipo($this->project, 'support'),
            'task_priority_id' => 4, 'developer_id' => $this->dev->id, 'estimated_hours' => 6, 'proposed_hours' => 9,
            'parent_task_id' => $pai->id, 'requester_contact_id' => $contato->id,
        ]);
        $task->participants()->attach($this->gestor->id);
        $task->comments()->create(['user_id' => $this->dev->id, 'body' => 'Olhei']);

        $this->actingAs($this->gestor)->get("/projetos/{$this->project->id}/quadro")->assertInertia(fn (AssertableInertia $page) => $page
            ->component('projects/board')
            ->where('project.key_prefix', 'SGM')
            ->where('project.open_sprint', null)
            ->has('stages', 8)
            ->where('stages.0.role', 'support')
            ->where('cards.1', [
                'id' => $task->id, 'key' => "SGM-{$task->number}", 'title' => 'Relatório', 'stage_id' => $task->board_stage_id,
                'position' => 0, 'priority' => 'urgent',
                'type' => ['id' => $task->task_type_id, 'slug' => 'support', 'label' => 'Suporte', 'color' => null],
                'area' => ['id' => $area->id, 'name' => 'Financeiro', 'color' => 'green'],
                'parent' => ['id' => $pai->id, 'key' => "SGM-{$pai->number}", 'title' => 'Épico', 'url' => url("/projetos/{$this->project->id}/cards/SGM-{$pai->number}")],
                'cover_url' => null, 'estimated_hours' => 6, 'proposed_hours' => 9,
                'developer' => ['id' => $this->dev->id, 'name' => 'Bruno Dev'], 'qa' => null,
                'participants' => [['id' => $this->gestor->id, 'name' => $this->gestor->name]],
                'due_on' => null, 'late' => false, 'attachments_count' => 0, 'comments_count' => 1,
                'children' => null, 'pause_reason' => null, 'rework_count' => 0, 'support_requester' => 'Marta',
                'qa_summary' => null, 'url' => url("/projetos/{$this->project->id}/cards/SGM-{$task->number}"),
            ])
            ->where('cards.0.children', ['todo' => 1, 'doing' => 0, 'done' => 0]));
    }

    public function test_child_progress_counts_by_stage_role_and_skips_archived_children(): void
    {
        $pai = $this->tarefa($this->project);
        foreach ([StageRole::QUEUE, StageRole::DEVELOPMENT, StageRole::PAUSED, StageRole::DONE] as $role) {
            $this->tarefa($this->project, $this->etapa($this->project, $role), ['parent_task_id' => $pai->id]);
        }
        $this->tarefa($this->project, null, ['parent_task_id' => $pai->id, 'archived_at' => now(), 'archive_reason' => 'x']);

        $this->assertSame(['todo' => 1, 'doing' => 2, 'done' => 1], $this->quadro()['cards'][0]['children']);
    }

    public function test_done_shows_only_the_open_and_the_last_closed_sprint_unless_old_done(): void
    {
        $done = $this->etapa($this->project, StageRole::DONE);
        $velha = $this->sprint($this->project, ['name' => 'S1', 'closed_at' => now()->subDays(20)]);
        $ultima = $this->sprint($this->project, ['name' => 'S2', 'closed_at' => now()->subDays(5)]);
        $aberta = $this->sprint($this->project, ['name' => 'S3']);
        $a = $this->tarefa($this->project, $done, ['sprint_id' => $velha->id]);
        $b = $this->tarefa($this->project, $done, ['sprint_id' => $ultima->id]);
        $c = $this->tarefa($this->project, $done, ['sprint_id' => $aberta->id]);
        $d = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT), ['sprint_id' => $velha->id]);

        $this->assertSame($this->ordenadas([$b, $c, $d]), $this->chaves());
        $this->assertSame($this->ordenadas([$a, $b, $c, $d]), $this->chaves(['old_done' => 1]));
    }

    public function test_archived_cards_leave_the_board_and_come_back_by_the_filter(): void
    {
        $ativo = $this->tarefa($this->project);
        $arquivado = $this->tarefa($this->project, null, ['archived_at' => now(), 'archive_reason' => 'Duplicado']);

        $this->assertSame($this->ordenadas([$ativo]), $this->chaves());
        $this->assertSame($this->ordenadas([$arquivado]), $this->chaves(['archived' => 1]));
    }

    public function test_search_by_key_or_title_and_the_simple_filters(): void
    {
        $area = $this->project->areas()->create(['name' => 'Financeiro']);
        $login = $this->tarefa($this->project, null, ['title' => 'Tela de login', 'developer_id' => $this->dev->id]);
        $menu = $this->tarefa($this->project, null, ['title' => 'Cardápio', 'project_area_id' => $area->id, 'task_priority_id' => 4, 'qa_id' => $this->dev->id]);
        $outro = $this->tarefa($this->project, null, ['title' => 'Outro', 'task_type_id' => $this->tipo($this->project, 'development')]);

        $this->assertSame($this->ordenadas([$login]), $this->chaves(['search' => 'login']));
        $this->assertSame($this->ordenadas([$menu]), $this->chaves(['search' => "sgm-{$menu->number}"]));
        $this->assertSame($this->ordenadas([$login]), $this->chaves(['developer_id' => $this->dev->id]));
        $this->assertSame($this->ordenadas([$menu]), $this->chaves(['qa_id' => $this->dev->id]));
        $this->assertSame($this->ordenadas([$menu]), $this->chaves(['area_id' => $area->id]));
        $this->assertSame($this->ordenadas([$menu]), $this->chaves(['priority' => 'urgent']));
        $this->assertSame($this->ordenadas([$outro]), $this->chaves(['type_id' => $this->tipo($this->project, 'development')]));
    }

    public function test_only_mine_is_dev_qa_or_participant(): void
    {
        $dev = $this->tarefa($this->project, null, ['developer_id' => $this->dev->id]);
        $qa = $this->tarefa($this->project, null, ['qa_id' => $this->dev->id]);
        $participante = $this->tarefa($this->project);
        $participante->participants()->attach($this->dev->id);
        $this->tarefa($this->project);

        $this->assertSame($this->ordenadas([$dev, $qa, $participante]), $this->chaves(['mine' => 1], $this->dev));
    }

    public function test_marks_filter_pause_with_its_reason_rework_support_and_proposed(): void
    {
        $pausa = $this->etapa($this->project, StageRole::PAUSED);
        $parado = $this->tarefa($this->project, $pausa);
        TaskStageMovement::query()->create(['task_id' => $parado->id, 'moved_by' => $this->dev->id, 'to_board_stage_id' => $pausa->id, 'reason_text' => 'Esperando API']);
        $voltou = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT));
        TaskStageMovement::query()->create([
            'task_id' => $voltou->id, 'moved_by' => $this->dev->id, 'to_board_stage_id' => $voltou->board_stage_id,
            'rework_origin_id' => ValidationType::QA, 'rework_reason_id' => ReworkReason::query()->value('id'),
        ]);
        $proposta = $this->tarefa($this->project, null, ['proposed_hours' => 5]);
        $this->tarefa($this->project);

        $this->assertSame($this->ordenadas([$parado]), $this->chaves(['marks' => ['pause']]));
        $this->assertSame($this->ordenadas([$voltou]), $this->chaves(['marks' => ['rework']]));
        $this->assertSame($this->ordenadas([$proposta, $voltou]), $this->chaves(['marks' => ['proposed', 'rework']]));
        $this->assertSame('Esperando API', $this->quadro(['marks' => ['pause']])['cards'][0]['pause_reason']);
        $this->assertSame(1, $this->quadro(['marks' => ['rework']])['cards'][0]['rework_count']);
    }

    public function test_late_is_past_the_due_date_out_of_done_discounting_business_days_paused(): void
    {
        $this->travelTo('2026-10-15 15:00:00');
        $doing = $this->etapa($this->project, StageRole::DEVELOPMENT);
        $pausa = $this->etapa($this->project, StageRole::PAUSED);

        $atrasado = $this->tarefa($this->project, $doing, ['due_on' => '2026-10-13']);
        $pronto = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DONE), ['due_on' => '2026-10-13']);
        // Ficou em pausa de 05/10 (seg) a 09/10 (sex): 4 dias úteis inteiros empurram o limite de 13/10 para 19/10.
        $pausado = $this->tarefa($this->project, $doing, ['due_on' => '2026-10-13']);
        TaskStageMovement::query()->forceCreate(['task_id' => $pausado->id, 'moved_by' => $this->dev->id, 'to_board_stage_id' => $pausa->id, 'created_at' => '2026-10-05 13:00:00']);
        TaskStageMovement::query()->forceCreate(['task_id' => $pausado->id, 'moved_by' => $this->dev->id, 'from_board_stage_id' => $pausa->id, 'to_board_stage_id' => $doing->id, 'created_at' => '2026-10-09 13:00:00']);
        $this->tarefa($this->project, $doing, ['due_on' => '2026-10-15']);

        // O pronto não atrasa; o pausado ainda está no prazo; o de hoje não passou.
        $this->assertSame($this->ordenadas([$atrasado]), $this->chaves(['marks' => ['late']]));
        $this->assertNotContains("SGM-{$pronto->number}", $this->chaves(['marks' => ['late']]));
    }

    public function test_a_finished_project_hides_quick_create_and_support_but_keeps_moving(): void
    {
        $this->project->update(['finished_at' => now()]);

        $props = $this->quadro();
        $this->assertSame([false, false, true, true], [$props['abilities']['create_card'], $props['abilities']['create_support'], $props['abilities']['move'], $props['project']['finished']]);
    }

    public function test_options_bring_only_active_items_of_the_project(): void
    {
        $this->project->areas()->create(['name' => 'Ativa', 'color' => 'blue']);
        $velha = $this->project->areas()->create(['name' => 'Velha']);
        $velha->forceFill(['deactivated_at' => now()])->save();
        $this->projeto()->areas()->create(['name' => 'De outro']);
        $saiu = User::factory()->developer()->create();
        $saiu->forceFill(['deactivated_at' => now()])->save();
        $this->alocar($this->project, $saiu);

        $options = $this->quadro()['options'];
        $this->assertSame([['id' => $this->project->areas()->where('name', 'Ativa')->value('id'), 'name' => 'Ativa', 'color' => 'blue']], $options['areas']);
        $this->assertSame(['Melhoria', 'Correção de bug', 'Suporte', 'Mudança (GMUD)', 'Desenvolvimento'], array_column($options['types'], 'label'));
        $this->assertSame([['id' => $this->dev->id, 'name' => 'Bruno Dev']], $options['members']);
    }

    public function test_the_number_of_queries_does_not_grow_with_the_cards(): void
    {
        $contar = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->gestor)->get("/projetos/{$this->project->id}/quadro")->assertOk();
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };
        $this->semeiaCards(5);
        $poucos = $contar();
        $this->semeiaCards(495);

        $this->assertSame($poucos, $contar(), 'o quadro consulta por card');
    }

    /** Cards variados (pai e filho, pausa, data prevista, participante, comentário), direto no banco. */
    private function semeiaCards(int $quantos): void
    {
        $pausa = $this->etapa($this->project, StageRole::PAUSED);
        $doing = $this->etapa($this->project, StageRole::DEVELOPMENT);
        $pai = $this->tarefa($this->project);
        for ($i = 0; $i < $quantos; $i++) {
            $stage = [$pausa, $doing][$i % 2];
            $task = $this->tarefa($this->project, $stage, [
                'parent_task_id' => $pai->id, 'developer_id' => $this->dev->id, 'due_on' => '2026-01-05', 'task_priority_id' => 1 + $i % 4,
            ]);
            TaskStageMovement::query()->create(['task_id' => $task->id, 'moved_by' => $this->dev->id, 'to_board_stage_id' => $stage->id, 'reason_text' => 'x']);
            $task->participants()->attach($this->gestor->id);
            $task->comments()->create(['user_id' => $this->dev->id, 'body' => 'c']);
        }
    }

    /** @param  list<Task>  $tasks @return list<string> */
    private function ordenadas(array $tasks): array
    {
        $keys = array_map(fn (Task $task) => "SGM-{$task->number}", $tasks);
        sort($keys);

        return $keys;
    }
}
