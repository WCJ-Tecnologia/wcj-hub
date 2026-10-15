<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\TimeEntry;
use App\Domains\Projetos\Services\SprintService;
use App\Domains\Projetos\Services\SprintTaskMover;
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
}
