<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TimeEntry;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** *Minhas horas* e o lançamento (F5 §02, §03, §05, §07; P30): período, totais, busca e recusas. */
class TimeEntryControllerTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $dev;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Quinta 15/10/2026, 23:30 em São Paulo (02:30 UTC do dia 16): "hoje" ainda é 15.
        Carbon::setTestNow(Carbon::parse('2026-10-16 02:30:00', 'UTC'));
        $this->semearProjetos();
        $this->dev = User::factory()->developer()->create();
        $this->project = $this->projeto();
        $this->alocar($this->project, $this->dev);
        $this->task = $this->tarefa($this->project, null, ['title' => 'Prazo de recurso', 'developer_id' => $this->dev->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function entry(string $date, float $hours, ?User $user = null, ?Task $task = null): TimeEntry
    {
        return ($task ?? $this->task)->timeEntries()->create(['user_id' => ($user ?? $this->dev)->id, 'worked_on' => $date, 'hours' => $hours]);
    }

    public function test_default_period_is_the_current_week_with_totals_per_day_and_period(): void
    {
        $this->entry('2026-10-12', 2);
        $this->entry('2026-10-15', 1.5);
        $this->entry('2026-10-15', 3);
        $this->entry('2026-10-11', 8); // domingo da semana anterior
        $this->entry('2026-10-14', 5, User::factory()->developer()->create()); // de outra pessoa

        $this->actingAs($this->dev)->get('/minhas-horas')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('time-entries/index')
                ->where('filters.from', '2026-10-12')
                ->where('filters.to', '2026-10-18')
                ->where('today', '2026-10-15')
                ->has('entries', 3)
                ->where('entries.0.worked_on', '2026-10-15')
                ->where('entries.0.can', ['update' => true, 'delete' => true])
                ->where('totals', ['days' => [['date' => '2026-10-15', 'hours' => 4.5], ['date' => '2026-10-12', 'hours' => 2]], 'total' => 6.5])
                ->where('tasks.0.id', $this->task->id)
                ->where('tasks.0.mine', true));
    }

    public function test_search_by_key_and_project_filter(): void
    {
        $outra = $this->tarefa($this->project, null, ['title' => 'Filtro por placa']);
        $this->entry('2026-10-13', 1);
        $this->entry('2026-10-13', 2, null, $outra);
        $key = "{$this->project->key_prefix}-{$outra->number}";

        $this->actingAs($this->dev)->get("/minhas-horas?search={$key}")
            ->assertInertia(fn (AssertableInertia $page) => $page->has('entries', 1)->where('entries.0.task.key', $key));
        $this->actingAs($this->dev)->get('/minhas-horas?search=recurso')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('entries', 1)->where('entries.0.task.title', 'Prazo de recurso'));
        $this->actingAs($this->dev)->get('/minhas-horas?project_id=999999')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('entries', 0));
    }

    /** @return array<string, array{array<string, mixed>, string, string}> */
    public static function recusas(): array
    {
        return [
            'zero' => [['worked_on' => '2026-10-15', 'hours' => 0], 'hours', 'Informe horas maiores que 0 e até 24'],
            'mais de 24' => [['worked_on' => '2026-10-15', 'hours' => 24.5], 'hours', 'Informe horas maiores que 0 e até 24'],
            'amanhã' => [['worked_on' => '2026-10-16', 'hours' => 1], 'worked_on', 'A data não pode ser futura'],
            'sem data' => [['hours' => 1], 'worked_on', 'O campo data é obrigatório.'],
        ];
    }

    /** @param  array<string, mixed>  $data */
    #[DataProvider('recusas')]
    public function test_refuses_out_of_range_hours_and_future_dates(array $data, string $field, string $message): void
    {
        $this->actingAs($this->dev)->post("/projetos/{$this->project->id}/tarefas/{$this->task->id}/horas", $data)
            ->assertSessionHasErrors([$field => $message]);

        $this->assertSame(0, TimeEntry::query()->count());
    }

    public function test_today_in_sao_paulo_and_24_hours_are_accepted(): void
    {
        $this->actingAs($this->dev)->post("/projetos/{$this->project->id}/tarefas/{$this->task->id}/horas", ['worked_on' => '2026-10-15', 'hours' => 24])
            ->assertSessionHasNoErrors();

        $this->assertSame('24.00', TimeEntry::query()->sole()->hours);
    }

    public function test_archived_task_is_a_toast_error(): void
    {
        $this->task->forceFill(['archived_at' => now(), 'archive_reason' => 'Duplicado'])->save();

        $this->actingAs($this->dev)->post("/projetos/{$this->project->id}/tarefas/{$this->task->id}/horas", ['worked_on' => '2026-10-15', 'hours' => 1])
            ->assertSessionHas('error', 'O card está arquivado: não recebe lançamento novo.');
    }

    public function test_task_of_another_project_in_the_route_is_404(): void
    {
        $alheia = $this->tarefa($this->projeto());

        $this->actingAs($this->dev)->post("/projetos/{$this->project->id}/tarefas/{$alheia->id}/horas", ['worked_on' => '2026-10-15', 'hours' => 1])->assertNotFound();
    }
}
