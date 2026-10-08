<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B1 T21: a rota da auditoria sobre o AuditLogQuery (Auditoria AC 3, 5–8; NUC-12). O admin vê,
 * em páginas de 50; o gestor e o dev recebem 403 e a tentativa fica gravada; o *Histórico* abre
 * filtrado no registro; não existe rota de editar nem de apagar.
 */
class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.display_timezone' => 'America/Sao_Paulo']);
        $this->travelTo(Carbon::parse('2026-10-06 12:00:00', 'America/Sao_Paulo'));
        $this->admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
    }

    private function log(int $auditableId, ?string $at = null): AuditLog
    {
        if ($at !== null) {
            $this->travelTo(Carbon::parse($at, 'America/Sao_Paulo'));
        }

        $log = AuditLog::query()->create([
            'user_id' => $this->admin->id,
            'action' => 'updated',
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'auditable_type' => 'user',
            'auditable_id' => $auditableId,
            'changes' => ['name' => ['Maria', 'Maria Souza']],
        ]);
        $this->travelTo(Carbon::parse('2026-10-06 12:00:00', 'America/Sao_Paulo'));

        return $log;
    }

    // AC 3 e 5: a lista com páginas de 50, o período padrão e as opções dos filtros.
    public function test_admin_ve_a_auditoria_com_os_filtros(): void
    {
        $maria = User::factory()->developer()->create(['name' => 'Maria Souza']);
        $log = $this->log($maria->id);

        $this->actingAs($this->admin)->get('/admin/audit-logs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/audit-logs/index')
                ->where('logs.per_page', 50)
                ->has('logs.data', 1)
                ->where('logs.data.0.id', $log->id)
                ->where('logs.data.0.created_at', '06/10/2026 12:00:00')
                ->where('logs.data.0.user', 'Ana Admin')
                ->where('logs.data.0.channel', 'Tela')
                ->where('logs.data.0.action_label', 'Atualizado')
                ->where('logs.data.0.record', 'Usuário Maria Souza')
                ->where('logs.data.0.outcome', 'success')
                ->where('logs.data.0.changes', [['field' => 'name', 'label' => 'Nome', 'before' => 'Maria', 'after' => 'Maria Souza']])
                ->where('filters.from', '2026-09-30')
                ->where('filters.id', null)
                ->where('record', null)
                ->where('options.channels.0', ['value' => 'screen', 'label' => 'Tela'])
                ->where('options.outcomes', [['value' => 'success', 'label' => 'Ok'], ['value' => 'denied', 'label' => 'Negado']])
                ->has('options.users', 2)
                ->has('options.actions')
                ->has('options.types'));
    }

    // AC 6: o Histórico da ficha abre filtrado no registro, sem limite de período.
    public function test_historico_abre_filtrado_no_registro_sem_limite_de_periodo(): void
    {
        $maria = User::factory()->developer()->create(['name' => 'Maria Souza']);
        $outro = User::factory()->developer()->create();
        $antigo = $this->log($maria->id, '2025-02-01 09:00:00');
        $recente = $this->log($maria->id);
        $this->log($outro->id);

        $this->actingAs($this->admin)->get("/admin/audit-logs?type=user&id={$maria->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 2)
                ->where('logs.data.0.id', $recente->id)
                ->where('logs.data.1.id', $antigo->id)
                ->where('filters.type', 'user')
                ->where('filters.id', $maria->id)
                ->where('filters.from', null)
                ->where('record', 'Usuário Maria Souza'));
    }

    // T43 (A8 da review de 07/10): "De" em formato inválido não derruba o período padrão; o
    // "De" vazio (que chega nulo) lista tudo.
    public function test_de_invalido_mantem_os_7_dias_e_de_vazio_lista_tudo(): void
    {
        $antigo = $this->log($this->admin->id, '2025-02-01 09:00:00');
        $recente = $this->log($this->admin->id);

        $this->actingAs($this->admin)->get('/admin/audit-logs?from=07/10/2026')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.from', '2026-09-30')
                ->has('logs.data', 1)
                ->where('logs.data.0.id', $recente->id));

        $this->actingAs($this->admin)->get('/admin/audit-logs?from=')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.from', null)
                ->has('logs.data', 2)
                ->where('logs.data.1.id', $antigo->id));
    }

    /** @return array<string, array{RoleEnum}> */
    public static function semAcesso(): array
    {
        return [
            'gestor de projetos' => [RoleEnum::PROJECT_MANAGER],
            'desenvolvedor' => [RoleEnum::DEVELOPER],
        ];
    }

    // AC 7 e AC 2: gestor e dev recebem 403, e a tentativa fica na auditoria.
    #[DataProvider('semAcesso')]
    public function test_quem_nao_e_admin_recebe_403_e_a_negacao_fica_gravada(RoleEnum $papel): void
    {
        $actor = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);
        $this->log($this->admin->id);

        $this->actingAs($actor)->get('/admin/audit-logs')->assertForbidden();

        $denied = AuditLog::query()->where('outcome', AuditLog::OUTCOME_DENIED)->sole();
        $this->assertSame($actor->id, $denied->user_id);
        $this->assertSame('viewed', $denied->action);
        $this->assertSame('audit_log', $denied->auditable_type);
        $this->assertNull($denied->auditable_id);
        $this->assertSame(2, AuditLog::query()->count());
    }

    // AC 8: nenhuma rota edita nem apaga a auditoria.
    public function test_nao_existe_rota_de_editar_nem_de_apagar(): void
    {
        $log = $this->log($this->admin->id);
        $before = AuditLog::query()->get()->toArray();

        foreach (['put', 'patch', 'delete'] as $verb) {
            $this->actingAs($this->admin)->{$verb}("/admin/audit-logs/{$log->id}", ['action' => 'deleted'])->assertNotFound();
            $this->actingAs($this->admin)->{$verb}('/admin/audit-logs', ['action' => 'deleted'])->assertStatus(405);
        }
        $this->actingAs($this->admin)->post('/admin/audit-logs', ['action' => 'deleted'])->assertStatus(405);

        $this->assertSame($before, AuditLog::query()->get()->toArray());
    }
}
