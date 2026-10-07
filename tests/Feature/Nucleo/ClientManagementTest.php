<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Services\NoClientManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B1 T32: sem o B2, o contrato é respondido pela implementação vazia (spec, premissa "Gestor
 * dos projetos do cliente antes de o B2 existir"; NUC-06; ADR-001).
 */
class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_o_container_entrega_a_implementacao_vazia(): void
    {
        $this->assertInstanceOf(NoClientManagement::class, app(ClientManagement::class));
    }

    public function test_ninguem_gere_cliente_algum(): void
    {
        $management = app(ClientManagement::class);
        $client = Client::factory()->create();

        foreach ([User::factory()->projectManager()->create(), User::factory()->admin()->create()] as $user) {
            $this->assertFalse($management->manages($user, $client));
            $this->assertSame([], $management->managedClientIds($user));
        }
    }

    public function test_nenhum_cliente_tem_projeto_aberto(): void
    {
        $this->assertSame(0, app(ClientManagement::class)->openProjectsCount(Client::factory()->create()));
    }
}
