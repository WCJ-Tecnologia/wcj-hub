<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Services\ClientService;
use App\Platform\Services\ContactService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\ClientManagementFalso;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * B1 T16, T33: ContactService (Contatos AC 1–4, 7–9, 13; NUC-08, NUC-09). As recusas por
 * papel (AC 5, 6) são da ContactPolicy (ContactPolicyTest) e das rotas (ContactControllerTest);
 * a anonimização (AC 10–12), do ContactAnonymizeTest. "Gere" vem do dublê do ClientManagement.
 */
class ContactServiceTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private ContactService $service;

    private User $admin;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ContactService::class);
        $this->admin = User::factory()->admin()->create();
        $this->client = Client::factory()->create(['name' => 'Padaria Pão Quente']);
    }

    private function assertAudited(Contact $contact, string $action, ?array $changes, ?User $by = null): void
    {
        $log = AuditLog::query()->where('auditable_type', 'contact')->where('auditable_id', $contact->id)
            ->where('action', $action)->latest('id')->firstOrFail();

        $this->assertSame(($by ?? $this->admin)->id, $log->user_id);
        $this->assertSame(AuditLog::OUTCOME_SUCCESS, $log->outcome);
        $this->assertNull($log->ai_authorization_id);
        // Auditoria AC 1: o IP da requisição de teste.
        $this->assertSame('127.0.0.1', $log->ip_address);
        // O JSON do MySQL reordena as chaves: compara sem ordem, com o tipo de cada valor.
        $actual = $log->changes;
        if ($changes !== null && $actual !== null) {
            ksort($changes);
            ksort($actual);
        }
        $this->assertSame($changes, $actual);
    }

    // AC 1
    public function test_cria_o_contato_no_cliente_e_audita(): void
    {
        ['contact' => $contact, 'warning' => $warning] = $this->service->create(
            $this->client,
            ['name' => 'Ana Lima', 'email' => 'ana@padaria.test', 'phone' => '11988880000'],
            $this->admin,
        );

        $this->assertNull($warning);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id, 'client_id' => $this->client->id, 'name' => 'Ana Lima',
            'email' => 'ana@padaria.test', 'phone' => '11988880000', 'deactivated_at' => null,
        ]);
        $this->assertAudited($contact, 'created', [
            'client_id' => [null, $this->client->id],
            'name' => [null, 'Ana Lima'],
            'email' => [null, 'ana@padaria.test'],
            'phone' => [null, '11988880000'],
        ]);
    }

    // AC 1: e-mail e telefone são opcionais; vazio vira nulo.
    public function test_email_e_telefone_sao_opcionais(): void
    {
        ['contact' => $contact] = $this->service->create($this->client, ['name' => 'Só Nome', 'email' => '', 'phone' => null], $this->admin);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'name' => 'Só Nome', 'email' => null, 'phone' => null]);
        $this->assertAudited($contact, 'created', ['client_id' => [null, $this->client->id], 'name' => [null, 'Só Nome']]);
    }

    // AC 2 e limites do F1 §02 (nome 120, e-mail 160, telefone 20).
    public function test_recusa_nome_vazio_e_campos_fora_do_limite_sem_criar(): void
    {
        $invalid = [
            'name' => ['name' => ''],
            'email' => ['name' => 'Ana', 'email' => 'não é e-mail'],
            'phone' => ['name' => 'Ana', 'phone' => str_repeat('1', 21)],
        ];
        $invalid['name_max'] = ['name' => str_repeat('a', 121)];
        $invalid['email_max'] = ['name' => 'Ana', 'email' => str_repeat('a', 150).'@exemplo.test'];

        foreach ($invalid as $case => $data) {
            try {
                $this->service->create($this->client, $data, $this->admin);
                $this->fail("{$case}: passou sem recusa.");
            } catch (ValidationException $e) {
                $this->assertSame([explode('_', $case)[0]], array_keys($e->errors()), $case);
            }
        }

        $this->assertSame(0, Contact::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }

    // F1 §02 (D3 de 07/10): telefone é texto; quem chama o Service direto também grava sem as
    // pontas em branco, e o "+" e os espaços do meio ficam.
    public function test_telefone_e_gravado_como_texto_sem_as_pontas_em_branco(): void
    {
        ['contact' => $contact] = $this->service->create($this->client, ['name' => 'Joost de Vries', 'phone' => '  +31 6 1234 5678 '], $this->admin);

        $this->assertSame('+31 6 1234 5678', $contact->fresh()->phone);
        $this->assertAudited($contact, 'created', [
            'client_id' => [null, $this->client->id],
            'name' => [null, 'Joost de Vries'],
            'phone' => [null, '+31 6 1234 5678'],
        ]);
    }

    // AC 2: cliente desativado não ganha contato (N11).
    public function test_recusa_contato_em_cliente_desativado(): void
    {
        $inactive = Client::factory()->inactive()->create();

        try {
            $this->service->create($inactive, ['name' => 'Ana Lima'], $this->admin);
            $this->fail('Passou sem recusa.');
        } catch (ValidationException $e) {
            $this->assertSame(['client' => ['Cliente desativado: reative o cliente para cadastrar contato.']], $e->errors());
        }

        $this->assertSame(0, Contact::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }

    // AC 3: avisa, sem recusar, quando outro contato ativo tem o mesmo e-mail.
    public function test_email_repetido_em_contato_ativo_salva_e_avisa(): void
    {
        $other = Client::factory()->create(['name' => 'Mercado Central']);
        Contact::factory()->create(['client_id' => $other->id, 'name' => 'Bia Reis', 'email' => 'compras@exemplo.test']);

        ['contact' => $contact, 'warning' => $warning] = $this->service->create(
            $this->client, ['name' => 'Caio Dias', 'email' => 'compras@exemplo.test'], $this->admin,
        );

        $this->assertSame('Já existe Bia Reis (Mercado Central) com este e-mail', $warning);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'email' => 'compras@exemplo.test']);
        $this->assertSame(2, Contact::query()->where('email', 'compras@exemplo.test')->count());
    }

    // AC 3: só contato ativo conta; desativado não gera aviso.
    public function test_email_de_contato_desativado_nao_avisa(): void
    {
        Contact::factory()->inactive()->create(['email' => 'compras@exemplo.test']);

        ['warning' => $warning] = $this->service->create($this->client, ['name' => 'Caio Dias', 'email' => 'compras@exemplo.test'], $this->admin);

        $this->assertNull($warning);
    }

    // AC 1, 3: editar audita antes e depois e avisa do repetido, sem contar o próprio contato.
    public function test_editar_audita_e_avisa_do_email_repetido(): void
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana', 'email' => 'ana@padaria.test', 'phone' => null]);

        $warning = $this->service->update($contact, ['name' => 'Ana Lima', 'email' => 'ana@padaria.test', 'phone' => '11988880000'], $this->admin);

        $this->assertNull($warning);
        $this->assertSame(['Ana Lima', '11988880000'], [$contact->fresh()->name, $contact->fresh()->phone]);
        $this->assertAudited($contact, 'updated', ['name' => ['Ana', 'Ana Lima'], 'phone' => [null, '11988880000']]);

        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Bia Reis', 'email' => 'bia@padaria.test']);
        $warning = $this->service->update($contact, ['name' => 'Ana Lima', 'email' => 'bia@padaria.test'], $this->admin);

        $this->assertSame('Já existe Bia Reis (Padaria Pão Quente) com este e-mail', $warning);
        $this->assertSame('bia@padaria.test', $contact->fresh()->email);
    }

    // AC 3 (D2 de 07/10): para quem não é Administrador o aviso não diz quem nem de qual cliente,
    // ao criar e ao editar; o contato salva do mesmo jeito.
    public function test_gestor_recebe_o_aviso_sem_nome_nem_cliente_do_outro_contato(): void
    {
        $manager = User::factory()->projectManager()->create();
        ClientManagementFalso::ligar()->gere($manager, $this->client);
        $other = Client::factory()->create(['name' => 'Mercado Central']);
        Contact::factory()->create(['client_id' => $other->id, 'name' => 'Bia Reis', 'email' => 'compras@exemplo.test']);
        $service = app(ContactService::class);

        ['contact' => $contact, 'warning' => $warning] = $service->create(
            $this->client, ['name' => 'Caio Dias', 'email' => 'compras@exemplo.test'], $manager,
        );

        $this->assertSame('Já existe um contato com este e-mail', $warning);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'email' => 'compras@exemplo.test']);

        $mine = Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Dora', 'email' => null]);
        $warning = $service->update($mine, ['name' => 'Dora', 'email' => 'compras@exemplo.test'], $manager);

        $this->assertSame('Já existe um contato com este e-mail', $warning);
        $this->assertSame('compras@exemplo.test', $mine->fresh()->email);
    }

    // AC 4: o gestor cria e edita com as mesmas regras; a auditoria leva o gestor.
    public function test_gestor_cria_e_edita_com_as_mesmas_regras(): void
    {
        $manager = User::factory()->projectManager()->create();
        ClientManagementFalso::ligar()->gere($manager, $this->client);

        ['contact' => $contact] = $this->service->create($this->client, ['name' => 'Ana Lima'], $manager);
        $this->service->update($contact, ['name' => 'Ana Souza'], $manager);

        $this->assertAudited($contact, 'created', ['client_id' => [null, $this->client->id], 'name' => [null, 'Ana Lima']], $manager);
        $this->assertAudited($contact, 'updated', ['name' => ['Ana Lima', 'Ana Souza']], $manager);

        $this->expectException(ValidationException::class);
        $this->service->create($this->client, ['name' => ''], $manager);
    }

    // AC 13: o gestor vê os contatos e o filtro só dos clientes que o contrato lista; o Administrador, todos.
    public function test_lista_e_filtro_do_gestor_seguem_os_clientes_que_o_contrato_lista(): void
    {
        $manager = User::factory()->projectManager()->create();
        $other = Client::factory()->create(['name' => 'Outro Cliente']);
        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima']);
        Contact::factory()->create(['client_id' => $other->id, 'name' => 'Bia Reis']);
        ClientManagementFalso::ligar()->gere($manager, $this->client);
        $service = app(ContactService::class);
        $filters = ['search' => '', 'client_id' => null, 'show_inactive' => false];

        $this->assertSame(['Ana Lima'], $service->paginate($filters, $manager)->pluck('name')->all());
        $this->assertSame([['id' => $this->client->id, 'name' => 'Padaria Pão Quente']], $service->clientOptions($manager));
        $this->assertSame(['Ana Lima', 'Bia Reis'], $service->paginate($filters, $this->admin)->pluck('name')->all());
        $this->assertCount(2, $service->clientOptions($this->admin));
    }

    // AC 13 com a implementação vazia do B1: o gestor não vê contato nem cliente no filtro.
    public function test_com_a_implementacao_vazia_lista_e_filtro_do_gestor_vem_vazios(): void
    {
        $manager = User::factory()->projectManager()->create();
        Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima']);
        $filters = ['search' => '', 'client_id' => null, 'show_inactive' => true];

        $this->assertSame(0, $this->service->paginate($filters, $manager)->total());
        $this->assertSame([], $this->service->clientOptions($manager));
        $this->assertSame(1, $this->service->paginate($filters, $this->admin)->total());
    }

    // AC 7: desativa com quem e quando, sai das listas de escolha, o card continua citando.
    // CR-1-15 do ciclo 1: a segunda desativação, por uma instância carregada antes da primeira
    // (ainda "ativa" em memória), decide pela linha do banco e não grava nada.
    public function test_desativar_pela_instancia_velha_nao_grava_outra_desativacao(): void
    {
        $other = User::factory()->admin()->create();
        $id = Contact::factory()->create(['client_id' => $this->client->id])->id;
        $a = Contact::query()->findOrFail($id);
        $b = Contact::query()->findOrFail($id);

        $this->service->deactivate($a, $other);
        $stamp = $a->fresh()->deactivated_at;
        $this->service->deactivate($b, $this->admin);

        $this->assertSame(1, AuditLog::query()->where('action', 'deactivated')->count());
        $this->assertEquals($stamp, $a->fresh()->deactivated_at);
        $this->assertSame($other->id, $a->fresh()->deactivated_by);
    }

    // CR-1-15 do ciclo 1, N11: o cliente desativado depois de carregado (ainda "ativo" em
    // memória) não ganha contato novo.
    public function test_recusa_contato_em_cliente_desativado_por_outra_instancia(): void
    {
        $client = Client::query()->findOrFail($this->client->id);
        app(ClientService::class)->deactivate(Client::query()->findOrFail($this->client->id), $this->admin);

        try {
            $this->service->create($client, ['name' => 'Ana Lima'], $this->admin);
            $this->fail('Criou contato em cliente desativado.');
        } catch (ValidationException $e) {
            $this->assertSame(['client' => ['Cliente desativado: reative o cliente para cadastrar contato.']], $e->errors());
        }

        $this->assertSame(0, Contact::query()->count());
    }

    public function test_desativar_grava_quem_e_quando_e_sai_das_listas_de_escolha(): void
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id]);
        $card = $this->supportCard($contact);

        $this->service->deactivate($contact, $this->admin);

        $fresh = $contact->fresh();
        $this->assertNotNull($fresh->deactivated_at);
        $this->assertSame($this->admin->id, $fresh->deactivated_by);
        $this->assertFalse(Contact::query()->active()->whereKey($contact->id)->exists());
        $this->assertSame($contact->id, DB::table('tasks')->where('id', $card)->value('requester_contact_id'));
        $this->assertAudited($contact, 'deactivated', ['active' => [true, false]]);
    }

    // N11: reativar limpa quem e quando e volta às listas.
    public function test_reativar_limpa_quem_e_quando(): void
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id]);
        $this->service->deactivate($contact, $this->admin);

        $this->service->reactivate($contact->fresh(), $this->admin);

        $fresh = $contact->fresh();
        $this->assertNull($fresh->deactivated_at);
        $this->assertNull($fresh->deactivated_by);
        $this->assertTrue(Contact::query()->active()->whereKey($contact->id)->exists());
        $this->assertAudited($contact, 'reactivated', ['active' => [false, true]]);
    }

    // AC 8
    public function test_exclui_contato_nunca_citado_e_audita(): void
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id, 'name' => 'Ana Lima', 'email' => null, 'phone' => '11988880000']);

        $this->service->delete($contact, $this->admin);

        $this->assertModelMissing($contact);
        $this->assertAudited($contact, 'deleted', [
            'client_id' => [$this->client->id, null],
            'name' => ['Ana Lima', null],
            'phone' => ['11988880000', null],
        ]);
    }

    // AC 9
    public function test_nao_exclui_contato_citado_em_card_de_suporte(): void
    {
        $contact = Contact::factory()->create(['client_id' => $this->client->id]);
        $this->supportCard($contact);

        try {
            $this->service->delete($contact, $this->admin);
            $this->fail('Excluiu contato já usado.');
        } catch (DomainException $e) {
            $this->assertSame('Contato já usado: desative em vez de excluir', $e->getMessage());
        }

        $this->assertModelExists($contact);
        $this->assertSame(0, AuditLog::query()->where('action', 'deleted')->count());
    }

    // AC 12 (D5) para quem chama o Service direto: anonimizado não se edita nem se reativa.
    public function test_anonimizado_nao_se_edita_nem_se_reativa_pelo_service(): void
    {
        $contact = Contact::factory()->inactive()->create(['client_id' => $this->client->id, 'name' => Contact::ANONYMIZED_NAME, 'email' => null, 'phone' => null]);
        AuditLog::query()->create(['user_id' => $this->admin->id, 'action' => AuditActionEnum::ANONYMIZED->value, 'outcome' => 'success', 'auditable_type' => 'contact', 'auditable_id' => $contact->id]);

        foreach ([fn () => $this->service->update($contact, ['name' => 'Ana Lima'], $this->admin), fn () => $this->service->reactivate($contact, $this->admin)] as $attempt) {
            try {
                $attempt();
                $this->fail('Alterou contato anonimizado.');
            } catch (DomainException $e) {
                $this->assertSame('Contato anonimizado: não pode ser alterado', $e->getMessage());
            }
        }

        $fresh = $contact->fresh();
        $this->assertSame([Contact::ANONYMIZED_NAME, false], [$fresh->name, $fresh->isActive()]);
        $this->assertSame(1, AuditLog::query()->count());
    }
}
