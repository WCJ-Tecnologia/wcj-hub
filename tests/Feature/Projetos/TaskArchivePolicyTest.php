<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Permissões de tirar o card do quadro (spec b3-arquivar-e-avisos: EXC-01 item 5, ARQ-01 item 5;
 * F3 §09; P26): só quem gere o projeto arquiva, desarquiva e exclui. O dev alocado, o gestor de
 * outro projeto, o dev de fora e o Comercial recebem 403, com a negação auditada, e nada muda.
 */
class TaskArchivePolicyTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
        $this->alocar($this->project, $this->dev);
    }

    /** @return array<string, array{string}> */
    public static function quemNaoPode(): array
    {
        return [
            'dev alocado' => ['dev alocado'],
            'dev não alocado' => ['dev'],
            'gestor que não gere' => ['gestor'],
            'comercial' => ['comercial'],
        ];
    }

    private function url(Task $task, string $suffix = ''): string
    {
        return "/projetos/{$this->project->id}/cards/{$this->project->key_prefix}-{$task->number}{$suffix}";
    }

    /** O card nunca usado: no Backlog, só com o movimento de criação. */
    private function novo(array $attributes = []): Task
    {
        $task = $this->tarefa($this->project, null, $attributes);
        TaskStageMovement::query()->create(['task_id' => $task->id, 'moved_by' => $this->dev->id, 'from_board_stage_id' => null, 'to_board_stage_id' => $task->board_stage_id]);

        return $task;
    }

    /** Uma linha nova de auditoria, `denied`, desta pessoa, com a ação e o card (como o FCH-04). */
    private function assertOneDenied(User $user, Closure $request, string $action, int $taskId, string $caso): TestResponse
    {
        $before = AuditLog::query()->max('id') ?? 0;
        $response = $request();
        $rows = AuditLog::query()->where('id', '>', $before)->get();

        $this->assertCount(1, $rows, "{$caso}: exatamente uma linha nova de auditoria");
        $this->assertSame(['denied', $action, 'task', $taskId, $user->id], [$rows[0]->outcome, $rows[0]->action, $rows[0]->auditable_type, $rows[0]->auditable_id, $rows[0]->user_id], $caso);

        return $response;
    }

    #[DataProvider('quemNaoPode')]
    public function test_only_who_manages_the_project_archives_unarchives_and_deletes(string $papel): void
    {
        $user = match ($papel) {
            'dev alocado' => $this->dev,
            'dev' => User::factory()->developer()->create(),
            'gestor' => User::factory()->projectManager()->create(),
            'comercial' => User::factory()->sales()->create(),
        };
        $ativo = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT));
        $arquivado = $this->tarefa($this->project, $this->etapa($this->project, StageRole::DEVELOPMENT), ['archived_at' => now(), 'archive_reason' => 'Antigo']);
        $nunca = $this->novo();
        $antes = DB::table('tasks')->orderBy('id')->get(['id', 'board_stage_id', 'archived_at', 'archive_reason', 'archived_by', 'sprint_id'])->map(fn (object $row) => (array) $row)->all();
        $as = fn () => $this->actingAs($user);

        $this->assertOneDenied($user, fn () => $as()->post($this->url($ativo, '/arquivar'), ['archive_reason' => 'Intruso']), 'archived', $ativo->id, 'arquivar')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url($ativo, '/arquivar'), ['archive_reason' => '']), 'archived', $ativo->id, 'arquivar sem motivo: 403 antes da validação')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url($arquivado, '/desarquivar')), 'archived', $arquivado->id, 'desarquivar')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->delete($this->url($nunca)), 'deleted', $nunca->id, 'excluir')->assertForbidden();

        $this->assertSame($antes, DB::table('tasks')->orderBy('id')->get(['id', 'board_stage_id', 'archived_at', 'archive_reason', 'archived_by', 'sprint_id'])->map(fn (object $row) => (array) $row)->all());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->whereIn('action', ['archived', 'unarchived', 'deleted'])->count());
    }

    public function test_the_administrator_and_the_manager_allocated_with_the_role_may(): void
    {
        $alocado = User::factory()->projectManager()->create();
        $this->alocar($this->project, $alocado);
        $admin = User::factory()->admin()->create();

        $a = $this->tarefa($this->project);
        $this->actingAs($admin)->post($this->url($a, '/arquivar'), ['archive_reason' => 'Admin'])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->actingAs($alocado)->post($this->url($a, '/desarquivar'))->assertSessionMissing('error');
        $this->actingAs($alocado)->delete($this->url($this->novo()))->assertSessionMissing('error');

        $this->assertSame(1, AuditLog::query()->where('outcome', 'success')->where('action', 'unarchived')->count());
        $this->assertSame(1, AuditLog::query()->where('outcome', 'success')->where('action', 'deleted')->count());
    }

    public function test_a_card_of_another_project_is_not_found_through_this_project(): void
    {
        $outro = $this->projeto(['manager_id' => $this->gestor->id]);
        $alheio = $this->tarefa($outro);
        $url = "/projetos/{$this->project->id}/cards/{$outro->key_prefix}-{$alheio->number}";

        $this->actingAs($this->gestor)->post("{$url}/arquivar", ['archive_reason' => 'X'])->assertNotFound();
        $this->actingAs($this->gestor)->delete($url)->assertNotFound();

        $this->assertNull($alheio->fresh()->archived_at);
    }
}
