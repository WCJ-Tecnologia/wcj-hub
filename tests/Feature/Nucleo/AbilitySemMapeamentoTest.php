<?php

namespace Tests\Feature\Nucleo;

use App\Http\Controllers\Controller;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * B1 T41 (A6 da review de 07/10): uma Policy nova (B2 em diante) com ability que ninguém pôs no
 * AuditActionEnum::forAbility() responde 403, não 500, e a negação fica gravada com o nome dela
 * (G3; Auditoria AC 2). A tela da auditoria mostra a linha com um rótulo genérico.
 */
class AbilitySemMapeamentoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Uma ability que o enum não conhece, negada a todos, numa rota como as do Hub.
        Gate::define('arquivarTeste', fn () => false);
        Route::middleware(['web', 'auth'])->post('/teste/clients/{client}/archive', [ArquivaCliente::class, 'archive']);
    }

    public function test_negacao_de_ability_sem_mapeamento_responde_403_e_grava_o_nome_dela(): void
    {
        $dev = User::factory()->developer()->create();
        $client = Client::factory()->create();

        $this->actingAs($dev)->post("/teste/clients/{$client->id}/archive")->assertForbidden();

        $log = AuditLog::query()->sole();
        $this->assertSame($dev->id, $log->user_id);
        $this->assertSame('arquivarTeste', $log->action);
        $this->assertSame(AuditLog::OUTCOME_DENIED, $log->outcome);
        $this->assertSame('client', $log->auditable_type);
        $this->assertSame($client->id, $log->auditable_id);
    }

    public function test_tela_da_auditoria_mostra_a_linha_com_rotulo_generico(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['name' => 'Davi Dev']);
        $client = Client::factory()->create(['name' => 'Padaria Pão Quente']);
        $this->actingAs($dev)->post("/teste/clients/{$client->id}/archive")->assertForbidden();

        $this->actingAs($admin)->get('/admin/audit-logs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'arquivarTeste')
                ->where('logs.data.0.action_label', 'Outra ação')
                ->where('logs.data.0.user', 'Davi Dev')
                ->where('logs.data.0.record', 'Cliente Padaria Pão Quente')
                ->where('logs.data.0.outcome', AuditLog::OUTCOME_DENIED));
    }
}

/** Um controller do Hub com uma ação que autoriza uma ability fora do enum. */
class ArquivaCliente extends Controller
{
    public function archive(Client $client): void
    {
        $this->authorize('arquivarTeste', $client);
    }
}
