<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Policies\ClientPolicy;
use Database\Seeders\WcjClientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ClientManagementFalso;
use Tests\TestCase;

/**
 * B1 T10, T33: matriz ability × papel da ClientPolicy (F1 §09; Clientes AC 5–8, 10; NUC-06,
 * NUC-07). O gestor vê, cria e escolhe; edita só o cliente que o ClientManagement diz que ele
 * gere (dublê aqui; com a implementação vazia, nenhum); desativar, reativar e excluir são do
 * Administrador, e a WCJ nunca se desativa.
 */
class ClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const SEM_REGISTRO = ['viewAny', 'create', 'viewChoiceList'];

    private const SO_ADMIN = ['deactivate', 'reactivate', 'delete'];

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::factory()->create();
    }

    public function test_registrada_no_gate(): void
    {
        $this->assertInstanceOf(ClientPolicy::class, Gate::getPolicyFor(Client::class));
    }

    /**
     * @return array<string, array{RoleEnum, bool, bool, bool, bool}>
     *                                                                papel, o contrato o lista como gestor do cliente, vê/cria/escolhe, edita, desativa/reativa/exclui
     */
    public static function papeis(): array
    {
        return [
            'administrador' => [RoleEnum::ADMIN, false, true, true, true],
            'gestor que gere o cliente' => [RoleEnum::PROJECT_MANAGER, true, true, true, false],
            'gestor que não gere o cliente' => [RoleEnum::PROJECT_MANAGER, false, true, false, false],
            // Mesmo que o contrato o listasse, dev não edita cliente (P5): a Policy confere o papel.
            'desenvolvedor listado pelo contrato' => [RoleEnum::DEVELOPER, true, false, false, false],
            'comercial' => [RoleEnum::SALES, false, false, false, false],
        ];
    }

    #[DataProvider('papeis')]
    public function test_matriz_de_abilities_por_papel(RoleEnum $papel, bool $listado, bool $ve, bool $edita, bool $admin): void
    {
        $actor = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);
        $management = ClientManagementFalso::ligar();
        if ($listado) {
            $management->gere($actor, $this->client);
        }

        foreach (self::SEM_REGISTRO as $ability) {
            $this->assertSame($ve, $actor->can($ability, Client::class), "{$papel->slug()} {$ability}");
        }
        $this->assertSame($ve, $actor->can('view', $this->client), "{$papel->slug()} view");
        $this->assertSame($edita, $actor->can('update', $this->client), "{$papel->slug()} update");

        foreach (self::SO_ADMIN as $ability) {
            $this->assertSame($admin, $actor->can($ability, $this->client), "{$papel->slug()} {$ability}");
        }
    }

    // Clientes AC 6 e 7: a pergunta é por cliente; gerir um não abre os outros.
    public function test_gestor_edita_so_o_cliente_que_o_contrato_lista(): void
    {
        $manager = User::factory()->projectManager()->create();
        ClientManagementFalso::ligar()->gere($manager, $this->client);

        $this->assertTrue($manager->can('update', $this->client));
        $this->assertFalse($manager->can('update', Client::factory()->create()));
    }

    // Clientes AC 7 com a implementação vazia do B1: o gestor ainda vê, cria e escolhe, mas não edita.
    public function test_com_a_implementacao_vazia_o_gestor_nao_edita_cliente_algum(): void
    {
        $manager = User::factory()->projectManager()->create();

        $this->assertFalse($manager->can('update', $this->client));
        $this->assertTrue($manager->can('view', $this->client));
        foreach (self::SEM_REGISTRO as $ability) {
            $this->assertTrue($manager->can($ability, Client::class), $ability);
        }
    }

    // Clientes AC 10: a resposta traz a mensagem que o 403 mostra.
    public function test_wcj_nunca_se_desativa_nem_pelo_administrador(): void
    {
        $this->seed(WcjClientSeeder::class);
        $wcj = Client::query()->where('country', 'BR')->where('document', WcjClientSeeder::CNPJ)->sole();
        $admin = User::factory()->admin()->create();

        $response = Gate::forUser($admin)->inspect('deactivate', $wcj);

        $this->assertTrue($response->denied());
        $this->assertSame('A WCJ não pode ser desativada', $response->message());
        $this->assertTrue($admin->can('update', $wcj));
        $this->assertTrue($admin->can('reactivate', $wcj));
    }

    // Clientes AC 14: a WCJ existe em toda instalação. Numa instalação nova ela ainda não tem
    // projeto nem contato, e o "só exclui o nunca usado" a deixaria sair.
    public function test_wcj_nunca_se_exclui_nem_sem_historico(): void
    {
        $this->seed(WcjClientSeeder::class);
        $wcj = Client::query()->where('country', 'BR')->where('document', WcjClientSeeder::CNPJ)->sole();
        $admin = User::factory()->admin()->create();

        $response = Gate::forUser($admin)->inspect('delete', $wcj);

        $this->assertTrue($response->denied());
        $this->assertSame('A WCJ não pode ser excluída', $response->message());
        $this->assertTrue($admin->can('delete', Client::factory()->create()));
    }

    // N7: o nome se edita (Clientes AC 6); quem identifica a WCJ é o país com o CNPJ da semente.
    public function test_a_wcj_renomeada_continua_protegida(): void
    {
        $this->seed(WcjClientSeeder::class);
        $wcj = Client::query()->where('country', 'BR')->where('document', WcjClientSeeder::CNPJ)->sole();
        $wcj->update(['name' => 'WCJ Tecnologia e Desenvolvimento de Sistemas Ltda']);
        $admin = User::factory()->admin()->create();

        $this->assertSame('A WCJ não pode ser desativada', Gate::forUser($admin)->inspect('deactivate', $wcj)->message());
        $this->assertSame('A WCJ não pode ser excluída', Gate::forUser($admin)->inspect('delete', $wcj)->message());
    }

    public function test_outro_cliente_com_o_nome_da_wcj_nao_e_a_wcj(): void
    {
        $this->seed(WcjClientSeeder::class);
        $admin = User::factory()->admin()->create();
        $homonimo = Client::factory()->create(['country' => 'BR', 'name' => 'WCJ Tecnologia']);

        $this->assertNotSame(WcjClientSeeder::CNPJ, $homonimo->document);
        $this->assertTrue(Gate::forUser($admin)->inspect('deactivate', $homonimo)->allowed());
        $this->assertTrue(Gate::forUser($admin)->inspect('delete', $homonimo)->allowed());
    }

    public function test_o_mesmo_documento_em_outro_pais_nao_e_a_wcj(): void
    {
        $admin = User::factory()->admin()->create();
        $foreign = Client::factory()->create(['country' => 'PT', 'document' => WcjClientSeeder::CNPJ]);

        $this->assertTrue($admin->can('deactivate', $foreign));
    }
}
