<?php

namespace Tests\Feature\Projetos;

use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Lista de projetos: quem vê o quê (P5, C14), busca e filtros (F2 §03, §05). */
class ProjectListTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private User $gestor;

    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();

        $this->gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create();

        // A: o gestor é o responsável. B: o gestor e o dev estão alocados. C: de outro gestor.
        $this->projeto(['key_prefix' => 'AAA', 'name' => 'A', 'manager_id' => $this->gestor->id]);
        $b = $this->projeto(['key_prefix' => 'BBB', 'name' => 'B']);
        $this->alocar($b, $this->gestor, $this->dev);
        $this->projeto(['key_prefix' => 'CCC', 'name' => 'C']);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<string>
     */
    private function prefixesFor(User $user, array $query = []): array
    {
        $prefixes = [];

        $this->actingAs($user)
            ->get('/projetos?'.http_build_query($query))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$prefixes) {
                $page->component('projects/index');
                $prefixes = array_column($page->toArray()['props']['projects']['data'], 'key_prefix');
            });

        return $prefixes;
    }

    public function test_each_role_sees_only_the_projects_it_may_see(): void
    {
        $this->assertSame(['AAA', 'BBB', 'CCC'], $this->prefixesFor(User::factory()->admin()->create()));
        $this->assertSame(['AAA', 'BBB'], $this->prefixesFor($this->gestor));
        $this->assertSame(['BBB'], $this->prefixesFor($this->dev));
    }

    public function test_sales_does_not_enter_projects_and_the_denial_is_audited(): void
    {
        $comercial = User::factory()->sales()->create();

        $this->actingAs($comercial)->get('/projetos')->assertForbidden();

        $this->assertDatabaseHas('audit_logs', ['user_id' => $comercial->id, 'action' => 'viewed', 'outcome' => 'denied', 'auditable_type' => 'project']);
    }

    /** CR-2-03: `can.manage` por linha lê o vínculo carregado em lote; o número de consultas não cresce com a página. */
    public function test_the_manager_list_loads_the_membership_in_batch_not_per_row(): void
    {
        $gestor = User::factory()->projectManager()->create();
        $alocar = function (int $quantos) use ($gestor): void {
            for ($i = 0; $i < $quantos; $i++) {
                $this->alocar($this->projeto(), $gestor);
            }
        };
        $consultas = function () use ($gestor): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($gestor)->get('/projetos')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
                ->where('projects.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['can']['manage'] === true)));
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };

        $alocar(3);
        $comTres = $consultas();
        $alocar(12);
        $comQuinze = $consultas();

        $this->assertSame($comTres, $comQuinze, 'a lista do gestor fez consultas a mais por linha');
    }

    public function test_finished_projects_are_hidden_until_the_filter_asks_for_them(): void
    {
        $admin = User::factory()->admin()->create();
        $this->projeto(['key_prefix' => 'FIM', 'name' => 'D'])->forceFill(['finished_at' => now()])->save();

        $this->assertNotContains('FIM', $this->prefixesFor($admin));
        $this->assertContains('FIM', $this->prefixesFor($admin, ['finished' => 1]));
    }

    public function test_search_matches_prefix_name_or_client_and_filters_combine(): void
    {
        $admin = User::factory()->admin()->create();
        $cliente = Client::factory()->create(['name' => 'Padaria Central']);
        $this->projeto(['key_prefix' => 'PAO', 'name' => 'Pedidos', 'client_id' => $cliente->id]);
        $this->projeto(['key_prefix' => 'ENT', 'name' => 'Entrega', 'client_id' => $cliente->id, 'due_on' => '2026-12-31']);

        $this->assertSame(['PAO'], $this->prefixesFor($admin, ['search' => 'pao']));
        $this->assertSame(['PAO'], $this->prefixesFor($admin, ['search' => 'Pedid']));
        $this->assertSame(['ENT', 'PAO'], $this->prefixesFor($admin, ['search' => 'Padaria']));
        $this->assertSame(['ENT'], $this->prefixesFor($admin, ['client_id' => $cliente->id, 'type' => 'delivery']));
        $this->assertSame(['PAO'], $this->prefixesFor($admin, ['client_id' => $cliente->id, 'type' => 'continuous']));
    }

    public function test_only_who_creates_projects_receives_the_client_and_manager_lists(): void
    {
        $this->actingAs($this->dev)->get('/projetos')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('formOptions', null));

        $this->actingAs($this->gestor)->get('/projetos')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('formOptions.clients')
                ->where('formOptions.managers', fn ($managers) => collect($managers)->pluck('id')->contains($this->gestor->id)));
    }
}
