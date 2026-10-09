<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Platform\Services\MemoizedClientManagement;
use App\Platform\Services\NoClientManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

/**
 * B1 T32: sem o B2, o contrato é respondido pela implementação vazia (spec, premissa "Gestor
 * dos projetos do cliente antes de o B2 existir"; NUC-06; ADR-001).
 */
class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    // T42: a vazia fica atrás da memória por requisição; os dois testes abaixo provam que quem
    // responde é ela.
    public function test_o_container_entrega_a_implementacao_vazia(): void
    {
        $management = app(ClientManagement::class);

        $this->assertInstanceOf(MemoizedClientManagement::class, $management);
        $this->assertInstanceOf(NoClientManagement::class, (new ReflectionProperty($management, 'source'))->getValue($management));
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
        $ids = Client::factory()->count(2)->create()->modelKeys();

        $this->assertSame([$ids[0] => 0, $ids[1] => 0], app(ClientManagement::class)->openProjectsCountByClient($ids));
    }
}
