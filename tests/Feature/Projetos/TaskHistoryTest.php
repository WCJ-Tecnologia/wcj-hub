<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\ValidationType;
use App\Platform\Models\AiAssistant;
use App\Platform\Models\AiAuthorization;
use App\Platform\Models\User;
use Database\Seeders\AiAssistantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * A linha do tempo do card, `/projetos/{id}/cards/{chave}/historico` (F7 §02, §03, §09; P14, P15,
 * P39, R14, R16, R17): o resumo e cada movimento, e quem vê.
 */
class TaskHistoryTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();

        // Quarta 14/10/2026, meio-dia em São Paulo.
        Carbon::setTestNow('2026-10-14 15:00:00');
        $this->semearProjetos();
        $this->seed(AiAssistantSeeder::class);
        $this->gestor = User::factory()->projectManager()->create(['name' => 'Rafael']);
        $this->project = $this->projeto(['manager_id' => $this->gestor->id, 'key_prefix' => 'SGM']);
        $this->ana = User::factory()->developer()->create(['name' => 'Ana', 'daily_hours' => 8]);
        $this->alocar($this->project, $this->ana);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Meia-noite local em UTC. */
    private function utc(string $local): string
    {
        return Carbon::parse($local, 'America/Sao_Paulo')->utc()->toDateTimeString();
    }

    /** O movimento da criação (de lugar nenhum para o Backlog), como o TaskService grava. */
    private function criarEm(Task $task, string $local, ?User $by = null): void
    {
        TaskStageMovement::query()->forceCreate([
            'task_id' => $task->id,
            'moved_by' => ($by ?? $this->gestor)->id,
            'from_board_stage_id' => null,
            'to_board_stage_id' => $task->board_stage_id,
            'created_at' => $this->utc($local),
        ]);
    }

    private function url(Task $task): string
    {
        return "/projetos/{$this->project->id}/cards/{$task->key()}/historico";
    }

    public function test_timeline_lists_every_movement_with_who_channel_stages_time_rework_and_pause(): void
    {
        $task = $this->tarefa($this->project, null, ['developer_id' => $this->ana->id, 'estimated_hours' => 16, 'title' => 'Prazo de recurso']);
        $todo = $this->etapa($this->project, StageRole::SPRINT);
        $doing = $this->etapa($this->project, StageRole::DEVELOPMENT);
        $review = $this->etapa($this->project, StageRole::CODE_REVIEW);
        $paused = $this->etapa($this->project, StageRole::PAUSED);
        $desativado = User::factory()->developer()->inactive()->create(['name' => 'Bruno']);
        $claude = AiAuthorization::query()->create(['user_id' => $this->ana->id, 'ai_assistant_id' => AiAssistant::CLAUDE, 'oauth_client_id' => (string) Str::uuid()]);

        $this->criarEm($task, '2026-10-05 00:00');
        $this->moverEm($task, $todo, $this->utc('2026-10-06 00:00'), $this->gestor);
        $this->moverEm($task, $doing, $this->utc('2026-10-06 12:00'), $desativado);
        $this->moverEm($task, $review, $this->utc('2026-10-07 00:00'), $this->ana);
        // Volta do code review: retrabalho com motivo "Outro" e texto (P39, P40).
        $outro = ReworkReason::query()->where('requires_text', true)->firstOrFail();
        TaskStageMovement::query()->forceCreate([
            'task_id' => $task->id, 'moved_by' => $this->ana->id, 'ai_authorization_id' => $claude->id,
            'from_board_stage_id' => $review->id, 'to_board_stage_id' => $doing->id,
            'rework_origin_id' => ValidationType::CODE_REVIEW, 'rework_reason_id' => $outro->id, 'reason_text' => 'Faltou o teste',
            'created_at' => $this->utc('2026-10-08 00:00'),
        ]);
        $task->forceFill(['board_stage_id' => $doing->id])->save();
        // Pausa com motivo (P15), e a etapa de pausa é removida depois (P12): o nome fica.
        TaskStageMovement::query()->forceCreate([
            'task_id' => $task->id, 'moved_by' => $this->gestor->id,
            'from_board_stage_id' => $doing->id, 'to_board_stage_id' => $paused->id, 'reason_text' => 'Aguardando o cliente',
            'created_at' => $this->utc('2026-10-09 00:00'),
        ]);
        $task->forceFill(['board_stage_id' => $paused->id])->save();
        $paused->forceFill(['name' => 'Em espera', 'archived_at' => now()])->save();

        $this->actingAs($this->gestor)->get($this->url($task->fresh()))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('projects/task-history')
                ->where('task.key', 'SGM-1')
                ->where('audit_url', null)
                ->count('movements', 6)
                // A criação.
                ->where('movements.0.from', null)
                ->where('movements.0.to.role', 'queue')
                ->where('movements.0.by', ['name' => 'Rafael', 'deactivated' => false])
                ->where('movements.0.channel', 'Tela')
                ->where('movements.0.at', '05/10/2026 00:00')
                ->where('movements.0.days', 1)
                ->where('movements.0.open', false)
                // Para o To do, meio dia útil.
                ->where('movements.1.days', 0.5)
                ->where('movements.2.by', ['name' => 'Bruno', 'deactivated' => true])
                ->where('movements.2.to.role', 'development')
                ->where('movements.2.days', 0.5)
                // A volta pela IA, com o retrabalho.
                ->where('movements.4.channel', 'IA: Claude')
                ->where('movements.4.from.role', 'code_review')
                ->where('movements.4.rework', ['origin' => 'Code review', 'reason' => $outro->label, 'text' => 'Faltou o teste'])
                ->where('movements.4.pause_reason', null)
                // A pausa, na etapa removida, até agora: sexta 09/10 → quarta 14/10 12h = 3,5 dias úteis.
                ->where('movements.5.to', ['name' => 'Em espera', 'role' => 'paused', 'removed' => true])
                ->where('movements.5.pause_reason', 'Aguardando o cliente')
                ->where('movements.5.rework', null)
                ->where('movements.5.days', 3.5)
                ->where('movements.5.open', true)
                // O resumo (F7 §02).
                ->where('summary.stage.name', 'Em espera')
                ->where('summary.stage.since', '09/10/2026 00:00')
                ->where('summary.due_on', null)
                ->where('summary.delay_days', null)
                ->where('summary.estimated_hours', 16)
                ->where('summary.logged_hours', 0)
                // Em desenvolvimento: 0,5 (06/10 12h → 07/10) + 1,0 (08/10 → 09/10) = 1,5 dia útil × 8 h.
                ->where('summary.development_days', 1.5)
                ->where('summary.development_hours', 12)
                ->where('summary.by_role', [
                    ['role' => 'queue', 'label' => 'Fila', 'days' => 1],
                    ['role' => 'sprint', 'label' => 'Sprint', 'days' => 0.5],
                    ['role' => 'development', 'label' => 'Desenvolvimento', 'days' => 1.5],
                    ['role' => 'code_review', 'label' => 'Code review', 'days' => 1],
                    ['role' => 'paused', 'label' => 'Pausa', 'days' => 3.5],
                ])
                ->where('summary.origin', null)
                ->where('summary.production_bugs', [])
                ->where('summary.archived', null));
    }

    public function test_summary_shows_delay_done_date_bugs_and_archive(): void
    {
        $doing = $this->etapa($this->project, StageRole::DEVELOPMENT);
        $done = $this->etapa($this->project, StageRole::DONE);

        // Atrasada: prevista para sexta 09/10, entrou em Doing na terça 06/10; 1 dia útil em pausa → 3 − 1 = 2.
        $late = $this->tarefa($this->project, null, ['developer_id' => $this->ana->id, 'estimated_hours' => 16, 'due_on' => '2026-10-09']);
        $this->criarEm($late, '2026-10-05 00:00');
        $this->moverEm($late, $doing, $this->utc('2026-10-06 00:00'));
        $this->moverEm($late, $this->etapa($this->project, StageRole::PAUSED), $this->utc('2026-10-07 00:00'));
        $this->moverEm($late, $doing, $this->utc('2026-10-08 00:00'));
        $late->timeEntries()->create(['user_id' => $this->ana->id, 'worked_on' => '2026-10-08', 'hours' => 6.5]);

        // Pronta: data prevista e concluída em, sem atraso (F7 §10.6); com um bug de produção ligado (P25).
        $ready = $this->tarefa($this->project, null, ['due_on' => '2026-10-01']);
        $this->criarEm($ready, '2026-09-28 00:00');
        $this->moverEm($ready, $done, $this->utc('2026-10-02 00:00'));
        $bug = $this->tarefa($this->project, null, ['origin_task_id' => $ready->id, 'title' => 'Erro no boleto']);
        $this->criarEm($bug, '2026-10-10 00:00');

        // Arquivada: sem atraso, com o motivo.
        $archived = $this->tarefa($this->project, null, ['due_on' => '2026-10-01', 'archived_at' => now(), 'archive_reason' => 'Duplicada', 'archived_by' => $this->gestor->id]);
        $this->criarEm($archived, '2026-09-28 00:00');

        $this->actingAs($this->gestor)->get($this->url($late->fresh()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.due_on', '2026-10-09')
                ->where('summary.delay_days', 2)
                ->where('summary.done_on', null)
                ->where('summary.logged_hours', 6.5));

        $this->actingAs($this->gestor)->get($this->url($ready->fresh()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.due_on', '2026-10-01')
                ->where('summary.delay_days', null)
                ->where('summary.done_on', '2026-10-02')
                ->where('summary.production_bugs.0.key', $bug->key())
                ->where('summary.missing', []));

        $this->actingAs($this->gestor)->get($this->url($bug->fresh()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.origin.key', $ready->key())
                ->where('summary.missing', ['developer', 'hours'])
                ->where('summary.development_hours', null)
                ->count('movements', 1));

        $this->actingAs($this->gestor)->get($this->url($archived->fresh()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.delay_days', null)
                ->where('summary.archived', ['on' => '2026-10-14', 'reason' => 'Duplicada', 'by' => 'Rafael']));
    }

    public function test_pause_without_reason_reads_sem_motivo(): void
    {
        $task = $this->tarefa($this->project);
        $this->criarEm($task, '2026-10-05 00:00');
        $this->moverEm($task, $this->etapa($this->project, StageRole::PAUSED), $this->utc('2026-10-06 00:00'));

        $this->actingAs($this->gestor)->get($this->url($task->fresh()))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('movements.1.pause_reason', 'Sem motivo'));
    }

    public function test_who_sees_the_timeline_and_who_gets_the_audit_link(): void
    {
        $task = $this->tarefa($this->project);
        $this->criarEm($task, '2026-10-05 00:00');
        $task = $task->fresh();
        $admin = User::factory()->admin()->create();
        $foraDoProjeto = User::factory()->developer()->create();
        $comercial = User::factory()->sales()->create();

        $this->actingAs($this->ana)->get($this->url($task))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('audit_url', null));
        $this->actingAs($admin)->get($this->url($task))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('audit_url', "http://localhost/admin/audit-logs?type=task&id={$task->id}"));
        $this->actingAs($foraDoProjeto)->get($this->url($task))->assertForbidden();
        $this->actingAs($comercial)->get($this->url($task))->assertForbidden();
        // Card de outro projeto pela rota deste: 404 (Route::bind).
        $outro = $this->projeto(['key_prefix' => 'OUT']);
        $this->actingAs($admin)->get("/projetos/{$this->project->id}/cards/OUT-1/historico")->assertNotFound();
        $this->actingAs($admin)->get("/projetos/{$outro->id}/cards/{$task->key()}/historico")->assertNotFound();
    }

    public function test_the_ficha_links_to_the_timeline(): void
    {
        $task = $this->tarefa($this->project);
        $this->criarEm($task, '2026-10-05 00:00');

        $this->actingAs($this->gestor)->get("/projetos/{$this->project->id}/cards/{$task->key()}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('task.history_url', 'http://localhost'.$this->url($task->fresh())));
    }
}
