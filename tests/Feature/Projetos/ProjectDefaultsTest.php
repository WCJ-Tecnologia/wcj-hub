<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Padrões do projeto (F2 §02, aba Padrões; P8, P15, P46). */
class ProjectDefaultsTest extends TestCase
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

    public function test_the_manager_saves_the_defaults_and_the_change_is_audited(): void
    {
        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/padroes", ['require_pause_reason' => false, 'sprint_days' => 15])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $fresh = $this->project->fresh();
        $this->assertFalse($fresh->require_pause_reason);
        $this->assertSame(15, $fresh->sprint_days);
        $log = AuditLog::query()->where('auditable_type', 'project')->where('action', 'updated')->sole();
        $this->assertSame([true, false], $log->changes['require_pause_reason']);
        $this->assertSame([10, 15], $log->changes['sprint_days']);

        // Salvar igual não grava auditoria de novo.
        $this->actingAs($this->gestor)->put("/projetos/{$this->project->id}/padroes", ['require_pause_reason' => false, 'sprint_days' => 15])->assertSessionHas('success');
        $this->assertSame(1, AuditLog::query()->where('auditable_type', 'project')->where('action', 'updated')->count());
    }

    public function test_the_sprint_length_must_be_a_positive_whole_number_of_days(): void
    {
        foreach ([0, -1, 'dez', 2.5, ''] as $valor) {
            $this->actingAs($this->gestor)
                ->put("/projetos/{$this->project->id}/padroes", ['require_pause_reason' => true, 'sprint_days' => $valor])
                ->assertSessionHasErrors('sprint_days');
        }
        $this->actingAs($this->gestor)
            ->put("/projetos/{$this->project->id}/padroes", ['sprint_days' => 5])
            ->assertSessionHasErrors('require_pause_reason');

        $this->assertSame([true, 10], [$this->project->fresh()->require_pause_reason, $this->project->fresh()->sprint_days]);
    }
}
