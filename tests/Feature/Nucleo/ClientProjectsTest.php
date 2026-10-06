<?php

namespace Tests\Feature\Nucleo;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Services\ProjectClientProjects;
use App\Platform\Contracts\ClientProjects;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B1 T5: o Núcleo pergunta a Projetos quais clientes cada gestor gere (D1, ADR-003; NUC-06)
 * e quantos projetos abertos o cliente tem (confirmação de desativar, Clientes AC 9).
 */
class ClientProjectsTest extends TestCase
{
    use RefreshDatabase;

    private int $prefixes = 0;

    private ClientProjects $clientProjects;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientProjects = app(ClientProjects::class);
    }

    public function test_o_contrato_e_respondido_pelo_dominio_de_projetos(): void
    {
        $this->assertInstanceOf(ProjectClientProjects::class, $this->clientProjects);
    }

    public function test_gestor_responsavel_pelo_projeto_gere_o_cliente(): void
    {
        $manager = User::factory()->projectManager()->create();
        $client = Client::factory()->create();
        $this->project($client, $manager);

        $this->assertSame([$client->id], $this->clientProjects->managedBy($manager));
        $this->assertTrue($this->clientProjects->manages($manager, $client->id));
    }

    public function test_gestor_alocado_no_projeto_gere_o_cliente(): void
    {
        $responsible = User::factory()->projectManager()->create();
        $allocated = User::factory()->projectManager()->create();
        $client = Client::factory()->create();
        $this->project($client, $responsible, members: [$allocated]);

        $this->assertSame([$client->id], $this->clientProjects->managedBy($allocated));
        $this->assertTrue($this->clientProjects->manages($allocated, $client->id));
    }

    public function test_dev_alocado_nao_gere_o_cliente(): void
    {
        $manager = User::factory()->projectManager()->create();
        $dev = User::factory()->developer()->create();
        $client = Client::factory()->create();
        $this->project($client, $manager, members: [$dev]);

        $this->assertSame([], $this->clientProjects->managedBy($dev));
        $this->assertFalse($this->clientProjects->manages($dev, $client->id));
    }

    public function test_projeto_de_outro_cliente_nao_conta(): void
    {
        $manager = User::factory()->projectManager()->create();
        $other = User::factory()->projectManager()->create();
        $mine = Client::factory()->create();
        $notMine = Client::factory()->create();
        $this->project($mine, $manager);
        $this->project($notMine, $other);

        $this->assertSame([$mine->id], $this->clientProjects->managedBy($manager));
        $this->assertFalse($this->clientProjects->manages($manager, $notMine->id));
    }

    public function test_cliente_de_varios_projetos_do_gestor_aparece_uma_vez(): void
    {
        $manager = User::factory()->projectManager()->create();
        $first = Client::factory()->create();
        $second = Client::factory()->create();
        $this->project($first, $manager);
        $this->project($first, $manager);
        $this->project($second, $manager);

        $this->assertSame([$first->id, $second->id], $this->clientProjects->managedBy($manager));
    }

    public function test_projetos_abertos_contam_so_os_sem_finished_at_do_cliente(): void
    {
        $manager = User::factory()->projectManager()->create();
        $client = Client::factory()->create();
        $other = Client::factory()->create();
        $this->project($client, $manager);
        $this->project($client, $manager);
        $this->project($client, $manager, finishedAt: now());
        $this->project($other, $manager);

        $this->assertSame(2, $this->clientProjects->openProjectsCount($client->id));
        $this->assertSame(0, $this->clientProjects->openProjectsCount(Client::factory()->create()->id));
    }

    /** @param  list<User>  $members */
    private function project(Client $client, User $manager, array $members = [], mixed $finishedAt = null): Project
    {
        $project = Project::query()->create([
            'client_id' => $client->id,
            'manager_id' => $manager->id,
            'key_prefix' => 'T'.++$this->prefixes,
            'name' => 'Projeto '.$this->prefixes,
            'starts_on' => now('America/Sao_Paulo')->toDateString(),
            'finished_at' => $finishedAt,
        ]);
        $project->users()->attach(array_map(fn (User $user) => $user->id, $members));

        return $project;
    }
}
