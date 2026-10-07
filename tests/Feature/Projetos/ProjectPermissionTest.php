<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Critérios de piso (especificacao.md §6) no projeto: quem não pode recebe 403 e nada muda
 * (F2 §09; P1, P5, C14). "Gere" = responsável ou alocado, com papel Gestor de projetos.
 */
class ProjectPermissionTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->project = $this->projeto(['key_prefix' => 'SGM', 'name' => 'Multas']);
    }

    /**
     * @return array<string, mixed>
     */
    private function editPayload(): array
    {
        return [
            'manager_id' => $this->project->manager_id,
            'key_prefix' => 'NOVO',
            'name' => 'Tentativa',
            'type' => 'continuous',
            'starts_on' => '2026-10-06',
        ];
    }

    /**
     * Toda escrita no projeto pela pessoa, cada uma com o status que voltou.
     *
     * @return array<string, TestResponse>
     */
    private function writesAs(User $user): array
    {
        $id = $this->project->id;

        return [
            'editar' => $this->actingAs($user)->put("/projetos/{$id}", $this->editPayload()),
            'encerrar' => $this->actingAs($user)->post("/projetos/{$id}/encerrar"),
            'reabrir' => $this->actingAs($user)->post("/projetos/{$id}/reabrir"),
            'excluir' => $this->actingAs($user)->delete("/projetos/{$id}"),
        ];
    }

    private function assertProjectUnchanged(): void
    {
        $fresh = Project::query()->find($this->project->id);
        $this->assertNotNull($fresh, 'o projeto continua existindo');
        $this->assertSame(
            $this->project->only(['key_prefix', 'name', 'manager_id', 'due_on', 'finished_at']),
            $fresh->only(['key_prefix', 'name', 'manager_id', 'due_on', 'finished_at']),
        );
    }

    public function test_a_developer_even_allocated_gets_403_on_every_write_and_nothing_changes(): void
    {
        $dev = User::factory()->developer()->create();
        $this->alocar($this->project, $dev);

        foreach ($this->writesAs($dev) as $action => $response) {
            $this->assertSame(403, $response->status(), $action);
        }
        $this->actingAs($dev)->post('/projetos', ['key_prefix' => 'DEV', 'name' => 'x'])->assertForbidden();

        $this->assertProjectUnchanged();
        $this->assertSame(1, Project::query()->count());
    }

    public function test_a_manager_who_does_not_manage_the_project_gets_403_and_nothing_changes(): void
    {
        $outroGestor = User::factory()->projectManager()->create();

        foreach ($this->writesAs($outroGestor) as $action => $response) {
            $this->assertSame(403, $response->status(), $action);
        }

        $this->assertProjectUnchanged();
    }

    public function test_sales_gets_403_on_every_write_and_nothing_changes(): void
    {
        $comercial = User::factory()->sales()->create();
        $this->alocar($this->project, $comercial);

        foreach ($this->writesAs($comercial) as $action => $response) {
            $this->assertSame(403, $response->status(), $action);
        }
        $this->actingAs($comercial)->post('/projetos', ['key_prefix' => 'COM', 'name' => 'x'])->assertForbidden();

        $this->assertProjectUnchanged();
        $this->assertSame(1, Project::query()->count());
    }

    public function test_a_manager_allocated_in_the_project_manages_it_like_the_responsible(): void
    {
        $gestorAlocado = User::factory()->projectManager()->create();
        $this->alocar($this->project, $gestorAlocado);

        $this->actingAs($gestorAlocado)
            ->put("/projetos/{$this->project->id}", $this->editPayload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('Tentativa', $this->project->refresh()->name);
    }

    public function test_the_responsible_who_lost_the_manager_role_no_longer_manages(): void
    {
        User::query()->whereKey($this->project->manager_id)->update(['role_id' => RoleEnum::DEVELOPER->value]);
        $rebaixado = User::query()->findOrFail($this->project->manager_id);

        $this->actingAs($rebaixado)->put("/projetos/{$this->project->id}", $this->editPayload())->assertForbidden();

        $this->assertProjectUnchanged();
    }

    public function test_only_the_admin_deletes_even_the_responsible_manager_gets_403(): void
    {
        $responsavel = User::query()->findOrFail($this->project->manager_id);

        $this->actingAs($responsavel)->delete("/projetos/{$this->project->id}")->assertForbidden();

        $this->assertModelExists($this->project);
    }

    public function test_admin_and_manager_create_projects(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->projectManager()->create()] as $index => $user) {
            $this->actingAs($user)
                ->post('/projetos', [
                    'client_id' => Client::factory()->create()->id,
                    'manager_id' => $user->id,
                    'key_prefix' => "OK{$index}",
                    'name' => "Projeto {$index}",
                    'type' => 'continuous',
                    'starts_on' => '2026-10-06',
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Project::query()->count());
    }
}
