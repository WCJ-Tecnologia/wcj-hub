<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Services\AttachmentService;
use App\Domains\Projetos\Services\CriterionService;
use App\Domains\Projetos\Services\SprintService;
use App\Domains\Projetos\Services\TaskCommentService;
use App\Domains\Projetos\Services\TaskDetailPresenter;
use App\Domains\Projetos\Services\TaskPullRequestService;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * FCH-03 AC 5 (spec b3-ficha; Verifier rodada 1, Fix 2; L-017): as quatro exclusões de verdade da
 * ficha (critério, link de PR, comentário, anexo) e a linha `deleted` da auditoria são um commit só.
 * Se a auditoria falha, a linha continua, e o arquivo do anexo continua no disco (ele só sai depois
 * do commit). Só o banco de verdade prova, sem o RefreshDatabase.
 */
class B3FichaTransacaoTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    private Project $project;

    private Task $task;

    private User $dev;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->semearProjetos();
        Storage::fake(TaskDetailPresenter::DISK);
        $this->dev = User::factory()->developer()->create();
        $this->project = $this->projeto();
        $this->alocar($this->project, $this->dev);
        $this->task = $this->tarefa($this->project);
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

    public function test_criterion_delete_rolls_back_with_the_subitems_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $criterion = $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'CA', 'test_result_id' => TestResult::PENDING]);
        $criterion->children()->create(['task_id' => $this->task->id, 'number' => 1, 'description' => 'Sub', 'test_result_id' => TestResult::PENDING]);
        $service = new CriterionService($this->failingAudit(), app(SprintService::class));

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->delete($criterion, $this->dev)));

        $this->assertSame(2, $this->task->acceptanceCriteria()->count(), 'o critério e o subitem continuam');
    }

    public function test_pull_request_delete_rolls_back_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $pr = $this->task->pullRequests()->create(['url' => 'https://github.com/x/y/pull/1', 'user_id' => $this->dev->id]);
        $service = new TaskPullRequestService($this->failingAudit(), app(SprintService::class));

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->remove($pr, $this->dev)));

        $this->assertTrue($this->task->pullRequests()->whereKey($pr->id)->exists(), 'o link continua');
    }

    public function test_comment_delete_rolls_back_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $comment = $this->task->comments()->create(['user_id' => $this->dev->id, 'body' => 'Meu']);
        $service = new TaskCommentService($this->failingAudit());

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->delete($comment, $this->dev)));

        $this->assertSame('Meu', $comment->fresh()?->body, 'o comentário continua');
    }

    public function test_attachment_delete_rolls_back_and_keeps_the_file_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        Storage::disk(TaskDetailPresenter::DISK)->put("tasks/{$this->task->id}/a.pdf", 'pdf');
        $attachment = $this->task->attachments()->create([
            'path' => "tasks/{$this->task->id}/a.pdf", 'original_name' => 'a.pdf', 'mime_type' => 'application/pdf', 'user_id' => $this->dev->id,
        ]);
        $service = new AttachmentService($this->failingAudit());

        $this->assertSame('a auditoria falhou', $this->failure(fn () => $service->remove($attachment, $this->dev)));

        $this->assertTrue($this->task->attachments()->whereKey($attachment->id)->exists(), 'a linha continua');
        Storage::disk(TaskDetailPresenter::DISK)->assertExists("tasks/{$this->task->id}/a.pdf");
    }

    /** O arquivo sai só depois do commit: com a transação de fora ainda aberta, ele continua no disco. */
    public function test_attachment_file_is_deleted_only_after_the_commit(): void
    {
        Storage::disk(TaskDetailPresenter::DISK)->put("tasks/{$this->task->id}/b.pdf", 'pdf');
        $attachment = $this->task->attachments()->create([
            'path' => "tasks/{$this->task->id}/b.pdf", 'original_name' => 'b.pdf', 'mime_type' => 'application/pdf', 'user_id' => $this->dev->id,
        ]);
        $service = app(AttachmentService::class);

        DB::transaction(function () use ($service, $attachment) {
            $service->remove($attachment, $this->dev);
            $this->assertFalse($this->task->attachments()->whereKey($attachment->id)->exists(), 'a linha já saiu na transação');
            Storage::disk(TaskDetailPresenter::DISK)->assertExists("tasks/{$this->task->id}/b.pdf");
        });

        Storage::disk(TaskDetailPresenter::DISK)->assertMissing("tasks/{$this->task->id}/b.pdf");
        $this->assertSame(1, AuditLog::query()->where('auditable_type', 'task_attachment')->where('action', 'deleted')->count());
    }
}
