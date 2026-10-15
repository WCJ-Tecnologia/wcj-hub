<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TimeEntry;
use App\Domains\Projetos\Services\DueDateService;
use App\Domains\Projetos\Services\SprintService;
use App\Domains\Projetos\Services\SprintTaskMover;
use App\Domains\Projetos\Services\SprintTaskService;
use App\Domains\Projetos\Services\TaskAssignmentService;
use App\Domains\Projetos\Services\TaskHoursService;
use App\Domains\Projetos\Services\TimeEntryService;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * DEL-01 AC 1 e DEL-02 AC 4 (Verifier, rodada 1, gap 5): a exclusão e a auditoria são um commit só
 * (G3). Se a auditoria falha, a sprint e o lançamento continuam lá. Só o banco de verdade prova,
 * sem o RefreshDatabase (que envolve o teste numa transação e esconderia a falta dela).
 */
class B5TransacaoTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    private User $gestor;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        parent::tearDown();
    }

    private function failingAudit(): AuditService
    {
        return new class extends AuditService
        {
            public function log(AuditActionEnum $action, Model $auditable, ?array $before = null, ?array $after = null, ?int $userId = null, ?int $aiAuthorizationId = null): AuditLog
            {
                throw new RuntimeException('a auditoria falhou');
            }
        };
    }

    /** Roda e devolve a mensagem da falha (o fail() do PHPUnit também é RuntimeException). */
    private function failure(\Closure $write): ?string
    {
        try {
            $write();
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        return null;
    }

    public function test_sprint_delete_rolls_back_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $sprint = $this->sprint($this->projeto(['manager_id' => $this->gestor->id]));
        $audit = $this->failingAudit();
        $service = new SprintService($audit, new SprintTaskMover($audit));

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->delete($sprint, $this->gestor)));

        $this->assertTrue(Sprint::query()->whereKey($sprint->id)->exists());
    }

    public function test_time_entry_delete_rolls_back_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $dev = User::factory()->developer()->create();
        $entry = $this->tarefa($this->projeto())->timeEntries()->create(['user_id' => $dev->id, 'worked_on' => '2026-10-06', 'hours' => 2]);
        $service = new TimeEntryService($this->failingAudit());

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->delete($entry, $dev)));

        $this->assertTrue(TimeEntry::query()->whereKey($entry->id)->exists());
    }

    /**
     * *Adicionar do Backlog* (D-GER-35): a sprint nova e as tarefas são um commit só. A auditoria
     * passa na sprint e na 1ª tarefa e falha na 2ª: nada fica, nem a sprint, nem a 1ª tarefa.
     */
    public function test_batch_rolls_back_the_new_sprint_and_every_task_when_one_write_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $project = $this->projeto(['manager_id' => $this->gestor->id]);
        $tasks = [$this->tarefa($project), $this->tarefa($project), $this->tarefa($project)];
        $audit = new class extends AuditService
        {
            private int $calls = 0;

            public function log(AuditActionEnum $action, Model $auditable, ?array $before = null, ?array $after = null, ?int $userId = null, ?int $aiAuthorizationId = null): AuditLog
            {
                if (++$this->calls === 3) {
                    throw new RuntimeException('a auditoria falhou');
                }

                return parent::log($action, $auditable, $before, $after, $userId, $aiAuthorizationId);
            }
        };
        $mover = new SprintTaskMover($audit);
        $dueDates = new DueDateService;
        $service = new SprintTaskService(new SprintService($audit, $mover), $mover, new TaskAssignmentService($audit, $dueDates), new TaskHoursService($audit, $dueDates));
        $nova = ['name' => 'Sprint 1', 'goal' => null, 'starts_on' => '2026-10-06', 'ends_on' => '2026-10-19'];

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->addMany($project, array_map(fn ($task) => ['id' => $task->id], $tasks), $this->gestor, $nova)));

        $this->assertSame(0, Sprint::query()->count());
        foreach ($tasks as $task) {
            $this->assertSame([null, $task->board_stage_id], [$task->fresh()->sprint_id, $task->fresh()->board_stage_id]);
        }
        $this->assertSame(0, TaskStageMovement::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }
}
