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
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * B1 T10: matriz ability × papel da ClientPolicy (F1 §09; Clientes AC 5–8, 10; NUC-06, NUC-07).
 * O gestor vê, cria e escolhe; edita só o cliente de projeto que gere; desativar, reativar e
 * excluir são do Administrador, e a WCJ nunca se desativa.
 */
class ClientPolicyTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

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
     *                                                                papel, gere um projeto do cliente, vê/cria/escolhe, edita, desativa/reativa/exclui
     */
    public static function papeis(): array
    {
        return [
            'administrador' => [RoleEnum::ADMIN, false, true, true, true],
            'gestor que gere o cliente' => [RoleEnum::PROJECT_MANAGER, true, true, true, false],
            'gestor que não gere o cliente' => [RoleEnum::PROJECT_MANAGER, false, true, false, false],
            // Alocado no projeto do cliente: trabalha nele, mas não o gere (P5).
            'desenvolvedor alocado' => [RoleEnum::DEVELOPER, true, false, false, false],
            'comercial' => [RoleEnum::SALES, false, false, false, false],
        ];
    }

    #[DataProvider('papeis')]
    public function test_matriz_de_abilities_por_papel(RoleEnum $papel, bool $noProjeto, bool $ve, bool $edita, bool $admin): void
    {
        $actor = User::factory()->create(['role_id' => $papel->value, 'daily_hours' => 8]);
        if ($noProjeto) {
            $manager = $papel === RoleEnum::PROJECT_MANAGER ? $actor : User::factory()->projectManager()->create();
            $this->project($this->client, $manager, members: $papel === RoleEnum::PROJECT_MANAGER ? [] : [$actor]);
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

    public function test_gestor_alocado_como_gestor_no_projeto_edita_o_cliente(): void
    {
        $responsible = User::factory()->projectManager()->create();
        $allocated = User::factory()->projectManager()->create();
        $this->project($this->client, $responsible, members: [$allocated]);

        $this->assertTrue($allocated->can('update', $this->client));
        $this->assertFalse($allocated->can('update', Client::factory()->create()));
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

    public function test_o_mesmo_documento_em_outro_pais_nao_e_a_wcj(): void
    {
        $admin = User::factory()->admin()->create();
        $foreign = Client::factory()->create(['country' => 'PT', 'document' => WcjClientSeeder::CNPJ]);

        $this->assertTrue($admin->can('deactivate', $foreign));
    }
}
