<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Services\TaskTypeService;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Tipos do projeto pela etiqueta (F3 §11 item 9; P18, G2, G7; CR-1-02 da revisão do B3 PR 1): as
 * mesmas quatro portas da área. Desativar o usado, excluir só o nunca usado, e os de nome interno
 * (Melhoria, Suporte, Desenvolvimento), que o código procura pelo slug, não se desativam nem se
 * excluem. Criar e editar estão no TaskPolicyTest e no TaskServiceTest.
 */
class TaskTypeTest extends TestCase
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

    private function url(string $suffix = ''): string
    {
        return "/projetos/{$this->project->id}/tipos{$suffix}";
    }

    /** @return list<string> os rótulos que o quadro oferece na etiqueta */
    private function tiposDoQuadro(): array
    {
        $labels = [];
        $this->actingAs($this->gestor)
            ->get("/projetos/{$this->project->id}/quadro")
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$labels) {
                $labels = array_column($page->toArray()['props']['options']['types'], 'label');
            });

        return $labels;
    }

    /** Rótulo único no projeto, sem diferença de acento e caixa; noutro projeto o mesmo nome é livre (P18; Verifier rodada 2, Fix 6). */
    public function test_a_repeated_label_is_refused_with_the_name_ignoring_accent_and_case_and_is_free_in_another_project(): void
    {
        foreach (['melhoria', 'MELHORIA', 'Melhoría'] as $repetido) {
            $this->actingAs($this->gestor)
                ->post($this->url(), ['name' => $repetido, 'color' => 'pink'])
                ->assertSessionHasErrors(['name' => 'Já existe o tipo Melhoria neste projeto.']);
        }
        $this->assertSame(5, $this->project->taskTypes()->count(), 'nada criado');

        $bug = $this->project->taskTypes()->where('label', 'Correção de bug')->sole();
        $this->actingAs($this->gestor)
            ->put($this->url("/{$bug->id}"), ['name' => 'suporte', 'color' => null])
            ->assertSessionHasErrors(['name' => 'Já existe o tipo Suporte neste projeto.']);
        $this->assertSame('Correção de bug', $bug->fresh()->label);

        $outro = $this->projeto();
        $outro->taskTypes()->create(['label' => 'Pesquisa']);
        $this->actingAs($this->gestor)->post($this->url(), ['name' => 'Pesquisa', 'color' => 'pink'])->assertSessionHasNoErrors();
        $this->assertSame(1, $this->project->taskTypes()->where('label', 'Pesquisa')->count(), 'o rótulo de outro projeto não trava este');
    }

    public function test_deactivate_takes_the_type_out_of_the_choice_and_the_card_keeps_it_until_reactivated(): void
    {
        $pesquisa = $this->project->taskTypes()->create(['label' => 'Pesquisa', 'color' => 'pink']);
        $card = $this->tarefa($this->project, null, ['task_type_id' => $pesquisa->id]);

        $this->actingAs($this->gestor)->post($this->url("/{$pesquisa->id}/deactivate"))->assertSessionHas('success', 'Tipo Pesquisa desativado.');
        $this->assertFalse($pesquisa->fresh()->isActive());
        $this->assertSame($this->gestor->id, $pesquisa->fresh()->deactivated_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deactivated', 'auditable_type' => 'task_type', 'auditable_id' => $pesquisa->id]);
        $this->assertNotContains('Pesquisa', $this->tiposDoQuadro());
        $this->assertSame($pesquisa->id, $card->fresh()->task_type_id, 'o card continua com o tipo (G7)');

        $this->actingAs($this->gestor)->post($this->url("/{$pesquisa->id}/reactivate"))->assertSessionHas('success', 'Tipo Pesquisa reativado.');
        $this->assertTrue($pesquisa->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['action' => 'reactivated', 'auditable_type' => 'task_type', 'auditable_id' => $pesquisa->id]);
        $this->assertContains('Pesquisa', $this->tiposDoQuadro());
    }

    public function test_delete_only_the_type_no_card_uses(): void
    {
        $livre = $this->project->taskTypes()->create(['label' => 'Livre']);
        $usado = $this->project->taskTypes()->create(['label' => 'Usado']);
        $this->tarefa($this->project, null, ['task_type_id' => $usado->id]);

        $this->actingAs($this->gestor)->delete($this->url("/{$livre->id}"))->assertSessionHas('success', 'Tipo Livre excluído.');
        $this->assertModelMissing($livre);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deleted', 'auditable_type' => 'task_type', 'auditable_id' => $livre->id]);

        $this->actingAs($this->gestor)->delete($this->url("/{$usado->id}"))->assertSessionHas('error', TaskTypeService::ALREADY_USED);
        $this->assertModelExists($usado);
    }

    public function test_the_types_the_code_finds_by_slug_are_neither_deactivated_nor_deleted(): void
    {
        foreach ([TaskType::IMPROVEMENT, TaskType::SUPPORT, TaskType::DEVELOPMENT] as $slug) {
            $type = $this->project->taskTypes()->where('slug', $slug)->sole();

            $this->actingAs($this->gestor)->post($this->url("/{$type->id}/deactivate"))->assertSessionHas('error', TaskTypeService::PROTECTED);
            $this->actingAs($this->gestor)->delete($this->url("/{$type->id}"))->assertSessionHas('error', TaskTypeService::PROTECTED);

            $this->assertTrue($type->fresh()->isActive(), $slug);
            $this->assertModelExists($type);
        }

        // O de rótulo sem slug (Correção de bug) é um tipo como outro qualquer.
        $bug = $this->project->taskTypes()->where('label', 'Correção de bug')->sole();
        $this->actingAs($this->gestor)->post($this->url("/{$bug->id}/deactivate"))->assertSessionHas('success');
        $this->assertFalse($bug->fresh()->isActive());
        $this->assertSame(4, $this->project->taskTypes()->active()->count(), 'ficam 4 ativos');
    }

    public function test_a_type_of_another_project_is_not_found_through_this_project(): void
    {
        $deFora = $this->projeto()->taskTypes()->where('slug', TaskType::IMPROVEMENT)->sole();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put($this->url("/{$deFora->id}"), ['name' => 'Invasão', 'color' => null])->assertNotFound();
        $this->actingAs($admin)->delete($this->url("/{$deFora->id}"))->assertNotFound();
        $this->actingAs($admin)->post($this->url("/{$deFora->id}/deactivate"))->assertNotFound();

        $this->assertSame(['Melhoria', true], [$deFora->fresh()->label, $deFora->fresh()->isActive()]);
    }
}
