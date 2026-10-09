<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ClientManagementFalso;
use Tests\TestCase;

/**
 * B1 T42 (A7 da review de 07/10): com a implementação real do B2, cada pergunta ao contrato
 * ClientManagement é consulta em `projects`. A lista não pode perguntar por linha: "quem gere o
 * quê" uma vez por requisição e os projetos abertos da página numa chamada só. O dublê conta as
 * chamadas, atrás da mesma memória que a implementação real usa.
 */
class ClientManagementPorRequisicaoTest extends TestCase
{
    use RefreshDatabase;

    private ClientManagementFalso $management;

    private User $manager;

    /** @var list<Client> */
    private array $clients;

    protected function setUp(): void
    {
        parent::setUp();

        $this->management = ClientManagementFalso::ligar();
        $this->manager = User::factory()->projectManager()->create();
        $this->clients = Client::factory()->count(20)->create()->all();
        $this->management->gere($this->manager, ...$this->clients);
        $this->management->projetosAbertos($this->clients[0], 3);
    }

    private function zerarContagem(): void
    {
        $this->management->chamadas = array_fill_keys(array_keys($this->management->chamadas), 0);
    }

    public function test_lista_de_20_clientes_do_admin_conta_os_projetos_abertos_numa_chamada(): void
    {
        $admin = User::factory()->admin()->create();
        $first = $this->clients[0];

        $this->actingAs($admin)->get('/admin/clients')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('clients.data', 20)
                ->where('clients.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['open_projects'] === ($row['id'] === $first->id ? 3 : 0))));

        $this->assertSame(['manages' => 0, 'managedClientIds' => 0, 'openProjectsCountByClient' => 1], $this->management->chamadas);
    }

    public function test_lista_de_20_clientes_do_gestor_pergunta_quem_ele_gere_uma_vez(): void
    {
        $this->actingAs($this->manager)->get('/admin/clients')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('clients.data', 20)
                ->where('clients.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['can']['update'] === true && $row['open_projects'] === null)));

        $this->assertSame(['manages' => 0, 'managedClientIds' => 1, 'openProjectsCountByClient' => 0], $this->management->chamadas);
    }

    public function test_lista_de_20_contatos_do_gestor_pergunta_quem_ele_gere_uma_vez(): void
    {
        foreach ($this->clients as $client) {
            Contact::factory()->create(['client_id' => $client->id]);
        }
        $this->zerarContagem();

        $this->actingAs($this->manager)->get('/admin/contacts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 20)
                ->has('clients', 20)
                ->where('contacts.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['can']['update'] === true)));

        $this->assertSame(['manages' => 0, 'managedClientIds' => 1, 'openProjectsCountByClient' => 0], $this->management->chamadas);
    }

    // A memória vale uma requisição: na seguinte, o que mudou no contrato já aparece.
    public function test_a_requisicao_seguinte_pergunta_de_novo(): void
    {
        $other = User::factory()->projectManager()->create();
        $this->actingAs($other)->get('/admin/clients')
            ->assertInertia(fn ($page) => $page->where('clients.data.0.can.update', false));

        $this->management->gere($other, ...$this->clients);
        $this->actingAs($other)->get('/admin/clients')
            ->assertInertia(fn ($page) => $page->where('clients.data.0.can.update', true));

        $this->assertSame(2, $this->management->chamadas['managedClientIds']);
    }
}
