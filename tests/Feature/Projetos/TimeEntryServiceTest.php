<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\TimeEntry;
use App\Domains\Projetos\Services\TimeEntryService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** TimeEntryService (P30; F5 §04, §10.12 a §10.14): lançar, corrigir e excluir, sempre auditado. */
class TimeEntryServiceTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    public function test_create_update_and_delete_are_audited_with_the_before(): void
    {
        $this->semearProjetos();
        $dev = User::factory()->developer()->create();
        $task = $this->tarefa($this->projeto());
        $entries = app(TimeEntryService::class);

        $entry = $entries->create($task, ['worked_on' => '2026-10-06', 'hours' => '2.5'], $dev);
        $this->assertSame([$dev->id, '2.50', '2026-10-06'], [$entry->user_id, $entry->hours, $entry->worked_on->toDateString()]);

        $entries->update($entry, ['worked_on' => '2026-10-07', 'hours' => '3'], $dev);
        $entries->delete($entry, $dev);

        $this->assertNull(TimeEntry::query()->find($entry->id));
        $logs = AuditLog::query()->where('auditable_type', 'time_entry')->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('action')->all());
        $this->assertSame(['2.50', '3.00'], $logs[1]->changes['hours']);
        $this->assertSame(['2026-10-07', null], $logs[2]->changes['worked_on']);
        $this->assertSame([$task->id, null], $logs[2]->changes['task_id']);
        // Verifier, rodada 1, gap 4: o antes da exclusão leva a pessoa e as horas (DEL-02 AC 4).
        $this->assertSame([$dev->id, null], $logs[2]->changes['user_id']);
        $this->assertSame(['3.00', null], $logs[2]->changes['hours']);
    }

    public function test_more_than_one_entry_on_the_same_day_and_task_is_fine(): void
    {
        $this->semearProjetos();
        $dev = User::factory()->developer()->create();
        $task = $this->tarefa($this->projeto());

        app(TimeEntryService::class)->create($task, ['worked_on' => '2026-10-06', 'hours' => '20'], $dev);
        app(TimeEntryService::class)->create($task, ['worked_on' => '2026-10-06', 'hours' => '20'], $dev);

        // F5 §10.12: a soma do dia não é conferida.
        $this->assertSame(2, $task->timeEntries()->count());
    }

    public function test_archived_task_does_not_take_a_new_entry(): void
    {
        $this->semearProjetos();
        $task = $this->tarefa($this->projeto(), null, ['archived_at' => now(), 'archive_reason' => 'Duplicado']);

        $this->expectExceptionObject(new DomainException(TimeEntryService::ARCHIVED_TASK));
        app(TimeEntryService::class)->create($task, ['worked_on' => '2026-10-06', 'hours' => '1'], User::factory()->developer()->create());
    }

    public function test_the_database_refuses_hours_out_of_range(): void
    {
        $this->semearProjetos();
        $task = $this->tarefa($this->projeto());

        $this->expectException(QueryException::class);
        app(TimeEntryService::class)->create($task, ['worked_on' => '2026-10-06', 'hours' => '24.5'], User::factory()->developer()->create());
    }
}
