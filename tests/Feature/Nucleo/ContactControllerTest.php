<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ClientManagementFalso;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * B1 T18, T33: rotas de contatos sobre o ContactService (Contatos AC 1–13; NUC-08, NUC-09,
 * NUC-10). Feliz, 403 com "nada muda" (dev em tudo; gestor fora dos clientes que o
 * ClientManagement lista para ele e em desativar, reativar, excluir e anonimizar), erro de
 * validação; o aviso de e-mail repetido chega como flash. "Gere" vem do dublê do contrato.
 */
class ContactControllerTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private User $admin;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
        $this->client = Client::factory()->create(['name' => 'Padaria Pão Quente']);
    }

    /** @return list<array<string, mixed>> */
    private function snapshot(): array
    {
        return Contact::query()->orderBy('id')->get()->map->only(['id', 'client_id', 'name', 'email', 'phone', 'deactivated_at', 'deactivated_by'])->all();
    }

    private function anonymized(): Contact
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id]);
        app(ContactService::class)->anonymize($contact, $this->admin);

        return $contact->fresh();
    }

    /** @return list<string> */
    private function names(string $query = ''): array
    {
        return collect($this->get("/admin/contacts?{$query}")->viewData('page')['props']['contacts']['data'])->pluck('name')->all();
    }

    // AC 13: colunas, ordem por nome, desativados escondidos por padrão, anonimizado com a situação.
    public function test_lista_geral_traz_as_colunas_do_f1_e_esconde_desativados(): void
    {
        $mercado = Client::factory()->create(['name' => 'Mercado Central']);
        $bia = Contact::factory()->create(['client_id' => $mercado->id, 'name' => 'Bia Reis', 'email' => 'bia@mercado.test', 'phone' => '11988880000']);
        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima']);
        $off = Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Caio Desativado']);
        $this->actingAs($this->admin)->post("/admin/contacts/{$off->id}/deactivate");
        $anonymized = $this->anonymized();

        $this->actingAs($this->admin)->get('/admin/contacts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/contacts/index')
                ->has('contacts.data', 2)
                ->where('contacts.data.0.name', 'Ana Lima')
                ->where('contacts.data.1', [
                    'id' => $bia->id, 'name' => 'Bia Reis', 'email' => 'bia@mercado.test', 'phone' => '11988880000',
                    'client_id' => $mercado->id, 'client_name' => 'Mercado Central', 'active' => true, 'anonymized' => false,
                    'deactivated_at' => null, 'deactivated_by' => null,
                    'can' => ['update' => true, 'deactivate' => true, 'reactivate' => false, 'delete' => true, 'anonymize' => true],
                ])
                ->where('filters', ['search' => '', 'client_id' => null, 'show_inactive' => false])
                ->where('clients', [['id' => $mercado->id, 'name' => 'Mercado Central'], ['id' => $this->client->id, 'name' => 'Padaria Pão Quente']]));

        $this->actingAs($this->admin)->get('/admin/contacts?show_inactive=1')
            ->assertInertia(fn ($page) => $page
                ->has('contacts.data', 4)
                ->where('contacts.data.2.name', 'Caio Desativado')
                ->where('contacts.data.2.active', false)
                ->where('contacts.data.2.deactivated_by', 'Ana Admin')
                ->where('contacts.data.2.can', ['update' => true, 'deactivate' => false, 'reactivate' => true, 'delete' => true, 'anonymize' => true])
                // AC 12: anonimizado sem editar, reativar nem anonimizar de novo.
                ->where('contacts.data.3.id', $anonymized->id)
                ->where('contacts.data.3.name', 'removido a pedido do titular')
                ->where('contacts.data.3.anonymized', true)
                ->where('contacts.data.3.active', false)
                ->where('contacts.data.3.can', ['update' => false, 'deactivate' => false, 'reactivate' => false, 'delete' => true, 'anonymize' => false]));
    }

    // AC 13: busca por nome, e-mail e telefone (com máscara); filtro por cliente.
    public function test_busca_por_nome_email_e_telefone_e_filtra_por_cliente(): void
    {
        $mercado = Client::factory()->create(['name' => 'Mercado Central']);
        Contact::factory()->create(['client_id' => $mercado->id, 'name' => 'Bia Reis', 'email' => 'compras@mercado.test', 'phone' => '11988880000']);
        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima', 'email' => 'ana@padaria.test', 'phone' => '1133334444']);
        $this->actingAs($this->admin);

        $this->assertSame(['Bia Reis'], $this->names('search=bia'));
        $this->assertSame(['Bia Reis'], $this->names('search=compras@'));
        $this->assertSame(['Ana Lima'], $this->names('search='.urlencode('(11) 3333')));
        $this->assertSame(['Ana Lima'], $this->names("client_id={$this->client->id}"));
        $this->assertSame(['Ana Lima', 'Bia Reis'], $this->names('client_id=999999'));
    }

    // AC 13, F1 §09: o gestor só vê os contatos e o filtro dos clientes que o contrato lista.
    public function test_gestor_ve_so_os_contatos_dos_clientes_dele(): void
    {
        $manager = User::factory()->projectManager()->create();
        ClientManagementFalso::ligar()->gere($manager, $this->client);
        $other = Client::factory()->create(['name' => 'Outro Cliente']);
        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima']);
        Contact::factory()->create(['client_id' => $other->id, 'name' => 'Bia Reis']);
        $this->actingAs($manager);

        $this->get('/admin/contacts')->assertOk()->assertInertia(fn ($page) => $page
            ->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'Ana Lima')
            ->where('contacts.data.0.can', ['update' => true, 'deactivate' => false, 'reactivate' => false, 'delete' => false, 'anonymize' => false])
            ->where('clients', [['id' => $this->client->id, 'name' => 'Padaria Pão Quente']]));
        $this->assertSame(['Ana Lima'], $this->names("client_id={$other->id}"));
        $this->assertSame([], $this->names('search=bia'));

        // O contrato não lista cliente algum para ele: lista vazia, sem erro.
        $this->actingAs(User::factory()->projectManager()->create());
        $this->get('/admin/contacts')->assertOk()->assertInertia(fn ($page) => $page->has('contacts.data', 0)->where('clients', []));
    }

    // AC 1, 3: cria no cliente, telefone sem máscara; e-mail repetido salva e avisa por flash.
    public function test_criar_no_cliente_e_avisar_do_email_repetido(): void
    {
        $this->actingAs($this->admin)->post("/admin/clients/{$this->client->id}/contacts", ['name' => 'Ana Lima', 'email' => 'compras@exemplo.test', 'phone' => '(11) 98888-0000'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Contato Ana Lima criado.')
            ->assertSessionMissing('warning');
        $this->assertDatabaseHas('contacts', ['client_id' => $this->client->id, 'name' => 'Ana Lima', 'email' => 'compras@exemplo.test', 'phone' => '11988880000']);

        $mercado = Client::factory()->create(['name' => 'Mercado Central']);
        $this->actingAs($this->admin)->post("/admin/clients/{$mercado->id}/contacts", ['name' => 'Bia Reis', 'email' => 'compras@exemplo.test'])
            ->assertSessionHas('success', 'Contato Bia Reis criado.')
            ->assertSessionHas('warning', 'Já existe Ana Lima (Padaria Pão Quente) com este e-mail');
        $this->assertSame(2, Contact::query()->where('email', 'compras@exemplo.test')->count());
    }

    // AC 3: o aviso chega à tela como flash warning, junto do sucesso (toast de aviso).
    public function test_aviso_de_email_repetido_chega_a_tela_junto_do_sucesso(): void
    {
        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima', 'email' => 'compras@exemplo.test']);

        $this->actingAs($this->admin)->from('/admin/contacts')->followingRedirects()
            ->post("/admin/clients/{$this->client->id}/contacts", ['name' => 'Bia Reis', 'email' => 'compras@exemplo.test'])
            ->assertInertia(fn ($page) => $page->component('admin/contacts/index')
                ->where('flash.success', 'Contato Bia Reis criado.')
                ->where('flash.warning', 'Já existe Ana Lima (Padaria Pão Quente) com este e-mail'));
    }

    // AC 2 e limites do F1 §02
    public function test_validacao_do_formulario(): void
    {
        $this->actingAs($this->admin)->from("/admin/clients/{$this->client->id}")
            ->post("/admin/clients/{$this->client->id}/contacts", ['name' => '', 'email' => 'não é e-mail', 'phone' => str_repeat('9', 21)])
            ->assertSessionHasErrors(['name', 'email', 'phone']);

        $inactive = Client::factory()->inactive()->create();
        $this->actingAs($this->admin)->post("/admin/clients/{$inactive->id}/contacts", ['name' => 'Ana Lima'])
            ->assertSessionHasErrors(['client' => 'Cliente desativado: reative o cliente para cadastrar contato.']);

        $this->assertSame(0, Contact::query()->count());
    }

    // AC 1, 3, 7, 8 e N11: editar (com o aviso), desativar, reativar e excluir.
    public function test_editar_desativar_reativar_e_excluir(): void
    {
        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Bia Reis', 'email' => 'bia@padaria.test']);
        $contact = Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana']);

        $this->actingAs($this->admin)->put("/admin/contacts/{$contact->id}", ['name' => 'Ana Lima', 'email' => 'bia@padaria.test', 'phone' => '+31 6 1234 5678'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Contato atualizado.')
            ->assertSessionHas('warning', 'Já existe Bia Reis (Padaria Pão Quente) com este e-mail');
        $this->assertSame(['Ana Lima', 'bia@padaria.test', '31612345678'], [$contact->fresh()->name, $contact->fresh()->email, $contact->fresh()->phone]);

        $this->actingAs($this->admin)->post("/admin/contacts/{$contact->id}/deactivate")
            ->assertRedirect()->assertSessionHas('success', 'Contato Ana Lima desativado.');
        $this->assertSame($this->admin->id, $contact->fresh()->deactivated_by);

        $this->actingAs($this->admin)->post("/admin/contacts/{$contact->id}/reactivate")
            ->assertRedirect()->assertSessionHas('success', 'Contato Ana Lima reativado.');
        $this->assertTrue($contact->fresh()->isActive());

        $this->actingAs($this->admin)->delete("/admin/contacts/{$contact->id}")
            ->assertRedirect()->assertSessionHas('success', 'Contato excluído.');
        $this->assertModelMissing($contact);
    }

    // AC 9 (SPEC_DEVIATION: toast de erro em vez de 403; ver ContactController::destroy)
    public function test_excluir_contato_ja_usado_mostra_a_mensagem_e_mantem_a_linha(): void
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id]);
        $this->supportCard($contact);

        $this->actingAs($this->admin)->delete("/admin/contacts/{$contact->id}")
            ->assertRedirect()->assertSessionHas('error', 'Contato já usado: desative em vez de excluir');

        $this->assertModelExists($contact);
    }

    // AC 10
    public function test_anonimizar_com_a_palavra_exata(): void
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima', 'email' => 'ana@padaria.test', 'phone' => '11988880000']);

        $this->actingAs($this->admin)->post("/admin/contacts/{$contact->id}/anonymize", ['confirmation' => 'ANONIMIZAR'])
            ->assertRedirect()->assertSessionHas('success', 'Contato anonimizado.');

        $fresh = $contact->fresh();
        $this->assertSame(['removido a pedido do titular', null, null, false], [$fresh->name, $fresh->email, $fresh->phone, $fresh->isActive()]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => 'anonymized', 'outcome' => 'success', 'auditable_type' => 'contact', 'auditable_id' => $contact->id]);
    }

    // AC 11
    public function test_confirmacao_diferente_de_anonimizar_e_recusada_e_nada_muda(): void
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id]);
        $before = $this->snapshot();

        foreach (['anonimizar', 'ANONIMIZA', 'ANONIMIZAR!', ''] as $typed) {
            $this->actingAs($this->admin)->from('/admin/contacts')
                ->post("/admin/contacts/{$contact->id}/anonymize", ['confirmation' => $typed])
                ->assertRedirect('/admin/contacts')
                ->assertSessionHasErrors(['confirmation' => 'Digite ANONIMIZAR para confirmar.']);
        }

        $this->assertEquals($before, $this->snapshot());
        $this->assertSame(0, AuditLog::query()->count());
    }

    // AC 12: anonimizado não se edita, não se reativa nem se anonimiza de novo, nem pelo admin.
    public function test_anonimizado_recebe_403_ao_editar_reativar_e_anonimizar(): void
    {
        $contact = $this->anonymized();
        $before = $this->snapshot();
        $rows = AuditLog::query()->where('outcome', 'success')->count();

        $attempts = [
            ['updated', fn () => $this->put("/admin/contacts/{$contact->id}", ['name' => 'Ana Lima'])],
            ['reactivated', fn () => $this->post("/admin/contacts/{$contact->id}/reactivate")],
            ['anonymized', fn () => $this->post("/admin/contacts/{$contact->id}/anonymize", ['confirmation' => 'ANONIMIZAR'])],
        ];

        foreach ($attempts as [$action, $attempt]) {
            $this->actingAs($this->admin);
            $attempt()->assertForbidden()->assertSee('Contato anonimizado: não pode ser alterado');
            $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => $action, 'outcome' => 'denied', 'auditable_type' => 'contact', 'auditable_id' => $contact->id]);
        }

        $this->assertEquals($before, $this->snapshot());
        $this->assertSame($rows, AuditLog::query()->where('outcome', 'success')->count());
    }

    // AC 4: o gestor cria e edita nos clientes que o contrato lista para ele.
    public function test_gestor_cria_e_edita_no_cliente_do_projeto_dele(): void
    {
        $manager = User::factory()->projectManager()->create();
        ClientManagementFalso::ligar()->gere($manager, $this->client);
        $this->actingAs($manager);

        $this->post("/admin/clients/{$this->client->id}/contacts", ['name' => 'Ana Lima'])->assertSessionHas('success', 'Contato Ana Lima criado.');
        $contact = Contact::query()->where('name', 'Ana Lima')->sole();
        $this->put("/admin/contacts/{$contact->id}", ['name' => 'Ana Souza'])->assertSessionHas('success', 'Contato atualizado.');

        $this->assertSame('Ana Souza', $contact->fresh()->name);
        $this->assertSame(2, AuditLog::query()->where('user_id', $manager->id)->where('outcome', 'success')->where('auditable_type', 'contact')->count());
    }

    // AC 5
    public function test_gestor_recebe_403_fora_dos_clientes_dele_e_nas_acoes_do_administrador(): void
    {
        $manager = User::factory()->projectManager()->create();
        ClientManagementFalso::ligar()->gere($manager, $this->client);
        $mine = Contact::factory()->create(['client_id' => $this->client->id]);
        $other = Client::factory()->create();
        $foreign = Contact::factory()->create(['client_id' => $other->id]);
        $inactive = Contact::factory()->inactive()->create(['client_id' => $this->client->id]);
        $before = $this->snapshot();

        $attempts = [
            // Criar nega pela classe: o contato ainda não existe (auditable_id nulo).
            ['created', null, fn () => $this->post("/admin/clients/{$other->id}/contacts", ['name' => 'Invasor'])],
            ['updated', $foreign->id, fn () => $this->put("/admin/contacts/{$foreign->id}", ['name' => 'Invadido'])],
            ['deactivated', $mine->id, fn () => $this->post("/admin/contacts/{$mine->id}/deactivate")],
            ['reactivated', $inactive->id, fn () => $this->post("/admin/contacts/{$inactive->id}/reactivate")],
            ['deleted', $mine->id, fn () => $this->delete("/admin/contacts/{$mine->id}")],
            ['anonymized', $mine->id, fn () => $this->post("/admin/contacts/{$mine->id}/anonymize", ['confirmation' => 'ANONIMIZAR'])],
        ];

        foreach ($attempts as [$action, $id, $attempt]) {
            $this->actingAs($manager);
            $attempt()->assertForbidden();
            $this->assertDatabaseHas('audit_logs', ['user_id' => $manager->id, 'action' => $action, 'outcome' => 'denied', 'auditable_type' => 'contact', 'auditable_id' => $id]);
        }

        $this->assertEquals($before, $this->snapshot());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->count());
    }

    // AC 6
    public function test_dev_recebe_403_em_toda_rota_de_contatos_e_nada_muda(): void
    {
        $dev = User::factory()->developer()->create();
        // Mesmo que o contrato o listasse para o cliente: ainda assim não acessa os contatos.
        ClientManagementFalso::ligar()->gere($dev, $this->client);
        $contact = Contact::factory()->create(['client_id' => $this->client->id]);
        $inactive = Contact::factory()->inactive()->create(['client_id' => $this->client->id]);
        $before = $this->snapshot();

        $attempts = [
            ['viewed', fn () => $this->get('/admin/contacts')],
            ['created', fn () => $this->post("/admin/clients/{$this->client->id}/contacts", ['name' => 'Ana Lima'])],
            ['updated', fn () => $this->put("/admin/contacts/{$contact->id}", ['name' => 'Ana Lima'])],
            ['deactivated', fn () => $this->post("/admin/contacts/{$contact->id}/deactivate")],
            ['reactivated', fn () => $this->post("/admin/contacts/{$inactive->id}/reactivate")],
            ['deleted', fn () => $this->delete("/admin/contacts/{$contact->id}")],
            ['anonymized', fn () => $this->post("/admin/contacts/{$contact->id}/anonymize", ['confirmation' => 'ANONIMIZAR'])],
        ];

        foreach ($attempts as [$action, $attempt]) {
            $this->actingAs($dev);
            $attempt()->assertForbidden();
            $this->assertDatabaseHas('audit_logs', ['user_id' => $dev->id, 'action' => $action, 'outcome' => 'denied']);
        }

        $this->assertEquals($before, $this->snapshot());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->count());
    }

    // Ficha do cliente: os contatos e o botão Novo contato seguem a ContactPolicy (F1 §09).
    public function test_ficha_do_cliente_mostra_contatos_e_novo_contato_por_policy(): void
    {
        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima']);
        $manager = User::factory()->projectManager()->create();
        $management = ClientManagementFalso::ligar();

        $this->actingAs($this->admin)->get("/admin/clients/{$this->client->id}")
            ->assertInertia(fn ($page) => $page->has('contacts', 1)->where('can_create_contact', true));

        $this->actingAs($manager)->get("/admin/clients/{$this->client->id}")
            ->assertInertia(fn ($page) => $page->where('contacts', null)->where('can_create_contact', false));

        $management->gere($manager, $this->client);
        $this->actingAs($manager)->get("/admin/clients/{$this->client->id}")
            ->assertInertia(fn ($page) => $page
                ->where('contacts.0.can', ['update' => true, 'deactivate' => false, 'reactivate' => false, 'delete' => false, 'anonymize' => false])
                ->where('can_create_contact', true));

        // N11: cliente desativado não oferece Novo contato.
        $this->client->setActive(false, $this->admin)->save();
        $this->actingAs($this->admin)->get("/admin/clients/{$this->client->id}")
            ->assertInertia(fn ($page) => $page->has('contacts', 1)->where('can_create_contact', false));
    }

    // AC 5 e 13 com a implementação vazia do B1: lista e filtro vazios; criar e editar dão 403 e nada muda.
    public function test_com_a_implementacao_vazia_o_gestor_nao_ve_cria_nem_edita_contato(): void
    {
        $manager = User::factory()->projectManager()->create();
        $contact = Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima']);
        $before = $this->snapshot();
        $this->actingAs($manager);

        $this->get('/admin/contacts')->assertOk()->assertInertia(fn ($page) => $page->has('contacts.data', 0)->where('clients', []));
        $this->get("/admin/clients/{$this->client->id}")->assertInertia(fn ($page) => $page->where('contacts', null)->where('can_create_contact', false));
        $this->post("/admin/clients/{$this->client->id}/contacts", ['name' => 'Bia Reis'])->assertForbidden();
        $this->put("/admin/contacts/{$contact->id}", ['name' => 'Ana Souza'])->assertForbidden();

        $this->assertDatabaseHas('audit_logs', ['user_id' => $manager->id, 'action' => 'created', 'outcome' => 'denied', 'auditable_type' => 'contact', 'auditable_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $manager->id, 'action' => 'updated', 'outcome' => 'denied', 'auditable_type' => 'contact', 'auditable_id' => $contact->id]);
        $this->assertEquals($before, $this->snapshot());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->count());
    }
}
