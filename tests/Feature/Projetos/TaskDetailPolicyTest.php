<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAttachment;
use App\Domains\Projetos\Models\TaskComment;
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
 * Permissões da ficha (spec b3-ficha: FCH-01, FCH-02, FCH-04; F3 §09, §10.10; P27): quem não vê o
 * projeto recebe 403 em toda rota da ficha e nada muda; o dev propõe horas e não decide; só o
 * autor, ainda no projeto, mexe no próprio comentário. Toda negação vira linha `denied`.
 */
class TaskDetailPolicyTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $dev;

    private Task $task;

    private TaskComment $comment;

    private TaskAttachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        Storage::fake('local');
        $this->gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
        $this->alocar($this->project, $this->dev);
        $this->task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['proposed_hours' => 5, 'estimated_hours' => 3]);
        $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'CA', 'test_result_id' => TestResult::PENDING]);
        $this->task->pullRequests()->create(['url' => 'https://github.com/x/y/pull/1']);
        $this->comment = $this->task->comments()->create(['user_id' => $this->dev->id, 'body' => 'Meu']);
        Storage::disk('local')->put('tasks/a.pdf', 'pdf');
        $this->attachment = $this->task->attachments()->create(['path' => 'tasks/a.pdf', 'original_name' => 'a.pdf', 'mime_type' => 'application/pdf']);
    }

    /** @return array<string, array{string}> */
    public static function quemNaoVe(): array
    {
        return ['dev não alocado' => ['dev'], 'gestor que não gere' => ['gestor'], 'comercial' => ['comercial']];
    }

    private function url(string $suffix = ''): string
    {
        return "/projetos/{$this->project->id}/cards/{$this->project->key_prefix}-{$this->task->number}{$suffix}";
    }

    /** @return array<string, mixed> */
    private function retrato(): array
    {
        return [
            'task' => $this->task->fresh()->only(['title', 'developer_id', 'estimated_hours', 'proposed_hours', 'story_as']),
            'criteria' => DB::table('task_acceptance_criteria')->orderBy('id')->pluck('description')->all(),
            'prs' => DB::table('task_pull_requests')->count(),
            'comments' => DB::table('task_comments')->orderBy('id')->pluck('body')->all(),
            'attachments' => DB::table('task_attachments')->count(),
        ];
    }

    /**
     * FCH-04 (como o TSK-04 do PR 1, L-015; CR-1-02 da revisão): a requisição grava exatamente uma
     * linha nova de auditoria, `denied`, desta pessoa, com a ação e o alvo (`task` com o id do card;
     * `task_comment` com o id do comentário; `task` com id nulo em `manageSprint`, que recebe a classe e o projeto). Nenhuma `success`.
     */
    private function assertOneDenied(User $user, Closure $request, string $action, string $type, ?int $id, string $caso): TestResponse
    {
        $before = AuditLog::query()->max('id') ?? 0;
        $response = $request();
        $rows = AuditLog::query()->where('id', '>', $before)->get();

        $this->assertCount(1, $rows, "{$caso}: exatamente uma linha nova de auditoria");
        $this->assertSame(
            ['denied', $action, $type, $id, $user->id],
            [$rows[0]->outcome, $rows[0]->action, $rows[0]->auditable_type, $rows[0]->auditable_id, $rows[0]->user_id],
            $caso,
        );

        return $response;
    }

    #[DataProvider('quemNaoVe')]
    public function test_who_does_not_see_the_project_gets_403_on_every_detail_route_and_nothing_changes(string $papel): void
    {
        $user = match ($papel) {
            'dev' => User::factory()->developer()->create(),
            'gestor' => User::factory()->projectManager()->create(),
            'comercial' => User::factory()->sales()->create(),
        };
        $antes = $this->retrato();
        $criterio = $this->task->acceptanceCriteria()->value('id');
        $pr = $this->task->pullRequests()->value('id');
        [$t, $tid, $c] = ['task', $this->task->id, $this->comment->id];
        $as = fn () => $this->actingAs($user);

        $this->assertOneDenied($user, fn () => $as()->get($this->url()), 'viewed', $t, $tid, 'ficha')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->patch($this->url(), ['title' => 'Intruso']), 'updated', $t, $tid, 'editar')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/horas/propor'), ['hours' => 1]), 'updated', $t, $tid, 'propor horas')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/horas/aprovar')), 'updated', $t, null, 'aprovar horas')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/horas/recusar')), 'updated', $t, null, 'recusar horas')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/criterios'), ['description' => 'X']), 'updated', $t, $tid, 'critério novo')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->patch($this->url("/criterios/{$criterio}"), ['description' => 'X']), 'updated', $t, $tid, 'editar critério')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->delete($this->url("/criterios/{$criterio}")), 'updated', $t, $tid, 'excluir critério')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/prs'), ['url' => 'https://github.com/x/y/pull/2']), 'updated', $t, $tid, 'PR novo')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->delete($this->url("/prs/{$pr}")), 'updated', $t, $tid, 'excluir PR')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/comentarios'), ['body' => 'X']), 'updated', $t, $tid, 'comentar')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->patch($this->url("/comentarios/{$c}"), ['body' => 'X']), 'updated', 'task_comment', $c, 'editar comentário')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->delete($this->url("/comentarios/{$c}")), 'deleted', 'task_comment', $c, 'excluir comentário')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->post($this->url('/anexos'), ['files' => [UploadedFile::fake()->create('b.pdf', 1, 'application/pdf')]]), 'updated', $t, $tid, 'anexar')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->get($this->url("/anexos/{$this->attachment->id}")), 'viewed', $t, $tid, 'baixar anexo')->assertForbidden();
        $this->assertOneDenied($user, fn () => $as()->delete($this->url("/anexos/{$this->attachment->id}")), 'updated', $t, $tid, 'excluir anexo')->assertForbidden();

        $this->assertSame($antes, $this->retrato());
        Storage::disk('local')->assertExists('tasks/a.pdf');
    }

    public function test_the_developer_proposes_but_does_not_set_nor_decide_hours(): void
    {
        $antes = $this->retrato();
        $dev = $this->dev;

        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->patch($this->url(), ['estimated_hours' => 9]), 'updated', 'task', null, 'definir horas')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->post($this->url('/horas/aprovar')), 'updated', 'task', null, 'aprovar')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->post($this->url('/horas/recusar')), 'updated', 'task', null, 'recusar')->assertForbidden();

        $this->assertSame($antes, $this->retrato());
    }

    public function test_who_manages_does_not_propose_and_the_denial_is_audited(): void
    {
        $gestor = $this->gestor;

        $this->assertOneDenied($gestor, fn () => $this->actingAs($gestor)->post($this->url('/horas/propor'), ['hours' => 1]), 'updated', 'task', $this->task->id, 'gestor propõe')->assertForbidden();

        $this->assertSame('5.00', $this->task->fresh()->proposed_hours);
    }

    public function test_admin_and_manager_do_not_touch_someone_elses_comment(): void
    {
        $c = $this->comment->id;
        foreach (['admin' => User::factory()->admin()->create(), 'gestor' => $this->gestor] as $papel => $user) {
            $this->assertOneDenied($user, fn () => $this->actingAs($user)->patch($this->url("/comentarios/{$c}"), ['body' => 'Editado']), 'updated', 'task_comment', $c, "{$papel} edita")->assertForbidden();
            $this->assertOneDenied($user, fn () => $this->actingAs($user)->delete($this->url("/comentarios/{$c}")), 'deleted', 'task_comment', $c, "{$papel} exclui")->assertForbidden();
        }

        $this->assertSame('Meu', $this->comment->fresh()->body);
    }

    public function test_the_author_who_left_the_project_no_longer_touches_the_comment(): void
    {
        $this->project->members()->where('user_id', $this->dev->id)->delete();
        $dev = $this->dev;
        $c = $this->comment->id;

        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->patch($this->url("/comentarios/{$c}"), ['body' => 'Editado']), 'updated', 'task_comment', $c, 'autor que saiu edita')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->delete($this->url("/comentarios/{$c}")), 'deleted', 'task_comment', $c, 'autor que saiu exclui')->assertForbidden();

        $this->assertSame('Meu', $this->comment->fresh()->body);
    }

    public function test_a_comment_or_attachment_of_another_card_is_404(): void
    {
        $outro = $this->tarefa($this->project);
        $alheio = $outro->comments()->create(['user_id' => $this->dev->id, 'body' => 'Do outro']);

        $this->actingAs($this->dev)->patch($this->url("/comentarios/{$alheio->id}"), ['body' => 'X'])->assertNotFound();
        $this->actingAs($this->dev)->delete($this->url("/comentarios/{$alheio->id}"))->assertNotFound();
        // O arquivo existe no disco: o 404 vem do vínculo com o card, não do arquivo ausente (Verifier rodada 1, Fix 5).
        Storage::disk('local')->put('tasks/b.pdf', 'pdf');
        $other = $outro->attachments()->create(['path' => 'tasks/b.pdf', 'original_name' => 'b.pdf', 'mime_type' => 'application/pdf']);
        $pr = $outro->pullRequests()->create(['url' => 'https://github.com/x/y/pull/9']);
        $this->actingAs($this->dev)->get($this->url("/anexos/{$other->id}"))->assertNotFound();
        $this->actingAs($this->dev)->delete($this->url("/anexos/{$other->id}"))->assertNotFound();
        $this->actingAs($this->dev)->delete($this->url("/prs/{$pr->id}"))->assertNotFound();

        $this->assertSame('Do outro', $alheio->fresh()->body);
        $this->assertTrue($outro->attachments()->whereKey($other->id)->exists(), 'o anexo do outro card continua');
        Storage::disk('local')->assertExists('tasks/b.pdf');
        $this->assertTrue($outro->pullRequests()->whereKey($pr->id)->exists(), 'o PR do outro card continua');
    }
}
