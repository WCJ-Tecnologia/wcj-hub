<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Critérios de piso (especificacao.md §6) na configuração do projeto (F2 §09): quem não gere
 * recebe 403 em toda escrita e nada muda; o dev alocado vê pessoas e etapas só leitura; quem
 * não está no projeto não abre a tela.
 */
class ProjectSettingsPermissionTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $membro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->project = $this->projeto(['key_prefix' => 'SGM', 'name' => 'Multas']);
        $this->project->areas()->create(['name' => 'Admin']);
        $this->membro = User::factory()->developer()->create();
        $this->alocar($this->project, $this->membro);
    }

    /**
     * @return array<string, TestResponse>
     */
    private function writesAs(User $user): array
    {
        $id = $this->project->id;
        $area = $this->project->areas()->sole();
        $paused = $this->etapa($this->project, StageRole::PAUSED);
        $ordem = $this->project->boardStages()->orderBy('position')->pluck('id')->reverse()->values()->all();

        return [
            'alocar' => $this->actingAs($user)->post("/projetos/{$id}/pessoas", ['user_ids' => [User::factory()->developer()->create()->id]]),
            'tirar' => $this->actingAs($user)->delete("/projetos/{$id}/pessoas/{$this->membro->id}"),
            'nova etapa' => $this->actingAs($user)->post("/projetos/{$id}/etapas", ['name' => 'X', 'stage_role_id' => StageRole::DONE]),
            'editar etapa' => $this->actingAs($user)->put("/projetos/{$id}/etapas/{$paused->id}", ['name' => 'X']),
            'reordenar' => $this->actingAs($user)->put("/projetos/{$id}/etapas/ordem", ['ids' => $ordem]),
            'remover etapa' => $this->actingAs($user)->delete("/projetos/{$id}/etapas/{$paused->id}"),
            'nova área' => $this->actingAs($user)->post("/projetos/{$id}/areas", ['name' => 'X']),
            'editar área' => $this->actingAs($user)->put("/projetos/{$id}/areas/{$area->id}", ['name' => 'X']),
            'desativar área' => $this->actingAs($user)->post("/projetos/{$id}/areas/{$area->id}/deactivate"),
            'reativar área' => $this->actingAs($user)->post("/projetos/{$id}/areas/{$area->id}/reactivate"),
            'excluir área' => $this->actingAs($user)->delete("/projetos/{$id}/areas/{$area->id}"),
            'padrões' => $this->actingAs($user)->put("/projetos/{$id}/padroes", ['require_pause_reason' => false, 'sprint_days' => 3]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        return [
            'members' => $this->project->members()->orderBy('id')->pluck('user_id')->all(),
            'stages' => $this->project->boardStages()->orderBy('id')->get(['name', 'stage_role_id', 'position', 'archived_at'])->toArray(),
            'areas' => $this->project->areas()->orderBy('id')->get(['name', 'deactivated_at'])->toArray(),
            'defaults' => $this->project->fresh()->only(['require_pause_reason', 'sprint_days']),
        ];
    }

    private function assertAllForbiddenAndNothingChanged(User $user): void
    {
        $antes = $this->snapshot();
        $sucessos = AuditLog::query()->where('outcome', 'success')->count();

        foreach ($this->writesAs($user) as $acao => $response) {
            $this->assertSame(403, $response->status(), $acao);
        }

        $this->assertSame($antes, $this->snapshot());
        $this->assertSame($sucessos, AuditLog::query()->where('outcome', 'success')->count(), 'nenhuma escrita passou');
        $this->assertGreaterThanOrEqual(12, AuditLog::query()->where('user_id', $user->id)->where('outcome', 'denied')->where('action', 'updated')->where('auditable_type', 'project')->where('auditable_id', $this->project->id)->count());
    }

    public function test_an_allocated_developer_sees_people_and_stages_read_only_and_cannot_write(): void
    {
        $dev = User::factory()->developer()->create();
        $this->alocar($this->project, $dev);

        $this->actingAs($dev)
            ->get("/projetos/{$this->project->id}/configuracao")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('project.can.manage', false)
                ->has('members', 2)
                ->has('stages', 8)
                ->where('areas', null)
                ->where('options', null));

        $this->assertAllForbiddenAndNothingChanged($dev);
    }

    public function test_a_manager_who_does_not_manage_the_project_gets_403_on_the_screen_and_on_every_write(): void
    {
        $outroGestor = User::factory()->projectManager()->create();

        $this->actingAs($outroGestor)->get("/projetos/{$this->project->id}/configuracao")->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['user_id' => $outroGestor->id, 'action' => 'viewed', 'outcome' => 'denied', 'auditable_type' => 'project', 'auditable_id' => $this->project->id]);

        $this->assertAllForbiddenAndNothingChanged($outroGestor);
    }

    public function test_sales_gets_403_even_if_allocated(): void
    {
        $comercial = User::factory()->sales()->create();
        $this->alocar($this->project, $comercial);

        $this->actingAs($comercial)->get("/projetos/{$this->project->id}/configuracao")->assertForbidden();
        $this->assertAllForbiddenAndNothingChanged($comercial);
    }

    public function test_the_admin_and_the_allocated_manager_write_everything(): void
    {
        $gestorAlocado = User::factory()->projectManager()->create();
        $this->alocar($this->project, $gestorAlocado);

        foreach ([User::factory()->admin()->create(), $gestorAlocado] as $user) {
            $this->actingAs($user)
                ->get("/projetos/{$this->project->id}/configuracao")
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->where('project.can.manage', true)->has('areas')->has('options.people'));
            $this->actingAs($user)->post("/projetos/{$this->project->id}/areas", ['name' => 'Área de '.$user->id])->assertSessionHasNoErrors();
        }

        $this->assertSame(3, $this->project->areas()->count());
        $this->assertSame(0, DB::table('audit_logs')->where('outcome', 'denied')->count());
    }
}
