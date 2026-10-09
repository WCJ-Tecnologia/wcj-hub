<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Services\ProjectClientManagement;
use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Services\MemoizedClientManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * A resposta de Projetos ao contrato do Núcleo (escopo B2; ADR-001; F1 §09): "gestor do
 * cliente" é quem responde ou está alocado, com papel Gestor de projetos, em algum projeto do
 * cliente. Substitui o ClientManagementTest do B1 (a implementação vazia saiu com o B2).
 */
class ProjectClientManagementTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
    }

    public function test_o_container_entrega_a_implementacao_de_projetos_atras_da_memoria(): void
    {
        $management = app(ClientManagement::class);

        $this->assertInstanceOf(MemoizedClientManagement::class, $management);
        $this->assertInstanceOf(ProjectClientManagement::class, (new ReflectionProperty($management, 'source'))->getValue($management));
    }

    public function test_gere_quem_responde_ou_esta_alocado_com_papel_gestor(): void
    {
        $client = Client::factory()->create();
        $responsavel = User::factory()->projectManager()->create();
        $gestorAlocado = User::factory()->projectManager()->create();
        $devAlocado = User::factory()->developer()->create();
        $gestorDeFora = User::factory()->projectManager()->create();
        $project = $this->projeto(['client_id' => $client->id, 'manager_id' => $responsavel->id]);
        $this->alocar($project, $gestorAlocado, $devAlocado);

        $management = app(ProjectClientManagement::class);

        $this->assertTrue($management->manages($responsavel, $client));
        $this->assertTrue($management->manages($gestorAlocado, $client));
        $this->assertFalse($management->manages($devAlocado, $client), 'alocado sem o papel de gestor não gere');
        $this->assertFalse($management->manages($gestorDeFora, $client));
        // O Administrador não passa pelo contrato: as Policies respondem antes (isAdmin).
        $this->assertFalse($management->manages(User::factory()->admin()->create(), $client));
    }

    public function test_os_ids_dos_clientes_geridos_vem_sem_repeticao_e_em_ordem(): void
    {
        $gestor = User::factory()->projectManager()->create();
        [$a, $b] = Client::factory()->count(2)->create()->all();
        $this->projeto(['client_id' => $b->id, 'manager_id' => $gestor->id]);
        $this->projeto(['client_id' => $a->id, 'manager_id' => $gestor->id]);
        $this->alocar($this->projeto(['client_id' => $a->id]), $gestor);
        $this->projeto(); // de outro gestor

        $this->assertSame([$a->id, $b->id], app(ProjectClientManagement::class)->managedClientIds($gestor));
        $this->assertSame([], app(ProjectClientManagement::class)->managedClientIds(User::factory()->developer()->create()));
    }

    public function test_os_projetos_abertos_por_cliente_vem_em_lote_com_todos_os_ids_pedidos(): void
    {
        [$a, $b, $c] = Client::factory()->count(3)->create()->all();
        $this->projeto(['client_id' => $a->id]);
        $this->projeto(['client_id' => $a->id]);
        $this->projeto(['client_id' => $a->id])->forceFill(['finished_at' => now()])->save();
        $this->projeto(['client_id' => $b->id])->forceFill(['finished_at' => now()])->save();

        $management = app(ProjectClientManagement::class);

        $this->assertSame([$a->id => 2, $b->id => 0, $c->id => 0], $management->openProjectsCountByClient([$a->id, $b->id, $c->id]));
        $this->assertSame([], $management->openProjectsCountByClient([]));
    }

    /** F1 §09 pela rota: com a ligação real, o gestor edita o cliente do projeto dele e só ele. */
    public function test_o_gestor_edita_o_cliente_do_projeto_dele_e_nao_o_de_fora(): void
    {
        $gestor = User::factory()->projectManager()->create();
        $meu = Client::factory()->create(['country' => 'US', 'document' => 'US-001']);
        $deFora = Client::factory()->create(['country' => 'US', 'document' => 'US-002']);
        $this->projeto(['client_id' => $meu->id, 'manager_id' => $gestor->id]);

        $this->actingAs($gestor)
            ->put("/admin/clients/{$meu->id}", ['name' => 'Cliente do meu projeto', 'country' => 'US', 'document' => 'US-001'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertSame('Cliente do meu projeto', $meu->fresh()->name);

        $this->actingAs($gestor)
            ->put("/admin/clients/{$deFora->id}", ['name' => 'Tentativa', 'country' => 'US', 'document' => 'US-002'])
            ->assertForbidden();
        $this->assertNotSame('Tentativa', $deFora->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $gestor->id, 'action' => 'updated', 'outcome' => 'denied', 'auditable_type' => 'client', 'auditable_id' => $deFora->id]);
    }
}
