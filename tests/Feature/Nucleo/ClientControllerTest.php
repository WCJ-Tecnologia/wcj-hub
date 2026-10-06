<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Database\Seeders\WcjClientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * B1 T12: rotas de clientes sobre o ClientService (Clientes AC 2, 3, 5–13; NUC-05, NUC-07).
 * Feliz, 403 com "nada muda" (dev em tudo; gestor em desativar, reativar, excluir e editar
 * cliente que não gere), erro de validação.
 */
class ClientControllerTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
    }

    /** @return array<string, mixed> */
    private function data(array $overrides = []): array
    {
        return ['name' => 'Padaria Pão Quente', 'country' => 'BR', 'document' => '12.ABC.345/01DE-35', ...$overrides];
    }

    /** @return list<array<string, mixed>> */
    private function snapshot(): array
    {
        return Client::query()->orderBy('id')->get()->map->only(['id', 'name', 'country', 'document', 'deactivated_at', 'deactivated_by'])->all();
    }

    // AC 13: colunas, ordem por nome, desativados escondidos por padrão.
    public function test_lista_traz_as_colunas_do_f1_em_ordem_e_esconde_desativados(): void
    {
        $zeta = Client::factory()->create(['name' => 'Zeta Ltda', 'document' => '38614817000122']);
        Client::factory()->create(['name' => 'Alfa Bouw', 'country' => 'NL', 'document' => null]);
        $off = Client::factory()->create(['name' => 'Beta Desativada']);
        $this->actingAs($this->admin)->post("/admin/clients/{$off->id}/deactivate");

        $this->actingAs($this->admin)->get('/admin/clients')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/clients/index')
                ->has('clients.data', 2)
                ->where('clients.data.0.name', 'Alfa Bouw')
                ->where('clients.data.0.country', 'NL')
                ->where('clients.data.0.country_name', 'Países Baixos')
                ->where('clients.data.0.document', null)
                ->where('clients.data.1.name', 'Zeta Ltda')
                ->where('clients.data.1.country_name', 'Brasil')
                ->where('clients.data.1.document', '38614817000122')
                ->where('clients.data.1.active', true)
                ->where('clients.data.1.id', $zeta->id)
                ->where('filters.show_inactive', false)
                ->where('can.create', true)
                ->has('countries', 249)
                ->where('countries.0', ['code' => 'AF', 'name' => 'Afeganistão']));

        $this->actingAs($this->admin)->get('/admin/clients?show_inactive=1')
            ->assertInertia(fn ($page) => $page
                ->has('clients.data', 3)
                ->where('clients.data.1.name', 'Beta Desativada')
                ->where('clients.data.1.active', false)
                ->where('clients.data.1.deactivated_by', 'Ana Admin')
                ->where('clients.data.1.can.reactivate', true)
                ->where('clients.data.1.can.deactivate', false)
                ->where('filters.show_inactive', true));
    }

    // AC 13: busca por nome e por documento (com ou sem máscara), filtro por país.
    public function test_busca_por_nome_e_documento_e_filtra_por_pais(): void
    {
        Client::factory()->create(['name' => 'Mercado Central', 'document' => '38614817000122']);
        Client::factory()->create(['name' => 'Padaria', 'document' => '12ABC34501DE35']);
        Client::factory()->create(['name' => 'Construtora PT', 'country' => 'PT', 'document' => 'PT-501234567']);
        $this->actingAs($this->admin);

        $names = fn (string $query) => collect($this->get("/admin/clients?{$query}")->viewData('page')['props']['clients']['data'])->pluck('name')->all();

        $this->assertSame(['Mercado Central'], $names('search=merc'));
        $this->assertSame(['Mercado Central'], $names('search='.urlencode('38.614.817')));
        $this->assertSame(['Padaria'], $names('search=abc345'));
        $this->assertSame(['Construtora PT'], $names('search=PT-501'));
        $this->assertSame(['Construtora PT'], $names('country=PT'));
        $this->assertSame(['Mercado Central', 'Padaria'], $names('country=BR'));
        // País fora da lista não filtra.
        $this->assertCount(3, $names('country=XX'));
    }

    // AC 9: a confirmação mostra quantos projetos abertos o cliente tem.
    public function test_lista_traz_os_projetos_abertos_para_a_confirmacao_de_desativar(): void
    {
        $client = Client::factory()->create();
        $manager = User::factory()->projectManager()->create();
        $this->project($client, $manager);
        $this->project($client, $manager);
        $this->project($client, $manager, finishedAt: now());

        $this->actingAs($this->admin)->get('/admin/clients')
            ->assertInertia(fn ($page) => $page
                ->where('clients.data.0.open_projects', 2)
                ->where('clients.data.0.can', ['update' => true, 'deactivate' => true, 'reactivate' => false, 'delete' => true]));
    }

    // AC 10: a WCJ não oferece desativar.
    public function test_wcj_na_lista_nao_oferece_desativar(): void
    {
        $this->seed(WcjClientSeeder::class);

        $this->actingAs($this->admin)->get('/admin/clients')
            ->assertInertia(fn ($page) => $page
                ->where('clients.data.0.name', 'WCJ Tecnologia')
                ->where('clients.data.0.can.deactivate', false)
                ->where('clients.data.0.open_projects', null));
    }

    // AC 1, 3 (rota): cria e grava sem máscara; repetido volta no campo com o nome do existente.
    public function test_criar_e_recusar_o_cnpj_repetido_no_campo(): void
    {
        $this->actingAs($this->admin)->post('/admin/clients', $this->data())
            ->assertRedirect()->assertSessionHas('success', 'Cliente Padaria Pão Quente criado.');
        $this->assertDatabaseHas('clients', ['name' => 'Padaria Pão Quente', 'country' => 'BR', 'document' => '12ABC34501DE35']);

        $this->actingAs($this->admin)->from('/admin/clients')
            ->post('/admin/clients', $this->data(['name' => 'Repetida', 'document' => '12abc34501de35']))
            ->assertRedirect('/admin/clients')
            ->assertSessionHasErrors(['document' => 'Já existe o cliente Padaria Pão Quente com este CNPJ']);
        $this->assertSame(1, Client::query()->count());
    }

    // AC 2 e limites do F1 §02
    public function test_validacao_do_formulario(): void
    {
        $this->actingAs($this->admin)->from('/admin/clients')
            ->post('/admin/clients', ['name' => '', 'country' => 'BR', 'document' => '12ABC34501DE36'])
            ->assertSessionHasErrors(['name', 'document' => 'Informe um CNPJ válido.']);

        $this->actingAs($this->admin)->from('/admin/clients')
            ->post('/admin/clients', ['name' => 'Sem CNPJ', 'country' => 'BR', 'document' => ''])
            ->assertSessionHasErrors(['document' => 'Informe o CNPJ.']);

        $this->actingAs($this->admin)->from('/admin/clients')
            ->post('/admin/clients', ['name' => str_repeat('a', 161), 'country' => 'ZZ', 'document' => null])
            ->assertSessionHasErrors(['name', 'country']);

        $this->assertSame(0, Client::query()->count());
    }

    public function test_editar_desativar_reativar_e_excluir(): void
    {
        $client = Client::factory()->create(['name' => 'Antigo']);

        $this->actingAs($this->admin)->put("/admin/clients/{$client->id}", $this->data(['name' => 'Novo']))
            ->assertRedirect()->assertSessionHas('success', 'Cliente atualizado.');
        $this->assertSame(['Novo', '12ABC34501DE35'], [$client->fresh()->name, $client->fresh()->document]);

        $this->actingAs($this->admin)->post("/admin/clients/{$client->id}/deactivate")
            ->assertRedirect()->assertSessionHas('success', 'Cliente Novo desativado.');
        $this->assertSame($this->admin->id, $client->fresh()->deactivated_by);

        $this->actingAs($this->admin)->post("/admin/clients/{$client->id}/reactivate")
            ->assertRedirect()->assertSessionHas('success', 'Cliente Novo reativado.');
        $this->assertTrue($client->fresh()->isActive());

        $this->actingAs($this->admin)->delete("/admin/clients/{$client->id}")
            ->assertRedirect()->assertSessionHas('success', 'Cliente excluído.');
        $this->assertModelMissing($client);
    }

    // AC 10
    public function test_desativar_a_wcj_responde_403_com_a_mensagem_e_grava_a_negacao(): void
    {
        $this->seed(WcjClientSeeder::class);
        $wcj = Client::query()->where('document', WcjClientSeeder::CNPJ)->sole();

        $this->actingAs($this->admin)->post("/admin/clients/{$wcj->id}/deactivate")
            ->assertForbidden()
            ->assertSee('A WCJ não pode ser desativada');

        $this->assertTrue($wcj->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => 'deactivated', 'outcome' => 'denied', 'auditable_type' => 'client', 'auditable_id' => $wcj->id]);
    }

    // AC 12 (SPEC_DEVIATION: toast de erro em vez de 403; ver ClientController::destroy)
    public function test_excluir_cliente_ja_usado_mostra_a_mensagem_e_mantem_a_linha(): void
    {
        $client = Client::factory()->create();
        Contact::query()->create(['client_id' => $client->id, 'name' => 'Ana Lima']);

        $this->actingAs($this->admin)->delete("/admin/clients/{$client->id}")
            ->assertRedirect()->assertSessionHas('error', 'Cliente já usado: desative em vez de excluir');

        $this->assertModelExists($client);
    }

    // AC 5, 6, 13: o gestor vê a lista e a ficha, cria e edita o cliente do projeto dele.
    public function test_gestor_ve_cria_e_edita_o_cliente_do_projeto_dele(): void
    {
        $manager = User::factory()->projectManager()->create();
        $mine = Client::factory()->create(['name' => 'Meu cliente']);
        $other = Client::factory()->create(['name' => 'Outro cliente']);
        $this->project($mine, $manager);
        $this->actingAs($manager);

        $this->get('/admin/clients')->assertOk()->assertInertia(fn ($page) => $page
            ->where('can.create', true)
            ->where('clients.data.0.name', 'Meu cliente')
            ->where('clients.data.0.can', ['update' => true, 'deactivate' => false, 'reactivate' => false, 'delete' => false])
            ->where('clients.data.0.open_projects', null)
            ->where('clients.data.1.name', 'Outro cliente')
            ->where('clients.data.1.can', ['update' => false, 'deactivate' => false, 'reactivate' => false, 'delete' => false]));
        $this->get("/admin/clients/{$other->id}")->assertOk();

        $this->post('/admin/clients', $this->data())->assertSessionHas('success', 'Cliente Padaria Pão Quente criado.');
        $this->assertDatabaseHas('audit_logs', ['user_id' => $manager->id, 'action' => 'created', 'outcome' => 'success', 'auditable_type' => 'client']);

        $this->put("/admin/clients/{$mine->id}", $this->data(['name' => 'Meu cliente editado', 'document' => '38614817000122']))
            ->assertSessionHas('success', 'Cliente atualizado.');
        $this->assertSame('Meu cliente editado', $mine->fresh()->name);
    }

    // AC 7
    public function test_gestor_recebe_403_ao_editar_cliente_que_nao_gere_e_em_desativar_reativar_e_excluir(): void
    {
        $manager = User::factory()->projectManager()->create();
        $mine = Client::factory()->create();
        $this->project($mine, $manager);
        $other = Client::factory()->create();
        $inactive = Client::factory()->inactive()->create();
        $before = $this->snapshot();

        $attempts = [
            ['updated', $other, fn () => $this->put("/admin/clients/{$other->id}", $this->data(['name' => 'Invadido']))],
            ['deactivated', $mine, fn () => $this->post("/admin/clients/{$mine->id}/deactivate")],
            ['reactivated', $inactive, fn () => $this->post("/admin/clients/{$inactive->id}/reactivate")],
            ['deleted', $other, fn () => $this->delete("/admin/clients/{$other->id}")],
        ];

        foreach ($attempts as [$action, $target, $attempt]) {
            $this->actingAs($manager);
            $attempt()->assertForbidden();
            $this->assertDatabaseHas('audit_logs', ['user_id' => $manager->id, 'action' => $action, 'outcome' => 'denied', 'auditable_type' => 'client', 'auditable_id' => $target->id]);
        }

        $this->assertEquals($before, $this->snapshot());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->count());
    }

    // AC 8
    public function test_dev_recebe_403_em_toda_rota_de_clientes_e_nada_muda(): void
    {
        $dev = User::factory()->developer()->create();
        $client = Client::factory()->create();
        // Alocado num projeto do cliente: ainda assim não acessa o cadastro.
        $this->project($client, User::factory()->projectManager()->create(), members: [$dev]);
        $inactive = Client::factory()->inactive()->create();
        $before = $this->snapshot();

        $attempts = [
            ['viewed', fn () => $this->get('/admin/clients')],
            ['viewed', fn () => $this->get("/admin/clients/{$client->id}")],
            ['created', fn () => $this->post('/admin/clients', $this->data())],
            ['updated', fn () => $this->put("/admin/clients/{$client->id}", $this->data())],
            ['deactivated', fn () => $this->post("/admin/clients/{$client->id}/deactivate")],
            ['reactivated', fn () => $this->post("/admin/clients/{$inactive->id}/reactivate")],
            ['deleted', fn () => $this->delete("/admin/clients/{$client->id}")],
        ];

        foreach ($attempts as [$action, $attempt]) {
            $this->actingAs($dev);
            $attempt()->assertForbidden();
            $this->assertDatabaseHas('audit_logs', ['user_id' => $dev->id, 'action' => $action, 'outcome' => 'denied', 'auditable_type' => 'client']);
        }

        $this->assertEquals($before, $this->snapshot());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->count());
    }

    // Ficha (F1 §01, §03): dados do cliente e os contatos, só para quem os vê (F1 §09).
    public function test_ficha_traz_os_dados_e_os_contatos_em_ordem_para_quem_ve_contatos(): void
    {
        $client = Client::factory()->create(['name' => 'Mercado', 'document' => '38614817000122']);
        Contact::query()->create(['client_id' => $client->id, 'name' => 'Bia Reis', 'email' => 'bia@mercado.test', 'phone' => '11988880000']);
        Contact::query()->create(['client_id' => $client->id, 'name' => 'Ana Lima', 'email' => null, 'phone' => null]);
        Contact::query()->create(['client_id' => Client::factory()->create()->id, 'name' => 'De outro cliente']);

        $this->actingAs($this->admin)->get("/admin/clients/{$client->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/clients/show')
                ->where('client.name', 'Mercado')
                ->where('client.country_name', 'Brasil')
                ->where('client.document', '38614817000122')
                ->where('client.active', true)
                ->has('contacts', 2)
                ->where('contacts.0', ['id' => $client->contacts()->where('name', 'Ana Lima')->value('id'), 'name' => 'Ana Lima', 'email' => null, 'phone' => null, 'active' => true, 'deactivated_at' => null, 'deactivated_by' => null])
                ->where('contacts.1.name', 'Bia Reis')
                ->where('contacts.1.email', 'bia@mercado.test')
                ->where('contacts.1.phone', '11988880000'));

        $manager = User::factory()->projectManager()->create();
        $this->actingAs($manager)->get("/admin/clients/{$client->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('client.name', 'Mercado')->where('contacts', null));

        $this->project($client, $manager);
        $this->actingAs($manager)->get("/admin/clients/{$client->id}")
            ->assertInertia(fn ($page) => $page->has('contacts', 2));
    }
}
