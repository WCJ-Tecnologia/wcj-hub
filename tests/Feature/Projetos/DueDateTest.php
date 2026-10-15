<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Services\DueDateService;
use App\Domains\Projetos\Services\TaskHoursService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Data prevista (P27, P28, P29, N3; F5 §02 "Como a data prevista é calculada", §10.8, §10.9) e as
 * horas previstas pelo gestor (TaskHoursService): definir, reestimar, aprovar e recusar a proposta.
 */
class DueDateTest extends TestCase
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

    /** Tarefa do dev que entrou em Doing no instante (UTC) dado. */
    private function emDesenvolvimento(?float $daily, ?string $hours, string $utc = '2026-10-08 13:00:00', bool $withDev = true): Task
    {
        $dev = User::factory()->developer()->create(['daily_hours' => $daily]);
        $task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), [
            'developer_id' => $withDev ? $dev->id : null,
            'estimated_hours' => $hours,
        ]);
        $this->moverEm($task, $this->etapa($this->project, StageRole::DEVELOPMENT), $utc);

        return $task->fresh();
    }

    /** @return array<string, array{float, string, string, string}> */
    public static function exemplos(): array
    {
        return [
            // F5 §02, exemplo da P28: entrada na quinta 08/10/2026 com 16 h.
            'dev de 8 h' => [8, '16', '2026-10-08 13:00:00', '2026-10-09'],
            'dev de 4 h, o feriado de 12/10 conta' => [4, '16', '2026-10-08 13:00:00', '2026-10-13'],
            // Fração arredonda para cima (F5 §10.9): 10 h / 8 = 2 dias.
            'fração' => [8, '10', '2026-10-08 13:00:00', '2026-10-09'],
            'meio dia' => [8, '0.5', '2026-10-08 13:00:00', '2026-10-08'],
            // 23:30 em São Paulo ainda é dia 08 (02:30 UTC do dia 09).
            'noite em São Paulo' => [8, '16', '2026-10-09 02:30:00', '2026-10-09'],
            // Entrada no sábado conta a partir da segunda.
            'sábado' => [8, '8', '2026-10-10 13:00:00', '2026-10-12'],
        ];
    }

    #[DataProvider('exemplos')]
    public function test_due_date_counts_business_days_from_the_first_entry_in_development(float $daily, string $hours, string $utc, string $expected): void
    {
        $task = $this->emDesenvolvimento($daily, $hours, $utc);

        $this->assertSame($expected, app(DueDateService::class)->calculate($task));
    }

    public function test_due_date_counts_from_the_first_entry_even_after_rework(): void
    {
        $task = $this->emDesenvolvimento(8, '16');
        $this->moverEm($task, $this->etapa($this->project, StageRole::TEST), '2026-10-12 13:00:00');
        $this->moverEm($task, $this->etapa($this->project, StageRole::DEVELOPMENT), '2026-10-14 13:00:00');

        $this->assertSame('2026-10-09', app(DueDateService::class)->calculate($task->fresh()));
    }

    public function test_no_due_date_without_dev_hours_daily_load_or_entry(): void
    {
        $dueDates = app(DueDateService::class);

        $this->assertNull($dueDates->calculate($this->emDesenvolvimento(8, null)));
        $this->assertNull($dueDates->calculate($this->emDesenvolvimento(null, '16')));
        $this->assertNull($dueDates->calculate($this->emDesenvolvimento(8, '16', withDev: false)));

        $dev = User::factory()->developer()->create();
        $nuncaEntrou = $this->tarefa($this->project, null, ['developer_id' => $dev->id, 'estimated_hours' => 16]);
        $this->assertNull($dueDates->calculate($nuncaEntrou));
    }

    public function test_apply_fills_a_missing_date_but_keeps_an_existing_one_unless_reestimating(): void
    {
        $task = $this->emDesenvolvimento(8, '16');
        $dueDates = app(DueDateService::class);

        $dueDates->apply($task);
        $this->assertSame('2026-10-09', $task->due_on->toDateString());

        // Trocar o dev ou a carga não mexe na data (N3, F5 §10.8).
        User::query()->whereKey($task->developer_id)->update(['daily_hours' => 4]);
        $dueDates->apply($task);
        $this->assertSame('2026-10-09', $task->due_on->toDateString());

        $dueDates->apply($task, reestimate: true);
        $this->assertSame('2026-10-13', $task->due_on->toDateString());
    }

    public function test_manager_reestimate_redoes_the_date_from_the_same_entry_and_audits_the_before(): void
    {
        $task = $this->emDesenvolvimento(8, '16');
        app(DueDateService::class)->apply($task);
        $task->save();

        app(TaskHoursService::class)->estimate($task, '32', $this->gestor);

        $task->refresh();
        $this->assertSame(['32.00', '2026-10-13'], [$task->estimated_hours, $task->due_on->toDateString()]);
        $log = AuditLog::query()->where('auditable_type', 'task')->where('action', 'updated')->sole();
        $this->assertSame(['2026-10-09', '2026-10-13'], $log->changes['due_on']);
        $this->assertSame(['16.00', '32.00'], $log->changes['estimated_hours']);
        $this->assertSame($this->gestor->id, $log->user_id);
    }

    public function test_setting_hours_on_a_task_already_in_doing_calculates_the_date_at_once(): void
    {
        $task = $this->emDesenvolvimento(8, null);

        app(TaskHoursService::class)->estimate($task, '16', $this->gestor);

        $this->assertSame('2026-10-09', $task->fresh()->due_on->toDateString());
    }

    public function test_clearing_hours_clears_the_date_and_the_same_value_writes_nothing(): void
    {
        $task = $this->emDesenvolvimento(8, '16');
        $hours = app(TaskHoursService::class);
        $hours->estimate($task, '16', $this->gestor);
        $this->assertSame(0, AuditLog::query()->count());

        $hours->estimate($task->fresh(), '16.0', $this->gestor);
        $this->assertSame(0, AuditLog::query()->count());

        $hours->estimate($task->fresh(), null, $this->gestor);
        $this->assertSame([null, null], [$task->fresh()->estimated_hours, $task->fresh()->due_on]);
    }

    public function test_approving_the_proposal_copies_it_and_redoes_the_date(): void
    {
        $task = $this->emDesenvolvimento(8, '16');
        $task->forceFill(['due_on' => '2026-10-09', 'proposed_hours' => 24])->save();

        app(TaskHoursService::class)->approveProposal($task, $this->gestor);

        $task->refresh();
        $this->assertSame(['24.00', null, '2026-10-12'], [$task->estimated_hours, $task->proposed_hours, $task->due_on->toDateString()]);
    }

    public function test_rejecting_the_proposal_only_clears_it(): void
    {
        $task = $this->emDesenvolvimento(8, '16');
        $task->forceFill(['due_on' => '2026-10-09', 'proposed_hours' => 24])->save();

        app(TaskHoursService::class)->rejectProposal($task, $this->gestor);

        $task->refresh();
        $this->assertSame(['16.00', null, '2026-10-09'], [$task->estimated_hours, $task->proposed_hours, $task->due_on->toDateString()]);
    }

    public function test_deciding_without_proposal_is_refused(): void
    {
        $task = $this->emDesenvolvimento(8, '16');

        $this->expectExceptionObject(new DomainException(TaskHoursService::NO_PROPOSAL));
        app(TaskHoursService::class)->approveProposal($task, $this->gestor);
    }
}
