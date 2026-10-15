<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Services\ListService;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Database\Seeders\ReworkReasonSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * LST-02 AC 6 (Verifier, rodada 1, gap 2): a escrita do motivo e a auditoria são um commit só
 * (G3). Se a auditoria falha, nada fica gravado. Só o banco de verdade prova, sem o
 * RefreshDatabase (que envolve o teste numa transação e esconderia a falta dela).
 */
class ReworkReasonTransacaoTest extends TestCase
{
    use DatabaseTruncation, UsaBancoDeVerdade;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->seed(ReworkReasonSeeder::class);
        $this->actingAs(User::factory()->admin()->create());
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        parent::tearDown();
    }

    /** Um ListService cuja auditoria falha na gravação. */
    private function serviceWithFailingAudit(): ListService
    {
        $audit = new class extends AuditService
        {
            public function log(AuditActionEnum $action, Model $auditable, ?array $before = null, ?array $after = null, ?int $userId = null, ?int $aiAuthorizationId = null): AuditLog
            {
                throw new RuntimeException('a auditoria falhou');
            }
        };

        return new ListService($audit);
    }

    public function test_create_rolls_back_when_the_audit_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());

        try {
            $this->serviceWithFailingAudit()->createReworkReason(['label' => 'Sem auditoria', 'requires_text' => false]);
            $this->fail('A falha da auditoria não subiu.');
        } catch (RuntimeException $e) {
            $this->assertSame('a auditoria falhou', $e->getMessage());
        }

        $this->assertDatabaseMissing('rework_reasons', ['label' => 'Sem auditoria']);
    }

    public function test_update_rolls_back_when_the_audit_fails(): void
    {
        $reason = ReworkReason::query()->where('label', 'Ajuste visual')->sole();

        try {
            $this->serviceWithFailingAudit()->updateReworkReason($reason, ['label' => 'Renomeado', 'requires_text' => true]);
            $this->fail('A falha da auditoria não subiu.');
        } catch (RuntimeException $e) {
            $this->assertSame('a auditoria falhou', $e->getMessage());
        }

        $this->assertDatabaseHas('rework_reasons', ['id' => $reason->id, 'label' => 'Ajuste visual', 'requires_text' => false]);
    }
}
