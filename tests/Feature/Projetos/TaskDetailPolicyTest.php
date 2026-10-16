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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs($user)->get($this->url())->assertForbidden();
        $this->actingAs($user)->patch($this->url(), ['title' => 'Intruso'])->assertForbidden();
        $this->actingAs($user)->post($this->url('/horas/propor'), ['hours' => 1])->assertForbidden();
        $this->actingAs($user)->post($this->url('/horas/aprovar'))->assertForbidden();
        $this->actingAs($user)->post($this->url('/criterios'), ['description' => 'X'])->assertForbidden();
        $this->actingAs($user)->patch($this->url("/criterios/{$criterio}"), ['description' => 'X'])->assertForbidden();
        $this->actingAs($user)->delete($this->url("/criterios/{$criterio}"))->assertForbidden();
        $this->actingAs($user)->post($this->url('/prs'), ['url' => 'https://github.com/x/y/pull/2'])->assertForbidden();
        $this->actingAs($user)->delete($this->url("/prs/{$pr}"))->assertForbidden();
        $this->actingAs($user)->post($this->url('/comentarios'), ['body' => 'X'])->assertForbidden();
        $this->actingAs($user)->post($this->url('/anexos'), ['files' => [UploadedFile::fake()->create('b.pdf', 1, 'application/pdf')]])->assertForbidden();
        $this->actingAs($user)->get($this->url("/anexos/{$this->attachment->id}"))->assertForbidden();
        $this->actingAs($user)->delete($this->url("/anexos/{$this->attachment->id}"))->assertForbidden();

        $this->assertSame($antes, $this->retrato());
        Storage::disk('local')->assertExists('tasks/a.pdf');
        $this->assertSame(0, AuditLog::query()->where('user_id', $user->id)->where('outcome', 'success')->count());
        $this->assertGreaterThanOrEqual(13, AuditLog::query()->where('user_id', $user->id)->where('outcome', 'denied')->count());
    }

    public function test_the_developer_proposes_but_does_not_set_nor_decide_hours(): void
    {
        $antes = $this->retrato();

        $this->actingAs($this->dev)->patch($this->url(), ['estimated_hours' => 9])->assertForbidden();
        $this->actingAs($this->dev)->post($this->url('/horas/aprovar'))->assertForbidden();
        $this->actingAs($this->dev)->post($this->url('/horas/recusar'))->assertForbidden();

        $this->assertSame($antes, $this->retrato());
        $this->assertTrue(AuditLog::query()->where('user_id', $this->dev->id)->where('outcome', 'denied')->where('action', 'updated')->exists());
    }

    public function test_who_manages_does_not_propose_and_the_denial_is_audited(): void
    {
        $this->actingAs($this->gestor)->post($this->url('/horas/propor'), ['hours' => 1])->assertForbidden();

        $this->assertSame('5.00', $this->task->fresh()->proposed_hours);
        $this->assertTrue(AuditLog::query()->where('user_id', $this->gestor->id)->where('outcome', 'denied')->where('action', 'updated')->exists());
    }

    public function test_admin_and_manager_do_not_touch_someone_elses_comment(): void
    {
        foreach ([User::factory()->admin()->create(), $this->gestor] as $user) {
            $this->actingAs($user)->patch($this->url("/comentarios/{$this->comment->id}"), ['body' => 'Editado'])->assertForbidden();
            $this->actingAs($user)->delete($this->url("/comentarios/{$this->comment->id}"))->assertForbidden();
        }

        $this->assertSame('Meu', $this->comment->fresh()->body);
    }

    public function test_the_author_who_left_the_project_no_longer_touches_the_comment(): void
    {
        $this->project->members()->where('user_id', $this->dev->id)->delete();

        $this->actingAs($this->dev)->patch($this->url("/comentarios/{$this->comment->id}"), ['body' => 'Editado'])->assertForbidden();
        $this->actingAs($this->dev)->delete($this->url("/comentarios/{$this->comment->id}"))->assertForbidden();

        $this->assertSame('Meu', $this->comment->fresh()->body);
    }

    public function test_a_comment_or_attachment_of_another_card_is_404(): void
    {
        $outro = $this->tarefa($this->project);
        $alheio = $outro->comments()->create(['user_id' => $this->dev->id, 'body' => 'Do outro']);

        $this->actingAs($this->dev)->patch($this->url("/comentarios/{$alheio->id}"), ['body' => 'X'])->assertNotFound();
        $this->actingAs($this->dev)->delete($this->url("/comentarios/{$alheio->id}"))->assertNotFound();
        $other = $outro->attachments()->create(['path' => 'tasks/b.pdf', 'original_name' => 'b.pdf', 'mime_type' => 'application/pdf']);
        $this->actingAs($this->dev)->get($this->url("/anexos/{$other->id}"))->assertNotFound();

        $this->assertSame('Do outro', $alheio->fresh()->body);
    }
}
