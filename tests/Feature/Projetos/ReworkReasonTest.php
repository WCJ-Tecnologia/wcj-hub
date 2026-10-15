<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Services\ListService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Database\Seeders\ValidationTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Motivos de retrabalho, lista geral do Administrador (F2 §02 a §04, §09; P40, G2, G7). */
class ReworkReasonTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->admin = User::factory()->admin()->create();
    }

    private function outro(): ReworkReason
    {
        return ReworkReason::query()->where('slug', ReworkReason::OTHER)->sole();
    }

    public function test_the_list_comes_with_the_seed_and_other_last(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/listas')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/lists/index')
                ->where('can.create', true)
                ->where('reworkReasons', fn ($reasons) => collect($reasons)->pluck('label')->all() === ['Ajuste visual', 'Erro / bug', 'Regra de negócio', 'Requisito incompleto ou mal documentado', 'Outro'])
                ->where('reworkReasons.4.fixed', true)
                ->where('reworkReasons.4.requires_text', true)
                ->where('reworkReasons.4.can.deactivate', false)
                ->where('reworkReasons.4.can.delete', false)
                ->where('reworkReasons.0.can.delete', true));
    }

    public function test_create_edit_and_the_repeated_name_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/rework-reasons', ['label' => ' Performance ', 'requires_text' => true])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Motivo Performance criado.');
        $novo = ReworkReason::query()->where('label', 'Performance')->sole();
        $this->assertTrue($novo->requires_text);
        $this->assertNull($novo->slug);
        $this->assertDatabaseHas('audit_logs', ['action' => 'created', 'auditable_type' => 'rework_reason', 'auditable_id' => $novo->id]);

        $this->actingAs($this->admin)
            ->post('/admin/rework-reasons', ['label' => 'performance', 'requires_text' => false])
            ->assertSessionHasErrors(['label' => 'Já existe o motivo Performance.']);

        $this->actingAs($this->admin)
            ->put("/admin/rework-reasons/{$novo->id}", ['label' => 'Lentidão', 'requires_text' => false])
            ->assertSessionHas('success');
        $this->assertSame(['Lentidão', false], [$novo->fresh()->label, $novo->fresh()->requires_text]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'updated', 'auditable_type' => 'rework_reason', 'auditable_id' => $novo->id]);

        $this->actingAs($this->admin)
            ->put("/admin/rework-reasons/{$novo->id}", ['label' => 'Ajuste visual', 'requires_text' => false])
            ->assertSessionHasErrors('label');
        $this->actingAs($this->admin)->post('/admin/rework-reasons', ['label' => str_repeat('a', 81), 'requires_text' => false])->assertSessionHasErrors('label');
        $this->actingAs($this->admin)->post('/admin/rework-reasons', ['label' => 'Sem flag'])->assertSessionHasErrors('requires_text');
    }

    public function test_other_renames_but_keeps_asking_for_text_and_never_leaves(): void
    {
        $outro = $this->outro();

        $this->actingAs($this->admin)
            ->put("/admin/rework-reasons/{$outro->id}", ['label' => 'Outro motivo', 'requires_text' => true])
            ->assertSessionHas('success');
        $this->assertSame('Outro motivo', $outro->fresh()->label);

        $this->actingAs($this->admin)
            ->put("/admin/rework-reasons/{$outro->id}", ['label' => 'Outro motivo', 'requires_text' => false])
            ->assertSessionHas('error', ListService::OTHER_REQUIRES_TEXT);
        $this->assertTrue($outro->fresh()->requires_text);

        $this->actingAs($this->admin)->post("/admin/rework-reasons/{$outro->id}/deactivate")->assertSessionHas('error', ListService::OTHER_LOCKED);
        $this->actingAs($this->admin)->delete("/admin/rework-reasons/{$outro->id}")->assertSessionHas('error', ListService::OTHER_LOCKED);
        $this->assertTrue($outro->fresh()->isActive());
        $this->assertModelExists($outro);
    }

    public function test_deactivate_reactivate_and_delete_only_the_never_used(): void
    {
        $livre = ReworkReason::query()->where('label', 'Ajuste visual')->sole();
        $usado = ReworkReason::query()->where('label', 'Erro / bug')->sole();
        $this->seed(ValidationTypeSeeder::class);
        $project = $this->projeto();
        $task = $this->tarefa($project);
        TaskStageMovement::query()->create([
            'task_id' => $task->id, 'moved_by' => $this->admin->id,
            'from_board_stage_id' => $this->etapa($project, StageRole::TEST)->id, 'to_board_stage_id' => $this->etapa($project, StageRole::DEVELOPMENT)->id,
            'rework_origin_id' => 1, 'rework_reason_id' => $usado->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/rework-reasons/{$usado->id}/deactivate")->assertSessionHas('success');
        $this->assertFalse($usado->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['action' => 'deactivated', 'auditable_type' => 'rework_reason', 'auditable_id' => $usado->id]);

        $this->actingAs($this->admin)
            ->get('/admin/listas')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('reworkReasons', fn ($r) => ! collect($r)->pluck('label')->contains('Erro / bug')));
        $this->actingAs($this->admin)
            ->get('/admin/listas?show_inactive=1')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('reworkReasons', fn ($r) => collect($r)->firstWhere('label', 'Erro / bug')['active'] === false));

        $this->actingAs($this->admin)->post("/admin/rework-reasons/{$usado->id}/reactivate")->assertSessionHas('success');
        $this->assertTrue($usado->fresh()->isActive());

        $this->actingAs($this->admin)->delete("/admin/rework-reasons/{$usado->id}")->assertSessionHas('error', ListService::ALREADY_USED);
        $this->assertModelExists($usado);

        $this->actingAs($this->admin)->delete("/admin/rework-reasons/{$livre->id}")->assertSessionHas('success', 'Motivo Ajuste visual excluído.');
        $this->assertModelMissing($livre);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deleted', 'auditable_type' => 'rework_reason', 'auditable_id' => $livre->id]);
    }

    public function test_who_is_not_admin_gets_403_on_everything_and_nothing_changes(): void
    {
        $livre = ReworkReason::query()->where('label', 'Ajuste visual')->sole();
        $antes = ReworkReason::query()->orderBy('id')->get(['id', 'label', 'requires_text', 'deactivated_at'])->toArray();

        foreach ([User::factory()->projectManager()->create(), User::factory()->developer()->create(), User::factory()->sales()->create()] as $user) {
            $this->actingAs($user)->get('/admin/listas')->assertForbidden();
            $this->actingAs($user)->post('/admin/rework-reasons', ['label' => 'Tentativa', 'requires_text' => false])->assertForbidden();
            $this->actingAs($user)->put("/admin/rework-reasons/{$livre->id}", ['label' => 'Tentativa', 'requires_text' => false])->assertForbidden();
            $this->actingAs($user)->post("/admin/rework-reasons/{$livre->id}/deactivate")->assertForbidden();
            $this->actingAs($user)->post("/admin/rework-reasons/{$livre->id}/reactivate")->assertForbidden();
            $this->actingAs($user)->delete("/admin/rework-reasons/{$livre->id}")->assertForbidden();

            foreach (['viewed', 'created', 'updated', 'deactivated', 'reactivated', 'deleted'] as $acao) {
                $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => $acao, 'outcome' => 'denied', 'auditable_type' => 'rework_reason']);
            }
        }

        $this->assertSame($antes, ReworkReason::query()->orderBy('id')->get(['id', 'label', 'requires_text', 'deactivated_at'])->toArray());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->where('auditable_type', 'rework_reason')->count());
    }
}
