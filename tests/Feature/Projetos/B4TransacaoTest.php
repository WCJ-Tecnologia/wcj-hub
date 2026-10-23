<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Services\AttachmentService;
use App\Domains\Projetos\Services\CriterionService;
use App\Domains\Projetos\Services\SprintService;
use App\Domains\Projetos\Services\TaskDetailPresenter;
use App\Domains\Projetos\Services\TaskService;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * As escritas da seção Teste e a linha da auditoria são um commit só (spec b4-teste-e-qa, premissa
 * "A auditoria"; CRUZ-01 AC 2, ROTEIRO-01 AC 4, EVID-01; Verifier rodada 1, Fix 2): se a auditoria
 * falha, nada muda, e o arquivo da evidência sai do disco. Só o banco de verdade prova, sem o
 * RefreshDatabase (no molde do B3FichaTransacaoTest).
 */
class B4TransacaoTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    private Project $project;

    private Task $task;

    private User $qa;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->semearProjetos();
        Storage::fake(TaskDetailPresenter::DISK);
        $this->qa = User::factory()->developer()->create();
        $this->project = $this->projeto();
        $this->alocar($this->project, $this->qa);
        $this->task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::TEST));
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

    private function tasks(): TaskService
    {
        return new TaskService($this->failingAudit(), app(SprintService::class));
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

    public function test_the_cross_test_mark_rolls_back_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $this->tasks()->markCrossTested($this->task, $this->qa)));

        $this->assertNull($this->task->fresh()->cross_tested_by);
        $this->assertNull($this->task->fresh()->cross_tested_at);
    }

    public function test_the_undo_rolls_back_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $this->task->forceFill(['cross_tested_by' => $this->qa->id, 'cross_tested_at' => now()])->save();

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $this->tasks()->undoCrossTested($this->task, $this->qa)));

        $this->assertSame($this->qa->id, $this->task->fresh()->cross_tested_by, 'a marca continua');
    }

    public function test_the_item_result_rolls_back_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $item = $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'CA', 'test_result_id' => TestResult::PENDING]);
        $service = new CriterionService($this->failingAudit(), app(SprintService::class));

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->setResult($item, $this->qa, 'failed')));

        $item->refresh();
        $this->assertSame([TestResult::PENDING, null, null], [$item->test_result_id, $item->marked_by, $item->marked_at]);
    }

    public function test_the_evidence_rolls_back_and_leaves_no_file_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $item = $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'CA', 'test_result_id' => TestResult::FAILED]);
        $service = new AttachmentService($this->failingAudit(), app(SprintService::class));

        $this->assertSame('a auditoria falhou', $this->failure(
            fn () => $service->upload($this->task, $this->qa, [UploadedFile::fake()->image('print.png')], $item->id),
        ));

        $this->assertSame(0, $this->task->attachments()->count(), 'a linha não ficou');
        $this->assertSame([], Storage::disk(TaskDetailPresenter::DISK)->allFiles(), 'o arquivo não ficou no disco');
    }
}
