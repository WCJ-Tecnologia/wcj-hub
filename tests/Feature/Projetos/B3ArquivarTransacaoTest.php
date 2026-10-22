<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Notifications\TaskAssigned;
use App\Domains\Projetos\Services\SprintService;
use App\Domains\Projetos\Services\TaskArchiveService;
use App\Domains\Projetos\Services\TaskCommentService;
use App\Domains\Projetos\Services\TaskUsageQuery;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\Concorrencia;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * A exclusão do card nunca usado no banco de verdade (spec b3-arquivar-e-avisos; Verifier rodada 1,
 * Fix 1 e Fix 2; L-017): a linha do card fica travada da checagem ao delete, e o card, o que sai
 * junto, o link dos avisos e a linha `deleted` da auditoria são um commit só. Sem o RefreshDatabase:
 * a outra conexão só enxerga dado commitado.
 */
class B3ArquivarTransacaoTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    private Project $project;

    private User $gestor;

    private User $dev;

    private Task $task;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
        $this->alocar($this->project, $this->dev);

        // O card nunca usado, com o que sai junto: critério, participante e o movimento de criação.
        $this->task = $this->tarefa($this->project);
        TaskStageMovement::query()->create(['task_id' => $this->task->id, 'moved_by' => $this->dev->id, 'from_board_stage_id' => null, 'to_board_stage_id' => $this->task->board_stage_id]);
        $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'CA', 'test_result_id' => TestResult::PENDING]);
        $this->task->participants()->attach($this->dev->id);
        $this->dev->notify(new TaskAssigned($this->task->load('project'), $this->gestor, 'dev'));
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
    private function failure(Closure $write): ?string
    {
        try {
            $write();
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        return null;
    }

    private function noticeUrl(): ?string
    {
        return DatabaseNotification::query()->sole()->data['url'];
    }

    /**
     * Fix 1: o comentário gravado entre a checagem do "nunca usado" e o delete espera a linha do card.
     * Sem a trava, ele entraria, e o CASCADE de task_comments o apagaria junto com o card.
     */
    public function test_a_comment_written_between_the_check_and_the_delete_waits_for_the_card_row(): void
    {
        $taskId = $this->task->id;
        $devId = $this->dev->id;

        $corrida = Concorrencia::intercalarDepoisDe(
            fn (QueryExecuted $consulta) => str_contains($consulta->sql, 'movements_count'),
            // Relido na outra conexão: o model carrega a conexão em que nasceu.
            fn () => app(TaskCommentService::class)->add(Task::query()->findOrFail($taskId), User::query()->findOrFail($devId), 'No meio da exclusão'),
        );

        app(TaskArchiveService::class)->delete($this->task, $this->gestor);

        $this->assertTrue($corrida->rodou, 'o gatilho casou com a consulta do isUnused');
        $this->assertNull($corrida->outroErro);
        $this->assertTrue($corrida->bloqueada, 'o comentário esperou a linha do card');
        $this->assertNull(Task::query()->find($taskId));
        $this->assertSame(0, DB::table('task_comments')->count());
        $this->assertSame(0, AuditLog::query()->where('auditable_type', 'task_comment')->count(), 'nenhum comentário commitado e depois apagado');
    }

    /** Fix 2: a auditoria falha → o card, o que sai junto e o link do aviso continuam. */
    public function test_delete_rolls_back_the_card_and_the_notice_link_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $service = new TaskArchiveService($this->failingAudit(), app(SprintService::class), app(TaskUsageQuery::class));

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->delete($this->task, $this->gestor)));

        $this->assertNotNull(Task::query()->find($this->task->id), 'o card continua');
        $this->assertSame([1, 1, 1], [
            DB::table('task_acceptance_criteria')->where('task_id', $this->task->id)->count(),
            DB::table('task_participants')->where('task_id', $this->task->id)->count(),
            TaskStageMovement::query()->where('task_id', $this->task->id)->count(),
        ]);
        $this->assertSame($this->task->url(), $this->noticeUrl(), 'o aviso segue com o link');
    }

    /** Fix 2: dentro de uma transação de fora, o link do aviso já saiu antes do commit, junto com o card. */
    public function test_the_notice_link_leaves_in_the_same_transaction_as_the_card(): void
    {
        DB::transaction(function () {
            app(TaskArchiveService::class)->delete($this->task, $this->gestor);

            $this->assertNull(Task::query()->find($this->task->id), 'o card já saiu na transação');
            $this->assertNull($this->noticeUrl(), 'o link já saiu na mesma transação');
            $this->assertSame(1, AuditLog::query()->where('auditable_type', 'task')->where('action', 'deleted')->count());
        });

        $this->assertNull($this->noticeUrl());
    }
}
