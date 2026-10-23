<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Models\TestResult;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Permissões da seção Teste (spec b4-teste-e-qa: CRUZ-01, CRUZ-02, ROTEIRO-01, EVID-01; F4 §09):
 * quem não vê o projeto recebe 403 em toda rota nova e nada muda, e cada negação vira exatamente
 * uma linha `denied` com a ação `updated` (AuditActionEnum::forAbility), como no TaskDetailPolicyTest.
 */
class TaskTestPolicyTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $dev;

    private User $marcou;

    private Task $task;

    private TaskAcceptanceCriterion $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        Storage::fake('local');
        $gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create();
        $this->marcou = User::factory()->developer()->create();
        $this->project = $this->projeto(['manager_id' => $gestor->id]);
        $this->alocar($this->project, $this->dev, $this->marcou);
        $this->task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::TEST), [
            'developer_id' => $this->dev->id, 'cross_tested_by' => $this->marcou->id, 'cross_tested_at' => now(),
        ]);
        $this->item = $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'CA', 'test_result_id' => TestResult::PENDING]);
    }

    /** @return array<string, array{string}> */
    public static function quemNaoVe(): array
    {
        return ['dev não alocado' => ['dev'], 'gestor que não gere' => ['gestor'], 'comercial' => ['comercial']];
    }

    private function url(string $suffix): string
    {
        return "/projetos/{$this->project->id}/cards/{$this->project->key_prefix}-{$this->task->number}{$suffix}";
    }

    /** @return array<string, mixed> */
    private function retrato(): array
    {
        $task = $this->task->fresh();
        $item = $this->item->fresh();

        return [
            'task' => [$task->cross_tested_by, $task->cross_tested_at?->toDateTimeString()],
            'item' => [$item->test_result_id, $item->marked_by, $item->marked_at?->toDateTimeString()],
            'attachments' => DB::table('task_attachments')->count(),
        ];
    }

    private function assertOneDenied(User $user, Closure $request, string $caso): TestResponse
    {
        $before = AuditLog::query()->max('id') ?? 0;
        $response = $request();
        $rows = AuditLog::query()->where('id', '>', $before)->get();

        $this->assertCount(1, $rows, "{$caso}: exatamente uma linha nova de auditoria");
        $this->assertSame(
            ['denied', 'updated', 'task', $this->task->id, $user->id],
            [$rows[0]->outcome, $rows[0]->action, $rows[0]->auditable_type, $rows[0]->auditable_id, $rows[0]->user_id],
            $caso,
        );

        return $response;
    }

    #[DataProvider('quemNaoVe')]
    public function test_who_does_not_see_the_project_gets_403_on_every_test_route_and_nothing_changes(string $papel): void
    {
        $user = match ($papel) {
            'dev' => User::factory()->developer()->create(),
            'gestor' => User::factory()->projectManager()->create(),
            'comercial' => User::factory()->sales()->create(),
        };
        $antes = $this->retrato();
        $as = fn () => $this->actingAs($user);

        $this->assertOneDenied($user, fn () => $as()->post($this->url('/teste-cruzado')), 'marcar')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/teste-cruzado/desfazer')), 'desfazer')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url("/criterios/{$this->item->id}/resultado"), ['result' => 'failed']), 'resultado')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/anexos'), [
            'files' => [UploadedFile::fake()->image('e.png')], 'criterion_id' => $this->item->id,
        ]), 'evidência')->assertForbidden();

        $this->assertSame($antes, $this->retrato());
    }

    public function test_the_developer_of_the_task_marking_is_denied_and_audited(): void
    {
        $this->task->forceFill(['cross_tested_by' => null, 'cross_tested_at' => null])->save();
        $antes = $this->retrato();

        $this->assertOneDenied($this->dev, fn () => $this->actingAs($this->dev)->post($this->url('/teste-cruzado')), 'o dev marca')->assertForbidden();

        $this->assertSame($antes, $this->retrato());
    }

    public function test_a_developer_who_did_not_mark_undoing_is_denied_and_audited(): void
    {
        $antes = $this->retrato();

        $this->assertOneDenied($this->dev, fn () => $this->actingAs($this->dev)->post($this->url('/teste-cruzado/desfazer')), 'outro dev desfaz')->assertForbidden();

        $this->assertSame($antes, $this->retrato());
    }

    public function test_any_allocated_person_runs_the_script_including_the_developer(): void
    {
        $this->actingAs($this->dev)->post($this->url("/criterios/{$this->item->id}/resultado"), ['result' => 'approved'])->assertSessionHasNoErrors();

        $this->assertSame(TestResult::PASSED, $this->item->fresh()->test_result_id);
    }
}
