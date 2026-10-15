<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ProjectArea;
use App\Domains\Projetos\Services\ProjectAreaService;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Áreas do projeto (F2 §02 a §04; P18, G2, G7): nome único sem acento e caixa, desativar o usado, excluir só o nunca usado. */
class ProjectAreaTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
    }

    public function test_create_rename_and_the_repeated_name_is_refused_ignoring_accent_and_case(): void
    {
        $this->actingAs($this->gestor)
            ->post("/projetos/{$this->project->id}/areas", ['name' => ' Âncora '])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Área Âncora criada.');
        $area = $this->project->areas()->sole();
        $this->assertDatabaseHas('audit_logs', ['action' => 'created', 'auditable_type' => 'project_area', 'auditable_id' => $area->id]);

        foreach (['ancora', 'ÂNCORA', 'Ancora'] as $repetido) {
            $this->actingAs($this->gestor)
                ->post("/projetos/{$this->project->id}/areas", ['name' => $repetido])
                ->assertSessionHasErrors(['name' => 'Já existe a área Âncora neste projeto.']);
        }
        // Noutro projeto o mesmo nome é livre (P18: lista do projeto).
        $this->actingAs(User::factory()->admin()->create())->post('/projetos/'.$this->projeto()->id.'/areas', ['name' => 'Âncora'])->assertSessionHasNoErrors();

        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/areas/{$area->id}", ['name' => 'Fornecedores'])
            ->assertSessionHas('success');
        $this->assertSame('Fornecedores', $area->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'updated', 'auditable_type' => 'project_area', 'auditable_id' => $area->id]);

        $outra = $this->project->areas()->create(['name' => 'Admin']);
        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/areas/{$outra->id}", ['name' => 'FORNECEDORES'])
            ->assertSessionHasErrors('name');
        $this->assertSame('Admin', $outra->fresh()->name);
    }

    public function test_deactivate_reactivate_and_the_list_hides_inactive_until_asked(): void
    {
        $area = $this->project->areas()->create(['name' => 'Admin']);
        $this->tarefa($this->project, null, ['project_area_id' => $area->id]);

        $this->actingAs($this->gestor)
            ->post("/projetos/{$this->project->id}/areas/{$area->id}/deactivate")
            ->assertSessionHas('success');
        $this->assertFalse($area->fresh()->isActive());
        $this->assertSame($this->gestor->id, $area->fresh()->deactivated_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deactivated', 'auditable_type' => 'project_area', 'auditable_id' => $area->id]);

        $nomes = function (bool $inativos): array {
            $areas = [];
            $this->actingAs($this->gestor)
                ->get("/projetos/{$this->project->id}/configuracao?aba=areas".($inativos ? '&show_inactive=1' : ''))
                ->assertOk()
                ->assertInertia(function (AssertableInertia $page) use (&$areas) {
                    $areas = $page->toArray()['props']['areas'];
                });

            return $areas;
        };
        $this->assertSame([], $nomes(false));
        $this->assertSame(['Admin'], array_column($nomes(true), 'name'));
        $this->assertFalse($nomes(true)[0]['active']);
        $this->assertSame($this->gestor->name, $nomes(true)[0]['deactivated_by']);

        $this->actingAs($this->gestor)
            ->post("/projetos/{$this->project->id}/areas/{$area->id}/reactivate")
            ->assertSessionHas('success');
        $this->assertTrue($area->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['action' => 'reactivated', 'auditable_type' => 'project_area']);
    }

    public function test_delete_only_the_area_no_card_uses(): void
    {
        $livre = $this->project->areas()->create(['name' => 'Livre']);
        $usada = $this->project->areas()->create(['name' => 'Usada']);
        $this->tarefa($this->project, null, ['project_area_id' => $usada->id]);

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/areas/{$livre->id}")
            ->assertSessionHas('success', 'Área Livre excluída.');
        $this->assertModelMissing($livre);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deleted', 'auditable_type' => 'project_area', 'auditable_id' => $livre->id]);

        $this->actingAs($this->gestor)
            ->delete("/projetos/{$this->project->id}/areas/{$usada->id}")
            ->assertSessionHas('error', ProjectAreaService::ALREADY_USED);
        $this->assertModelExists($usada);
    }

    public function test_an_area_of_another_project_is_not_found_through_this_project(): void
    {
        $deFora = ProjectArea::query()->create(['project_id' => $this->projeto()->id, 'name' => 'De fora']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put("/projetos/{$this->project->id}/areas/{$deFora->id}", ['name' => 'Invasão'])->assertNotFound();
        $this->actingAs($admin)->delete("/projetos/{$this->project->id}/areas/{$deFora->id}")->assertNotFound();
        $this->actingAs($admin)->post("/projetos/{$this->project->id}/areas/{$deFora->id}/deactivate")->assertNotFound();

        $this->assertSame(['De fora', true], [$deFora->fresh()->name, $deFora->fresh()->isActive()]);
    }

    public function test_the_areas_tab_lists_by_name(): void
    {
        $this->project->areas()->create(['name' => 'Zebra']);
        $this->project->areas()->create(['name' => 'Admin']);

        $this->actingAs($this->gestor)
            ->get("/projetos/{$this->project->id}/configuracao?aba=areas")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.tab', 'areas')
                ->where('areas', fn ($areas) => collect($areas)->pluck('name')->all() === ['Admin', 'Zebra']));
    }
}
