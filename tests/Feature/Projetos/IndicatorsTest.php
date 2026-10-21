<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Indicadores do projeto, `/projetos/{id}/indicadores` (F7 §01 a §06, §09; P11, P15, P25, P26,
 * P53, P54, R16, R17): cada aba com os exemplos do F7, os filtros, quem vê o quê e a contagem de
 * consultas com 500 cards (escopo §10).
 */
class IndicatorsTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $ana;

    private User $quesia;

    private Sprint $aberta;

    private Sprint $encerrada;

    /** @var array<string, Task> */
    private array $t = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Quarta 14/10/2026, meio-dia em São Paulo.
        Carbon::setTestNow('2026-10-14 15:00:00');
        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create(['name' => 'Rafael']);
        $this->project = $this->projeto(['manager_id' => $this->gestor->id, 'key_prefix' => 'SGM']);
        $this->ana = User::factory()->developer()->create(['name' => 'Ana', 'daily_hours' => 8]);
        $this->quesia = User::factory()->developer()->create(['name' => 'Quésia', 'daily_hours' => 4]);
        $this->alocar($this->project, $this->ana, $this->quesia);
        $this->encerrada = $this->sprint($this->project, ['name' => 'Sprint 0', 'starts_on' => '2026-09-22', 'ends_on' => '2026-10-05', 'closed_at' => '2026-10-05 20:00:00']);
        $this->aberta = $this->sprint($this->project, ['name' => 'Sprint 1']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function utc(string $local): string
    {
        return Carbon::parse($local, 'America/Sao_Paulo')->utc()->toDateTimeString();
    }

    /** Card criado no Backlog no dia dado (meia-noite local), com o movimento da criação. */
    private function card(string $name, array $attributes, string $createdOn = '2026-10-05', ?int $sprintId = 0): Task
    {
        $task = $this->tarefa($this->project, null, ['title' => $name, 'sprint_id' => $sprintId === 0 ? $this->aberta->id : $sprintId, ...$attributes]);
        TaskStageMovement::query()->forceCreate([
            'task_id' => $task->id, 'moved_by' => $this->gestor->id, 'from_board_stage_id' => null,
            'to_board_stage_id' => $task->board_stage_id, 'created_at' => $this->utc("{$createdOn} 00:00"),
        ]);
        $task->forceFill(['created_at' => $this->utc("{$createdOn} 00:00")])->save();

        return $this->t[$name] = $task;
    }

    private function mover(Task $task, int $role, string $local, ?User $by = null, ?string $reason = null): void
    {
        $to = $this->etapa($this->project, $role);
        TaskStageMovement::query()->forceCreate([
            'task_id' => $task->id, 'moved_by' => ($by ?? $this->gestor)->id,
            'from_board_stage_id' => $task->board_stage_id, 'to_board_stage_id' => $to->id,
            'reason_text' => $reason, 'created_at' => $this->utc($local),
        ]);
        $task->forceFill(['board_stage_id' => $to->id])->save();
    }

    private function lancar(Task $task, User $user, float $hours, string $day = '2026-10-08'): void
    {
        $task->timeEntries()->create(['user_id' => $user->id, 'worked_on' => $day, 'hours' => $hours]);
    }

    /** O projeto de exemplo das abas, com o cenário do F7 §06. */
    private function cenario(): void
    {
        // T1: atrasada. Prevista para sexta 09/10; Doing na terça 06/10; 1 dia útil em pausa → 3 − 1 = 2.
        $t1 = $this->card('T1', ['developer_id' => $this->ana->id, 'qa_id' => $this->quesia->id, 'estimated_hours' => 16, 'due_on' => '2026-10-09']);
        $this->mover($t1, StageRole::DEVELOPMENT, '2026-10-06 00:00');
        $this->mover($t1, StageRole::PAUSED, '2026-10-07 00:00');
        $this->mover($t1, StageRole::DEVELOPMENT, '2026-10-08 00:00');
        $this->lancar($t1, $this->ana, 4);
        $this->lancar($t1, $this->quesia, 2);
        // T2: no prazo, vence sexta 16/10; Doing desde terça 13/10 (1,5 dia útil).
        $t2 = $this->card('T2', ['developer_id' => $this->ana->id, 'estimated_hours' => 8, 'due_on' => '2026-10-16']);
        $this->mover($t2, StageRole::DEVELOPMENT, '2026-10-13 00:00');
        // T3: entrou em desenvolvimento sem dev e sem horas: sem data prevista.
        $t3 = $this->card('T3', []);
        $this->mover($t3, StageRole::DEVELOPMENT, '2026-10-13 00:00');
        // T4: em pausa com motivo, direto do Backlog.
        $t4 = $this->card('T4', ['developer_id' => $this->ana->id]);
        $this->mover($t4, StageRole::PAUSED, '2026-10-13 00:00', $this->gestor, 'Aguardando o cliente');
        // T5: nunca saiu do Backlog, fora de sprint, com horas previstas.
        $this->card('T5', ['estimated_hours' => 5], sprintId: null);
        // T6: arquivada, com horas previstas e lançadas.
        $t6 = $this->card('T6', ['developer_id' => $this->ana->id, 'estimated_hours' => 40, 'due_on' => '2026-10-01', 'archived_at' => now(), 'archive_reason' => 'Duplicada']);
        $this->lancar($t6, $this->ana, 3);
        // T7: pronta na Sprint 0, com data prevista vencida (sem atraso: pronta).
        $t7 = $this->card('T7', ['developer_id' => $this->ana->id, 'estimated_hours' => 10, 'due_on' => '2026-10-01'], '2026-09-28', $this->encerrada->id);
        $this->mover($t7, StageRole::DONE, '2026-10-02 00:00');
        // T8: bug de produção da T7, no Backlog, fora de sprint.
        $this->card('T8', ['origin_task_id' => $t7->id], '2026-10-10', null);
        // T9: Quésia dev, Ana QA; vence hoje.
        $t9 = $this->card('T9', ['developer_id' => $this->quesia->id, 'qa_id' => $this->ana->id, 'estimated_hours' => 4, 'due_on' => '2026-10-14']);
        $this->mover($t9, StageRole::DEVELOPMENT, '2026-10-13 00:00');
    }

    private function abrir(User $user, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get("/projetos/{$this->project->id}/indicadores?".http_build_query($query));
    }

    private function key(string $name): string
    {
        return $this->t[$name]->key();
    }

    public function test_situacao_counts_by_stage_and_lists_late_upcoming_paused_and_without_due_date(): void
    {
        $this->cenario();

        $this->abrir($this->gestor, ['aba' => 'situacao'])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('projects/indicators')
            ->where('tabs', ['situacao', 'tempo', 'horas', 'pessoas', 'qualidade', 'minhas'])
            ->where('filters.tab', 'situacao')
            ->where('today', '2026-10-14')
            ->where('stage_time', null)
            ->where('situation.by_stage', [
                ['id' => $this->etapa($this->project, StageRole::SUPPORT)->id, 'name' => 'Support', 'role' => 'support', 'count' => 0],
                ['id' => $this->etapa($this->project, StageRole::QUEUE)->id, 'name' => 'Backlog', 'role' => 'queue', 'count' => 2],
                ['id' => $this->etapa($this->project, StageRole::SPRINT)->id, 'name' => 'To do', 'role' => 'sprint', 'count' => 0],
                ['id' => $this->etapa($this->project, StageRole::PAUSED)->id, 'name' => 'Paused', 'role' => 'paused', 'count' => 1],
                ['id' => $this->etapa($this->project, StageRole::DEVELOPMENT)->id, 'name' => 'Doing', 'role' => 'development', 'count' => 4],
                ['id' => $this->etapa($this->project, StageRole::CODE_REVIEW)->id, 'name' => 'Code-review', 'role' => 'code_review', 'count' => 0],
                ['id' => $this->etapa($this->project, StageRole::TEST)->id, 'name' => 'In test', 'role' => 'test', 'count' => 0],
                ['id' => $this->etapa($this->project, StageRole::DONE)->id, 'name' => 'Done', 'role' => 'done', 'count' => 1],
            ])
            ->count('situation.late', 1)
            ->where('situation.late.0.task.key', $this->key('T1'))
            ->where('situation.late.0.developer', 'Ana')
            ->where('situation.late.0.due_on', '2026-10-09')
            ->where('situation.late.0.paused_days', 1)
            ->where('situation.late.0.delay_days', 2)
            ->where('situation.upcoming', [
                ['task' => $this->ref('T9'), 'developer' => 'Quésia', 'due_on' => '2026-10-14'],
                ['task' => $this->ref('T2'), 'developer' => 'Ana', 'due_on' => '2026-10-16'],
            ])
            ->where('situation.paused', [
                ['task' => $this->ref('T4'), 'developer' => 'Ana', 'reason' => 'Aguardando o cliente', 'since' => '13/10/2026 00:00', 'by' => 'Rafael'],
            ])
            ->where('situation.without_due', [
                ['task' => $this->ref('T3'), 'missing' => ['developer', 'hours']],
            ]));
    }

    public function test_tempo_por_etapa_adds_up_by_role_in_board_order_for_the_cards_that_left_the_backlog(): void
    {
        $this->cenario();

        // Todas as sprints: T1, T2, T3, T4, T7 e T9 saíram do Backlog; T5 e T8 não; T6 é arquivada.
        $this->abrir($this->gestor, ['aba' => 'tempo', 'sprint' => 'todas'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.sprint', 'todas')
            ->where('stage_time.rows', [
                // Fila: 1 + 6 + 6 + 6 + 4 + 6 = 29 dias úteis em 6 cards.
                ['role' => 'queue', 'label' => 'Fila', 'stages' => ['Backlog'], 'tasks' => 6, 'total_days' => 29, 'average_days' => 4.8],
                // Pausa: T1 (1) e T4 (1,5, até agora).
                ['role' => 'paused', 'label' => 'Pausa', 'stages' => ['Paused'], 'tasks' => 2, 'total_days' => 2.5, 'average_days' => 1.3],
                // Desenvolvimento: T1 (5,5), T2, T3 e T9 (1,5 cada).
                ['role' => 'development', 'label' => 'Desenvolvimento', 'stages' => ['Doing'], 'tasks' => 4, 'total_days' => 10, 'average_days' => 2.5],
            ]));

        // Sem pedir sprint, vale a aberta (F7 §05): T1 a T4 e T9.
        $this->abrir($this->gestor, ['aba' => 'tempo'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.sprint', (string) $this->aberta->id)
            ->where('stage_time.rows.0.tasks', 5)
            ->where('stage_time.rows.0.total_days', 25));

        // Fora de sprint: T5 e T8 nunca saíram do Backlog.
        $this->abrir($this->gestor, ['aba' => 'tempo', 'sprint' => 'fora'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stage_time.rows', []));
    }

    public function test_horas_compares_estimated_with_logged_and_shows_the_development_time_beside(): void
    {
        $this->cenario();

        $this->abrir($this->gestor, ['aba' => 'horas', 'sprint' => 'todas'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hours.rows', [
                ['task' => $this->ref('T1'), 'developer' => 'Ana', 'stage' => $this->stageOf(StageRole::DEVELOPMENT), 'archived' => false, 'estimated' => 16, 'logged' => 6, 'difference' => -10, 'development_hours' => 44],
                ['task' => $this->ref('T2'), 'developer' => 'Ana', 'stage' => $this->stageOf(StageRole::DEVELOPMENT), 'archived' => false, 'estimated' => 8, 'logged' => 0, 'difference' => -8, 'development_hours' => 12],
                ['task' => $this->ref('T5'), 'developer' => null, 'stage' => $this->stageOf(StageRole::QUEUE), 'archived' => false, 'estimated' => 5, 'logged' => 0, 'difference' => -5, 'development_hours' => null],
                ['task' => $this->ref('T6'), 'developer' => 'Ana', 'stage' => $this->stageOf(StageRole::QUEUE), 'archived' => true, 'estimated' => 40, 'logged' => 3, 'difference' => -37, 'development_hours' => 0],
                ['task' => $this->ref('T7'), 'developer' => 'Ana', 'stage' => $this->stageOf(StageRole::DONE), 'archived' => false, 'estimated' => 10, 'logged' => 0, 'difference' => -10, 'development_hours' => 0],
                ['task' => $this->ref('T9'), 'developer' => 'Quésia', 'stage' => $this->stageOf(StageRole::DEVELOPMENT), 'archived' => false, 'estimated' => 4, 'logged' => 0, 'difference' => -4, 'development_hours' => 6],
            ])
            // As previstas da arquivada ficam fora do total (F7 §10.5); as lançadas dela ficam.
            ->where('hours.totals', ['estimated' => 43, 'logged' => 9, 'difference' => -34, 'development_hours' => 62]));

        // Filtros: dev, busca por chave e por título.
        $this->abrir($this->gestor, ['aba' => 'horas', 'sprint' => 'todas', 'developer_id' => $this->quesia->id])
            ->assertInertia(fn (AssertableInertia $page) => $page->count('hours.rows', 1)->where('hours.rows.0.task.key', $this->key('T9')));
        $this->abrir($this->gestor, ['aba' => 'horas', 'sprint' => 'todas', 'search' => $this->key('T2')])
            ->assertInertia(fn (AssertableInertia $page) => $page->count('hours.rows', 1)->where('hours.rows.0.task.key', $this->key('T2')));
        $this->abrir($this->gestor, ['aba' => 'horas', 'sprint' => 'todas', 'search' => 't7'])
            ->assertInertia(fn (AssertableInertia $page) => $page->count('hours.rows', 1)->where('hours.rows.0.task.key', $this->key('T7')));
    }

    public function test_por_pessoa_adds_up_by_developer_with_the_hours_each_person_logged(): void
    {
        $this->cenario();

        $this->abrir($this->gestor, ['aba' => 'pessoas', 'sprint' => 'todas'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('people.rows', [
                // Ana: dev de T1, T2, T4 e T7 (a T6 arquivada só entra pelas 3 h lançadas).
                ['person' => ['id' => $this->ana->id, 'name' => 'Ana'], 'daily_hours' => 8, 'tasks' => 4, 'estimated' => 34, 'logged' => 7, 'development_hours' => 56, 'late' => 1, 'paused' => 1],
                // Quésia: dev da T9 e 2 h lançadas como QA da T1.
                ['person' => ['id' => $this->quesia->id, 'name' => 'Quésia'], 'daily_hours' => 4, 'tasks' => 1, 'estimated' => 4, 'logged' => 2, 'development_hours' => 6, 'late' => 0, 'paused' => 0],
                // Sem dev: T3, T5 e T8.
                ['person' => null, 'daily_hours' => null, 'tasks' => 3, 'estimated' => 5, 'logged' => 0, 'development_hours' => 0, 'late' => 0, 'paused' => 0],
            ]));
    }

    public function test_qualidade_lists_the_production_bugs_with_the_original_task(): void
    {
        $this->cenario();

        $this->abrir($this->gestor, ['aba' => 'qualidade'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('quality.total', 1)
            ->where('quality.rows', [
                ['task' => $this->ref('T8'), 'origin' => $this->ref('T7'), 'created_on' => '2026-10-10', 'stage' => $this->stageOf(StageRole::QUEUE), 'archived' => false],
            ]));
    }

    public function test_minhas_tarefas_lists_my_open_cards_as_dev_or_qa_late_first(): void
    {
        $this->cenario();

        $this->abrir($this->ana, ['aba' => 'situacao'])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            // O dev não tem as abas do gestor: pedir uma delas cai na dele, sem o conteúdo (P54).
            ->where('tabs', ['minhas'])
            ->where('filters.tab', 'minhas')
            ->where('situation', null)
            ->where('mine.rows', [
                ['task' => $this->ref('T1'), 'role' => 'developer', 'stage' => $this->stageOf(StageRole::DEVELOPMENT), 'due_on' => '2026-10-09', 'missing' => [], 'delay_days' => 2, 'estimated' => 16, 'logged_by_me' => 4, 'development_hours' => 44],
                ['task' => $this->ref('T9'), 'role' => 'qa', 'stage' => $this->stageOf(StageRole::DEVELOPMENT), 'due_on' => '2026-10-14', 'missing' => [], 'delay_days' => 0, 'estimated' => 4, 'logged_by_me' => 0, 'development_hours' => null],
                ['task' => $this->ref('T2'), 'role' => 'developer', 'stage' => $this->stageOf(StageRole::DEVELOPMENT), 'due_on' => '2026-10-16', 'missing' => [], 'delay_days' => 0, 'estimated' => 8, 'logged_by_me' => 0, 'development_hours' => 12],
                ['task' => $this->ref('T4'), 'role' => 'developer', 'stage' => $this->stageOf(StageRole::PAUSED), 'due_on' => null, 'missing' => ['hours'], 'delay_days' => null, 'estimated' => null, 'logged_by_me' => 0, 'development_hours' => 0],
            ]));

        $keys = fn (TestResponse $response) => array_column(array_column($response->inertiaProps()['mine']['rows'], 'task'), 'key');
        $this->assertSame([$this->key('T1')], $keys($this->abrir($this->ana, ['due' => 'late'])));
        $this->assertSame([$this->key('T4')], $keys($this->abrir($this->ana, ['due' => 'none'])));
        // "Vencem até" inclui a que já venceu.
        $this->assertSame([$this->key('T1'), $this->key('T9')], $keys($this->abrir($this->ana, ['due' => 'until', 'until' => '2026-10-14'])));
        $this->assertSame([$this->key('T2')], $keys($this->abrir($this->ana, ['search' => 'T2'])));
        // O gestor também tem a aba, com as tarefas dele: nenhuma.
        $this->abrir($this->gestor, ['aba' => 'minhas'])->assertInertia(fn (AssertableInertia $page) => $page->where('mine.rows', []));
    }

    public function test_who_opens_the_indicators(): void
    {
        $admin = User::factory()->admin()->create();
        $foraDoProjeto = User::factory()->developer()->create();
        $comercial = User::factory()->sales()->create();
        $outroGestor = User::factory()->projectManager()->create();

        $this->abrir($admin)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->count('tabs', 6)->where('filters.tab', 'situacao'));
        $this->abrir($this->gestor)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->count('tabs', 6));
        $this->abrir($this->ana)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('tabs', ['minhas']));
        $this->abrir($foraDoProjeto)->assertForbidden();
        $this->abrir($outroGestor)->assertForbidden();
        $this->abrir($comercial)->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['user_id' => $foraDoProjeto->id, 'outcome' => 'denied']);
    }

    public function test_filter_options_are_the_sprints_people_types_and_areas_of_the_project(): void
    {
        $this->abrir($this->gestor)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('options.sprints', [
                ['id' => $this->aberta->id, 'name' => 'Sprint 1', 'open' => true],
                ['id' => $this->encerrada->id, 'name' => 'Sprint 0', 'open' => false],
            ])
            ->where('options.developers', [['id' => $this->ana->id, 'name' => 'Ana'], ['id' => $this->quesia->id, 'name' => 'Quésia']])
            ->count('options.types', 5)
            ->where('options.areas', []));
    }

    public function test_the_number_of_queries_does_not_grow_with_the_cards(): void
    {
        $this->cenario();
        $contar = function (string $tab): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->abrir($this->gestor, ['aba' => $tab, 'sprint' => 'todas'])->assertOk();
            $this->abrir($this->ana, ['aba' => $tab])->assertOk();
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };
        $tabs = ['situacao', 'tempo', 'horas', 'pessoas', 'qualidade', 'minhas'];
        $poucos = array_map($contar, $tabs);
        $this->semeiaCards(500);

        $this->assertSame($poucos, array_map($contar, $tabs), 'os indicadores consultam por card');
    }

    /** Cards variados direto no banco: metade da Ana, alguns em pausa, com horas e movimentos. */
    private function semeiaCards(int $quantos): void
    {
        $backlog = $this->etapa($this->project, StageRole::QUEUE)->id;
        $doing = $this->etapa($this->project, StageRole::DEVELOPMENT)->id;
        $paused = $this->etapa($this->project, StageRole::PAUSED)->id;
        $tipo = $this->tipo($this->project, 'improvement');
        $start = (int) $this->project->fresh()->last_task_number;
        $tasks = [];
        for ($i = 1; $i <= $quantos; $i++) {
            $stage = $i % 7 === 0 ? $paused : $doing;
            $tasks[] = [
                'project_id' => $this->project->id, 'number' => $start + $i, 'board_stage_id' => $stage, 'task_type_id' => $tipo,
                'task_priority_id' => 2, 'title' => "Card {$i}", 'sprint_id' => $this->aberta->id,
                'developer_id' => $i % 2 === 0 ? $this->ana->id : ($i % 5 === 0 ? $this->quesia->id : null),
                'qa_id' => $i % 3 === 0 ? $this->ana->id : null,
                'estimated_hours' => $i % 4 === 0 ? 8 : null, 'due_on' => $i % 4 === 0 ? '2026-10-09' : null,
                'origin_task_id' => $i % 11 === 0 ? $this->t['T7']->id : null,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        DB::table('tasks')->insert($tasks);
        $this->project->forceFill(['last_task_number' => $start + $quantos])->save();
        $ids = DB::table('tasks')->where('project_id', $this->project->id)->where('number', '>', $start)->pluck('id');
        $movements = [];
        $entries = [];
        foreach ($ids as $id) {
            $movements[] = ['task_id' => $id, 'moved_by' => $this->gestor->id, 'from_board_stage_id' => null, 'to_board_stage_id' => $backlog, 'created_at' => $this->utc('2026-10-05 00:00')];
            $movements[] = ['task_id' => $id, 'moved_by' => $this->gestor->id, 'from_board_stage_id' => $backlog, 'to_board_stage_id' => $doing, 'created_at' => $this->utc('2026-10-06 00:00')];
            $entries[] = ['task_id' => $id, 'user_id' => $this->ana->id, 'worked_on' => '2026-10-08', 'hours' => 1, 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($movements, 500) as $chunk) {
            DB::table('task_stage_movements')->insert($chunk);
        }
        foreach (array_chunk($entries, 500) as $chunk) {
            DB::table('time_entries')->insert($chunk);
        }
    }

    /** @return array{id: int, key: string, title: string, url: string} */
    private function ref(string $name): array
    {
        $task = $this->t[$name];

        return ['id' => $task->id, 'key' => $task->key(), 'title' => $name, 'url' => $task->url()];
    }

    /** @return array{id: int, name: string, role: string} */
    private function stageOf(int $role): array
    {
        $stage = $this->etapa($this->project, $role);

        return ['id' => $stage->id, 'name' => $stage->name, 'role' => $stage->stageRole->slug];
    }
}
