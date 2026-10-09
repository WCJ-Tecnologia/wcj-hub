<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Services\ClientService;
use App\Rules\AlphanumericCnpj;
use Database\Seeders\WcjClientSeeder;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ClientManagementFalso;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * B1 T11: ClientService (Clientes AC 1–6, 9–12; edge cases da máscara e da troca de país;
 * NUC-05, NUC-06, NUC-07, NUC-13). As recusas por papel (AC 7, 8) são da ClientPolicy
 * (ClientPolicyTest) e das rotas (ClientControllerTest); a semente da WCJ (AC 14), do
 * SementesEExemploTest.
 */
class ClientServiceTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private ClientService $service;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ClientService::class);
        $this->admin = User::factory()->admin()->create();
    }

    /** @return array<string, mixed> */
    private function data(array $overrides = []): array
    {
        return ['name' => 'Padaria Pão Quente', 'country' => 'BR', 'document' => '12ABC34501DE35', ...$overrides];
    }

    private function assertAudited(Client $client, string $action, ?array $changes, ?User $by = null): void
    {
        $log = AuditLog::query()->where('auditable_type', 'client')->where('auditable_id', $client->id)
            ->where('action', $action)->latest('id')->firstOrFail();

        $this->assertSame(($by ?? $this->admin)->id, $log->user_id);
        $this->assertSame(AuditLog::OUTCOME_SUCCESS, $log->outcome);
        $this->assertNull($log->ai_authorization_id);
        // Auditoria AC 1: o IP da requisição de teste.
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertSame($changes, $log->changes);
    }

    /** @param  array<string, string>  $errors */
    private function assertRefused(callable $attempt, array $errors): void
    {
        try {
            $attempt();
            $this->fail('Passou sem recusa.');
        } catch (ValidationException $e) {
            $this->assertSame(array_map(fn (string $message) => [$message], $errors), $e->errors());
        }
    }

    // AC 1 e edge case da máscara: aceita com máscara e em minúsculas, grava limpo e maiúsculo.
    public function test_cria_com_cnpj_alfanumerico_gravado_sem_mascara_e_audita(): void
    {
        $client = $this->service->create($this->data(['document' => '12.abc.345/01de-35']), $this->admin);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id, 'name' => 'Padaria Pão Quente', 'country' => 'BR',
            'document' => '12ABC34501DE35', 'deactivated_at' => null, 'deactivated_by' => null,
        ]);
        $this->assertAudited($client, 'created', [
            'name' => [null, 'Padaria Pão Quente'],
            'country' => [null, 'BR'],
            'document' => [null, '12ABC34501DE35'],
        ]);
    }

    // AC 2
    public function test_brasil_sem_cnpj_ou_com_cnpj_invalido_e_recusado_no_campo_sem_criar(): void
    {
        $this->assertRefused(fn () => $this->service->create($this->data(['document' => '']), $this->admin), ['document' => 'Informe o CNPJ.']);
        $this->assertRefused(fn () => $this->service->create($this->data(['document' => null]), $this->admin), ['document' => 'Informe o CNPJ.']);
        $this->assertRefused(fn () => $this->service->create($this->data(['document' => '12ABC34501DE36']), $this->admin), ['document' => 'Informe um CNPJ válido.']);

        $this->assertSame(0, Client::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }

    // AC 3: mesmo país e documento, em outra caixa e com máscara, cita o cliente que já existe.
    public function test_cnpj_repetido_no_mesmo_pais_e_recusado_citando_o_cliente_existente(): void
    {
        $existing = $this->service->create($this->data(['name' => 'Mercado Central']), $this->admin);
        $other = $this->service->create($this->data(['name' => 'Outro', 'document' => '38614817000122']), $this->admin);

        $this->assertRefused(
            fn () => $this->service->create($this->data(['name' => 'Repetido', 'document' => '12.abc.345/01de-35']), $this->admin),
            ['document' => 'Já existe o cliente Mercado Central com este CNPJ'],
        );
        $this->assertRefused(
            fn () => $this->service->update($other, $this->data(['name' => 'Outro editado']), $this->admin),
            ['document' => 'Já existe o cliente Mercado Central com este CNPJ'],
        );

        $this->assertSame(2, Client::query()->count());
        $this->assertSame(['Outro', '38614817000122'], [$other->fresh()->name, $other->fresh()->document]);
        $this->assertSame(0, AuditLog::query()->where('action', 'updated')->count());
        // Salvar o próprio cliente sem mudar o documento não é repetido.
        $this->service->update($existing, $this->data(['name' => 'Mercado Central Ltda']), $this->admin);
        $this->assertSame('Mercado Central Ltda', $existing->fresh()->name);
    }

    // Unique por país (N5): o mesmo documento noutro país é outro cliente.
    public function test_mesmo_documento_em_outro_pais_nao_e_repetido_mas_no_mesmo_pais_estrangeiro_e(): void
    {
        $this->service->create($this->data(['name' => 'Brasileira']), $this->admin);
        $this->service->create($this->data(['name' => 'Holandesa', 'country' => 'NL', 'document' => '12ABC34501DE35']), $this->admin);

        $this->assertRefused(
            fn () => $this->service->create($this->data(['name' => 'Repetida', 'country' => 'NL', 'document' => '12abc34501de35']), $this->admin),
            ['document' => 'Já existe o cliente Holandesa com este identificador fiscal'],
        );
        $this->assertSame(2, Client::query()->count());
    }

    // AC 4
    public function test_fora_do_brasil_aceita_documento_vazio_e_identificador_que_nao_e_cnpj(): void
    {
        $semDocumento = $this->service->create($this->data(['name' => 'Bouwbedrijf', 'country' => 'NL', 'document' => '']), $this->admin);
        $comDocumento = $this->service->create($this->data(['name' => 'Construtora PT', 'country' => 'PT', 'document' => 'PT-501234567']), $this->admin);

        $this->assertNull($semDocumento->fresh()->document);
        $this->assertSame('PT-501234567', $comDocumento->fresh()->document);
        $this->assertSame(['NL', 'PT'], [$semDocumento->fresh()->country, $comDocumento->fresh()->country]);
    }

    public function test_campos_obrigatorios_tamanho_e_pais_da_lista(): void
    {
        try {
            $this->service->create(['name' => str_repeat('a', 161), 'country' => 'XX', 'document' => str_repeat('1', 31)], $this->admin);
            $this->fail('Passou sem recusa.');
        } catch (ValidationException $e) {
            $this->assertSame(['name', 'country', 'document'], array_keys($e->errors()));
        }

        try {
            $this->service->create(['name' => '', 'country' => ''], $this->admin);
            $this->fail('Passou sem recusa.');
        } catch (ValidationException $e) {
            $this->assertSame(['name', 'country'], array_keys($e->errors()));
        }

        $this->assertSame(0, Client::query()->count());
    }

    // Edge case: o país sai do Brasil num cliente com CNPJ.
    public function test_trocar_o_pais_mantem_o_documento_e_deixa_de_validar_como_cnpj(): void
    {
        $client = $this->service->create($this->data(), $this->admin);

        $this->service->update($client, $this->data(['country' => 'PT']), $this->admin);
        $this->assertSame(['PT', '12ABC34501DE35'], [$client->fresh()->country, $client->fresh()->document]);
        $this->assertAudited($client, 'updated', ['country' => ['BR', 'PT']]);

        $this->service->update($client, $this->data(['country' => 'PT', 'document' => 'NIF 501234567']), $this->admin);
        $this->assertSame('NIF 501234567', $client->fresh()->document);

        // De volta ao Brasil, o documento volta a ser CNPJ e é conferido.
        $this->assertRefused(
            fn () => $this->service->update($client, $this->data(['document' => 'NIF 501234567']), $this->admin),
            ['document' => 'Informe um CNPJ válido.'],
        );
        $this->assertSame('PT', $client->fresh()->country);
    }

    // AC 5
    public function test_gestor_cria_pelo_service_com_as_mesmas_validacoes_e_a_autoria_dele(): void
    {
        $manager = User::factory()->projectManager()->create();

        $client = $this->service->create($this->data(), $manager);
        $this->assertAudited($client, 'created', [
            'name' => [null, 'Padaria Pão Quente'],
            'country' => [null, 'BR'],
            'document' => [null, '12ABC34501DE35'],
        ], $manager);

        $this->assertRefused(fn () => $this->service->create($this->data(['name' => 'Outra']), $manager), ['document' => 'Já existe o cliente Padaria Pão Quente com este CNPJ']);
        $this->assertRefused(fn () => $this->service->create($this->data(['name' => 'Outra', 'document' => '123']), $manager), ['document' => 'Informe um CNPJ válido.']);
    }

    // AC 6: quem decide se ele gere é a ClientPolicy; o Service aplica as mesmas validações.
    public function test_gestor_edita_cliente_do_projeto_dele_com_as_mesmas_validacoes(): void
    {
        $manager = User::factory()->projectManager()->create();
        $client = Client::factory()->create(['name' => 'Nome antigo', 'document' => '38614817000122']);
        ClientManagementFalso::ligar()->gere($manager, $client);

        $this->service->update($client, $this->data(['name' => 'Nome novo', 'document' => '38.614.817/0001-22']), $manager);

        $this->assertSame('Nome novo', $client->fresh()->name);
        $this->assertAudited($client, 'updated', ['name' => ['Nome antigo', 'Nome novo']], $manager);
        $this->assertRefused(
            fn () => $this->service->update($client, $this->data(['document' => '38614817000123']), $manager),
            ['document' => 'Informe um CNPJ válido.'],
        );
        $this->assertSame('38614817000122', $client->fresh()->document);
    }

    // AC 9: projeto aberto não impede; sai da lista de escolha.
    public function test_desativar_cliente_com_projetos_abertos_grava_quem_e_quando_e_tira_da_escolha(): void
    {
        $client = Client::factory()->create(['name' => 'Com projetos']);
        $this->project($client, User::factory()->projectManager()->create());

        $this->service->deactivate($client, $this->admin);

        $client->refresh();
        $this->assertFalse($client->isActive());
        $this->assertNotNull($client->deactivated_at);
        $this->assertSame($this->admin->id, $client->deactivated_by);
        $this->assertAudited($client, 'deactivated', ['active' => [true, false]]);
        $this->assertNotContains($client->id, array_column($this->service->choiceList($this->admin), 'id'));
    }

    public function test_desativar_quem_ja_esta_desativado_mantem_o_carimbo_original(): void
    {
        $other = User::factory()->admin()->create();
        $client = Client::factory()->create();
        $this->service->deactivate($client, $other);
        $stamp = $client->fresh()->deactivated_at;

        $this->service->deactivate($client, $this->admin);

        $this->assertEquals($stamp, $client->fresh()->deactivated_at);
        $this->assertSame($other->id, $client->fresh()->deactivated_by);
        $this->assertSame(1, AuditLog::query()->where('action', 'deactivated')->count());
    }

    // CR-1-15 do ciclo 1: a segunda desativação, por uma instância carregada antes da primeira
    // (ainda "ativa" em memória), decide pela linha do banco e não grava nada.
    public function test_desativar_pela_instancia_velha_nao_grava_outra_desativacao(): void
    {
        $other = User::factory()->admin()->create();
        $id = Client::factory()->create()->id;
        $a = Client::query()->findOrFail($id);
        $b = Client::query()->findOrFail($id);

        $this->service->deactivate($a, $other);
        $stamp = $a->fresh()->deactivated_at;
        $this->service->deactivate($b, $this->admin);

        $this->assertSame(1, AuditLog::query()->where('action', 'deactivated')->count());
        $this->assertEquals($stamp, $a->fresh()->deactivated_at);
        $this->assertSame($other->id, $a->fresh()->deactivated_by);
    }

    // AC 10: a Policy responde 403; o Service também recusa, para quem o chamar direto.
    public function test_wcj_nao_se_desativa_pelo_service(): void
    {
        $this->seed(WcjClientSeeder::class);
        $wcj = Client::query()->where('document', WcjClientSeeder::CNPJ)->sole();

        try {
            $this->service->deactivate($wcj, $this->admin);
            $this->fail('Desativou a WCJ.');
        } catch (DomainException $e) {
            $this->assertSame('A WCJ não pode ser desativada', $e->getMessage());
        }

        $this->assertTrue($wcj->fresh()->isActive());
        $this->assertSame(0, AuditLog::query()->count());
    }

    // CR-1-04 do ciclo 1: a trava de excluir a WCJ não fica só na Policy.
    public function test_wcj_nao_se_exclui_pelo_service(): void
    {
        $this->seed(WcjClientSeeder::class);
        $wcj = Client::query()->where('document', WcjClientSeeder::CNPJ)->sole();

        try {
            $this->service->delete($wcj, $this->admin);
            $this->fail('Excluiu a WCJ.');
        } catch (DomainException $e) {
            $this->assertSame('A WCJ não pode ser excluída', $e->getMessage());
        }

        $this->assertModelExists($wcj);
        $this->assertSame(0, AuditLog::query()->where('action', 'deleted')->count());
    }

    /** @return array<string, array{array<string, string|null>}> */
    public static function trocaDeIdentidadeDaWcj(): array
    {
        return [
            'CNPJ' => [['document' => '12ABC34501DE35']],
            'país' => [['country' => 'NL']],
            'país e documento' => [['country' => 'NL', 'document' => null]],
        ];
    }

    // T38 (A2 da review de 07/10), N7: com outro CNPJ ou país, a WCJ deixaria de ser reconhecida
    // e perderia a trava de desativar e excluir. Vale para quem chamar o Service.
    #[DataProvider('trocaDeIdentidadeDaWcj')]
    public function test_wcj_nao_troca_de_cnpj_nem_de_pais_nem_pelo_admin_nem_pelo_gestor_que_a_gere(array $troca): void
    {
        $this->seed(WcjClientSeeder::class);
        $wcj = Client::query()->where('document', WcjClientSeeder::CNPJ)->sole();
        $manager = User::factory()->projectManager()->create();
        ClientManagementFalso::ligar()->gere($manager, $wcj);

        foreach ([$this->admin, $manager] as $actor) {
            try {
                $this->service->update($wcj, ['name' => 'WCJ Tecnologia', 'country' => 'BR', 'document' => WcjClientSeeder::CNPJ, ...$troca], $actor);
                $this->fail('Trocou a identidade da WCJ.');
            } catch (DomainException $e) {
                $this->assertSame('O CNPJ e o país da WCJ não podem ser alterados', $e->getMessage());
            }
        }

        $wcj->refresh();
        $this->assertSame(['WCJ Tecnologia', 'BR', WcjClientSeeder::CNPJ], [$wcj->name, $wcj->country, $wcj->document]);
        $this->assertTrue($wcj->isWcj());
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_wcj_continua_trocando_de_nome(): void
    {
        $this->seed(WcjClientSeeder::class);
        $wcj = Client::query()->where('document', WcjClientSeeder::CNPJ)->sole();

        // Com a máscara: o mesmo CNPJ, então não é troca.
        $this->service->update($wcj, ['name' => 'WCJ Tecnologia Ltda', 'country' => 'BR', 'document' => '38.614.817/0001-22'], $this->admin);

        $this->assertSame('WCJ Tecnologia Ltda', $wcj->fresh()->name);
        $this->assertAudited($wcj, 'updated', ['name' => ['WCJ Tecnologia', 'WCJ Tecnologia Ltda']]);
    }

    public function test_reativar_limpa_quem_e_quando_e_volta_para_a_escolha(): void
    {
        $client = Client::factory()->create();
        $this->service->deactivate($client, $this->admin);

        $this->service->reactivate($client, $this->admin);

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'deactivated_at' => null, 'deactivated_by' => null]);
        $this->assertAudited($client, 'reactivated', ['active' => [false, true]]);
        $this->assertContains($client->id, array_column($this->service->choiceList($this->admin), 'id'));
    }

    // AC 11
    public function test_excluir_cliente_sem_contato_e_sem_projeto_apaga_e_audita(): void
    {
        $client = $this->service->create($this->data(), $this->admin);

        $this->service->delete($client, $this->admin);

        $this->assertModelMissing($client);
        $this->assertAudited($client, 'deleted', [
            'name' => ['Padaria Pão Quente', null],
            'country' => ['BR', null],
            'document' => ['12ABC34501DE35', null],
        ]);
    }

    // AC 12
    public function test_excluir_cliente_com_contato_ou_projeto_e_recusado_e_a_linha_fica(): void
    {
        $withContact = Client::factory()->create();
        Contact::query()->create(['client_id' => $withContact->id, 'name' => 'Ana Lima']);
        $withProject = Client::factory()->create();
        $this->project($withProject, User::factory()->projectManager()->create());

        foreach ([$withContact, $withProject] as $client) {
            try {
                $this->service->delete($client, $this->admin);
                $this->fail('Excluiu cliente já usado.');
            } catch (DomainException $e) {
                $this->assertSame('Cliente já usado: desative em vez de excluir', $e->getMessage());
            }
            $this->assertModelExists($client);
        }
        $this->assertSame(0, AuditLog::query()->where('action', 'deleted')->count());
    }

    // NUC-13 (Lista de escolha AC 2)
    public function test_lista_de_escolha_traz_so_ativos_com_id_e_nome_em_ordem(): void
    {
        $zeta = Client::factory()->create(['name' => 'Zeta']);
        Client::factory()->inactive()->create(['name' => 'Desativado']);
        $alfa = Client::factory()->create(['name' => 'Alfa']);

        $this->assertSame([['id' => $alfa->id, 'name' => 'Alfa'], ['id' => $zeta->id, 'name' => 'Zeta']], $this->service->choiceList($this->admin));
    }

    // CR-1-05 do ciclo 1, NUC-13 (Lista de escolha AC 3): a Policy vale dentro do Service.
    public function test_desenvolvedor_e_recusado_na_lista_de_escolha_e_a_negacao_e_auditada(): void
    {
        Client::factory()->create();
        $dev = User::factory()->developer()->create();

        try {
            $this->service->choiceList($dev);
            $this->fail('Desenvolvedor recebeu a lista de escolha.');
        } catch (AuthorizationException) {
        }

        $log = AuditLog::query()->sole();
        $this->assertSame(
            [$dev->id, 'viewed', AuditLog::OUTCOME_DENIED, 'client', null],
            [$log->user_id, $log->action, $log->outcome, $log->auditable_type, $log->auditable_id],
        );
    }

    public function test_gestor_recebe_a_lista_de_escolha_sem_negacao_auditada(): void
    {
        $ativo = Client::factory()->create(['name' => 'Ativo']);
        Client::factory()->inactive()->create(['name' => 'Desativado']);
        $manager = User::factory()->projectManager()->create();

        $this->assertSame([['id' => $ativo->id, 'name' => 'Ativo']], $this->service->choiceList($manager));
        $this->assertSame(0, AuditLog::query()->count());
    }

    // Done when: a factory gera CNPJ que o Service aceita.
    public function test_factory_gera_cnpj_valido(): void
    {
        foreach (Client::factory()->count(20)->make() as $client) {
            $erro = null;
            (new AlphanumericCnpj)->validate('document', $client->document, function (string $mensagem) use (&$erro) {
                $erro = $mensagem;
            });
            $this->assertNull($erro, "CNPJ inválido da factory: {$client->document}");
        }
    }
}
