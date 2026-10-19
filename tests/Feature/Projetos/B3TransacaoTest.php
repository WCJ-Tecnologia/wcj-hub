<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Services\BoardStageService;
use App\Domains\Projetos\Services\ProjectService;
use App\Domains\Projetos\Services\TaskTypeService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CriaProjeto;
use Tests\Support\UsaBancoDeVerdade;
use Tests\TestCase;

/**
 * MIG-02 AC 6 (spec b3-quadro-e-card; Verifier rodada 1, Fix 5): os 5 tipos padrão nascem na
 * mesma transação do projeto. Se gravar um tipo falha, não fica projeto, quadro, tipo nem
 * auditoria. Só o banco de verdade prova, sem o RefreshDatabase (que envolve o teste numa
 * transação e esconderia a falta dela); a falha vem de dentro do createDefaults, porque uma
 * falha na auditoria também pararia o mutante "createDefaults depois do commit".
 */
class B3TransacaoTest extends TestCase
{
    use CriaProjeto, DatabaseTruncation, UsaBancoDeVerdade;

    protected function setUp(): void
    {
        $this->exigirBancoDeVerdade();
        parent::setUp();
        $this->semearProjetos();
    }

    protected function tearDown(): void
    {
        $this->limparOQueFicou();
        parent::tearDown();
    }

    public function test_project_create_rolls_back_the_project_the_board_and_the_types_when_a_type_fails(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $gestor = User::factory()->projectManager()->create();
        $client = Client::factory()->create();

        $failingTypes = new class(app(AuditService::class)) extends TaskTypeService
        {
            public function createDefaults(Project $project): void
            {
                $project->taskTypes()->create(['slug' => 'improvement', 'label' => 'Melhoria']);
                throw new RuntimeException('o tipo falhou');
            }
        };
        $service = new ProjectService(app(AuditService::class), app(BoardStageService::class), $failingTypes);

        $message = null;
        try {
            $service->create([
                'client_id' => $client->id, 'manager_id' => $gestor->id, 'key_prefix' => 'TRX',
                'name' => 'Transação', 'starts_on' => '2026-10-06', 'due_on' => null,
            ]);
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
        }

        $this->assertSame('o tipo falhou', $message);
        $this->assertSame(0, Project::query()->count(), 'o projeto não ficou');
        $this->assertSame(0, DB::table('board_stages')->count(), 'o quadro não ficou');
        $this->assertSame(0, DB::table('task_types')->count(), 'o tipo gravado antes da falha não ficou');
        $this->assertSame(0, AuditLog::query()->where('auditable_type', 'project')->count(), 'sem linha created do projeto');
    }
}
