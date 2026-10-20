<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Models\TaskAttachment;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Notifications\TaskAssigned;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * A ficha do card (F3 §02 a §04, F5 §02; escopo B3, PR 2), uma regra por teste, pela rota que a
 * tela chama: a ficha no contrato `TaskDetail`, editar (título, requisição, card pai, tarefa de
 * origem, capa, dev e QA com aviso P22), horas (P27), critérios (P17, P37, T1, T9), links de PR
 * (P23), comentários (P24) e anexos (P24, P59).
 */
class TaskDetailTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $dev;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        Storage::fake('local');
        $this->gestor = User::factory()->projectManager()->create(['name' => 'Ana Gestora']);
        $this->dev = User::factory()->developer()->create(['name' => 'Bruno Dev', 'daily_hours' => 8]);
        $this->project = $this->projeto(['manager_id' => $this->gestor->id, 'key_prefix' => 'SGM']);
        $this->alocar($this->project, $this->dev);
        $this->task = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['title' => 'Login']);
    }

    private function url(string $suffix = '', ?Task $task = null): string
    {
        $task ??= $this->task;

        return "/projetos/{$this->project->id}/cards/SGM-{$task->number}{$suffix}";
    }

    /* ---------------------------------------------------------------- a ficha */

    public function test_the_detail_page_follows_the_contract(): void
    {
        $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'Entra com e-mail', 'test_result_id' => TestResult::PASSED]);
        $sub = $this->task->acceptanceCriteria()->create(['number' => 2, 'description' => 'Erros', 'test_result_id' => TestResult::PENDING]);
        $sub->children()->create(['task_id' => $this->task->id, 'number' => 1, 'description' => 'Senha errada', 'test_result_id' => TestResult::FAILED]);
        $this->task->comments()->create(['user_id' => $this->dev->id, 'body' => 'Começando']);

        $this->actingAs($this->dev)->get($this->url())->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('projects/task')
            ->where('project.key_prefix', 'SGM')
            ->where('task.key', "SGM-{$this->task->number}")
            ->where('task.stage.role', 'sprint')
            ->where('task.criteria.0.number', 'CA_1')
            ->where('task.criteria.0.result', 'approved')
            ->where('task.criteria.1.children.0.number', 'CA_2.1')
            ->where('task.criteria.1.children.0.result', 'failed')
            ->where('task.comments.0.author.name', 'Bruno Dev')
            ->where('task.comments.0.can', ['update' => true, 'delete' => true])
            ->where('task.created_by', null)
            ->where('task.abilities.update', true)
            ->where('task.abilities.propose_hours', true)
            ->where('task.abilities.set_hours', false)
            ->where('task.abilities.archive', false)
            ->where('task.abilities.delete', false)
            ->has('options.stages', 8)
            ->has('options.cards', 0));
    }

    /** Benchmark da ficha (CR-1-01 da revisão; escopo §10): o número de consultas não cresce com os itens do card. */
    public function test_the_number_of_queries_to_open_the_detail_does_not_grow_with_its_items(): void
    {
        $contar = function (User $as): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($as)->get($this->url())->assertOk();
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };
        $this->semeiaItens(2);
        [$dev, $gestor] = [$contar($this->dev), $contar($this->gestor)];
        $this->semeiaItens(30);

        $this->assertSame($dev, $contar($this->dev), 'a ficha consulta por item para o dev');
        $this->assertSame($gestor, $contar($this->gestor), 'a ficha consulta por item para quem gere');
    }

    /** Critério com subitem, comentário, PR, anexo, filho, bug de produção e participante, direto no banco. */
    private function semeiaItens(int $quantos): void
    {
        $outro = User::factory()->developer()->create();
        $this->alocar($this->project, $outro);
        $base = (int) $this->task->acceptanceCriteria()->whereNull('parent_id')->max('number');
        for ($i = 1; $i <= $quantos; $i++) {
            $criterio = $this->task->acceptanceCriteria()->create(['number' => $base + $i, 'description' => "CA {$i}", 'test_result_id' => TestResult::PENDING]);
            $criterio->children()->create(['task_id' => $this->task->id, 'number' => 1, 'description' => 'Sub', 'test_result_id' => TestResult::PASSED]);
            $this->task->comments()->create(['user_id' => $i % 2 ? $this->dev->id : $outro->id, 'body' => "Comentário {$i}"]);
            $this->task->pullRequests()->create(['url' => "https://github.com/x/y/pull/{$base}{$i}", 'user_id' => $outro->id]);
            Storage::disk('local')->put("tasks/{$this->task->id}/{$base}-{$i}.png", 'png');
            $this->task->attachments()->create(['path' => "tasks/{$this->task->id}/{$base}-{$i}.png", 'original_name' => "{$i}.png", 'mime_type' => 'image/png', 'user_id' => $outro->id]);
            $this->tarefa($this->project, null, ['parent_task_id' => $this->task->id, 'developer_id' => $outro->id]);
            $this->tarefa($this->project, null, ['origin_task_id' => $this->task->id]);
        }
        $this->task->participants()->syncWithoutDetaching([$outro->id]);
    }

    /* ---------------------------------------------------------------- editar */

    public function test_title_and_requisition_are_saved_trimmed_and_blank_clears(): void
    {
        $this->actingAs($this->dev)->patch($this->url(), [
            'title' => '  Tela de login  ', 'story_as' => 'gestor', 'story_want' => '  ', 'description' => 'Ver o Figma',
        ])->assertSessionHasNoErrors();

        $this->task->refresh();
        $this->assertSame(['Tela de login', 'gestor', null, 'Ver o Figma'], [$this->task->title, $this->task->story_as, $this->task->story_want, $this->task->description]);
        $this->actingAs($this->dev)->patch($this->url(), ['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_origin_and_parent_are_cards_of_the_same_project_one_level_only(): void
    {
        $pai = $this->tarefa($this->project, null, ['title' => 'Épico']);
        $avo = $this->tarefa($this->project);
        $neto = $this->tarefa($this->project, null, ['parent_task_id' => $pai->id]);
        $deOutro = $this->tarefa($this->projeto());

        $this->actingAs($this->dev)->patch($this->url(), ['origin_task_id' => $deOutro->id])->assertSessionHasErrors('origin_task_id');
        $this->actingAs($this->dev)->patch($this->url(), ['origin_task_id' => $this->task->id])->assertSessionHasErrors('origin_task_id');
        $this->actingAs($this->dev)->patch($this->url(), ['parent_task_id' => $neto->id])
            ->assertSessionHasErrors(['parent_task_id' => "SGM-{$neto->number} já é filho de SGM-{$pai->number}."]);
        $this->actingAs($this->dev)->patch($this->url('', $pai), ['parent_task_id' => $avo->id])
            ->assertSessionHasErrors(['parent_task_id' => "SGM-{$pai->number} já é pai de outros cards; um nível só."]);

        $this->actingAs($this->dev)->patch($this->url(), ['origin_task_id' => $avo->id, 'parent_task_id' => $pai->id])->assertSessionHasNoErrors();
        $this->assertSame([$avo->id, $pai->id], [$this->task->fresh()->origin_task_id, $this->task->fresh()->parent_task_id]);
        $this->actingAs($this->dev)->patch($this->url(), ['parent_task_id' => null])->assertSessionHasNoErrors();
        $this->assertNull($this->task->fresh()->parent_task_id);
    }

    public function test_dev_and_qa_are_allocated_people_and_are_warned_unless_self(): void
    {
        Notification::fake();
        $qa = User::factory()->developer()->create();
        $this->alocar($this->project, $qa);
        $fora = User::factory()->developer()->create();

        $this->actingAs($this->dev)->patch($this->url(), ['developer_id' => $fora->id])->assertSessionHas('error');
        $this->assertNull($this->task->fresh()->developer_id);

        $this->actingAs($this->dev)->patch($this->url(), ['developer_id' => $this->dev->id, 'qa_id' => $qa->id])->assertSessionHasNoErrors();

        $this->assertSame([$this->dev->id, $qa->id], [$this->task->fresh()->developer_id, $this->task->fresh()->qa_id]);
        Notification::assertNotSentTo($this->dev, TaskAssigned::class);
        Notification::assertSentTo($qa, TaskAssigned::class, fn (TaskAssigned $notice) => $notice->toArray($qa)['text']
            === "Você é o QA de SGM-{$this->task->number} — Login (projeto {$this->project->name}), definido por Bruno Dev."
            && $notice->via($qa) === ['database', 'mail']);
    }

    public function test_only_who_manages_sets_hours_directly(): void
    {
        $this->actingAs($this->dev)->patch($this->url(), ['estimated_hours' => 8])->assertForbidden();
        $this->actingAs($this->gestor)->patch($this->url(), ['estimated_hours' => 8])->assertSessionHasNoErrors();

        $this->assertSame('8.00', $this->task->fresh()->estimated_hours);
    }

    public function test_cover_is_an_image_attachment_of_the_card_and_removing_it_clears_the_cover(): void
    {
        $this->actingAs($this->dev)->post($this->url('/anexos'), ['files' => [
            UploadedFile::fake()->image('tela.png'), UploadedFile::fake()->create('spec.pdf', 10, 'application/pdf'),
        ]])->assertSessionHasNoErrors();
        [$png, $pdf] = TaskAttachment::query()->orderBy('id')->get()->all();
        $outra = $this->tarefa($this->project);

        $this->actingAs($this->dev)->patch($this->url(), ['cover_attachment_id' => $pdf->id])->assertSessionHasErrors('cover_attachment_id');
        $this->actingAs($this->dev)->patch($this->url('', $outra), ['cover_attachment_id' => $png->id])->assertSessionHasErrors('cover_attachment_id');
        $this->actingAs($this->dev)->patch($this->url(), ['cover_attachment_id' => $png->id])->assertSessionHasNoErrors();
        $this->assertSame($png->id, $this->task->fresh()->cover_attachment_id);

        $this->actingAs($this->dev)->delete($this->url("/anexos/{$png->id}"))->assertSessionHasNoErrors();

        $this->assertNull($this->task->fresh()->cover_attachment_id, 'a FK limpa a capa (P59)');
        Storage::disk('local')->assertMissing($png->path);
        Storage::disk('local')->assertExists($pdf->path);
    }

    /* ---------------------------------------------------------------- horas */

    public function test_dev_proposes_and_the_manager_approves_or_rejects(): void
    {
        $this->task->forceFill(['estimated_hours' => 8])->save();

        $this->actingAs($this->dev)->post($this->url('/horas/propor'), ['hours' => 12])->assertSessionHasNoErrors();
        $this->assertSame(['8.00', '12.00', $this->dev->id], [$this->task->fresh()->estimated_hours, $this->task->fresh()->proposed_hours, $this->task->fresh()->proposed_by]);

        $this->actingAs($this->dev)->post($this->url('/horas/aprovar'))->assertForbidden();
        $this->actingAs($this->gestor)->post($this->url('/horas/propor'), ['hours' => 5])->assertForbidden();
        $this->actingAs($this->gestor)->post($this->url('/horas/aprovar'))->assertSessionHas('success');
        $this->assertSame(['12.00', null, null], [$this->task->fresh()->estimated_hours, $this->task->fresh()->proposed_hours, $this->task->fresh()->proposed_by]);

        $this->actingAs($this->dev)->post($this->url('/horas/propor'), ['hours' => 20]);
        $this->actingAs($this->gestor)->post($this->url('/horas/recusar'))->assertSessionHas('success');
        $this->assertSame(['12.00', null], [$this->task->fresh()->estimated_hours, $this->task->fresh()->proposed_hours]);
    }

    /* ---------------------------------------------------------------- critérios */

    public function test_criteria_numbers_are_stable_and_subitems_go_under_the_parent(): void
    {
        foreach (['Um', 'Dois', 'Três'] as $description) {
            $this->actingAs($this->dev)->post($this->url('/criterios'), ['description' => $description])->assertSessionHasNoErrors();
        }
        $dois = TaskAcceptanceCriterion::query()->where('description', 'Dois')->sole();
        $this->actingAs($this->dev)->post($this->url('/criterios'), ['description' => 'Dois A', 'parent_id' => $dois->id])->assertSessionHasNoErrors();
        $this->actingAs($this->dev)->delete($this->url('/criterios/'.TaskAcceptanceCriterion::query()->where('description', 'Um')->value('id')))->assertSessionHasNoErrors();
        $this->actingAs($this->dev)->post($this->url('/criterios'), ['description' => 'Quatro'])->assertSessionHasNoErrors();

        // Excluir o último e reaproveitar o número é a pergunta 2 da spec b3-ficha (F3 × banco.md).
        $this->assertSame(
            [['Dois', null, 2], ['Três', null, 3], ['Dois A', $dois->id, 1], ['Quatro', null, 4]],
            TaskAcceptanceCriterion::query()->orderBy('id')->get()->map(fn ($c) => [$c->description, $c->parent_id, $c->number])->all(),
            'excluir não renumera (T9)',
        );
    }

    public function test_editing_a_tested_criterion_sends_it_back_to_pending(): void
    {
        $criterio = $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'A', 'test_result_id' => TestResult::PASSED]);

        $this->actingAs($this->dev)->patch($this->url("/criterios/{$criterio->id}"), ['description' => 'A, com B'])->assertSessionHasNoErrors();

        $this->assertSame(['A, com B', TestResult::PENDING], [$criterio->fresh()->description, $criterio->fresh()->test_result_id]);
    }

    public function test_a_tested_criterion_or_one_with_a_tested_subitem_is_not_deleted(): void
    {
        $pai = $this->task->acceptanceCriteria()->create(['number' => 3, 'description' => 'Pai', 'test_result_id' => TestResult::PENDING]);
        $pai->children()->create(['task_id' => $this->task->id, 'number' => 1, 'description' => 'Filho', 'test_result_id' => TestResult::FAILED]);

        $this->actingAs($this->dev)->delete($this->url("/criterios/{$pai->id}"))->assertSessionHas('error', 'CA_3 já foi testado: edite em vez de excluir.');

        $this->assertSame(2, TaskAcceptanceCriterion::query()->count());
    }

    public function test_deleting_a_pending_criterion_takes_the_subitems_and_audits_the_before(): void
    {
        $pai = $this->task->acceptanceCriteria()->create(['number' => 1, 'description' => 'Pai', 'test_result_id' => TestResult::PENDING]);
        $pai->children()->create(['task_id' => $this->task->id, 'number' => 1, 'description' => 'Filho', 'test_result_id' => TestResult::PENDING]);

        $this->actingAs($this->dev)->delete($this->url("/criterios/{$pai->id}"))->assertSessionHasNoErrors();

        $this->assertSame(0, TaskAcceptanceCriterion::query()->count());
        $log = AuditLog::query()->where('auditable_type', 'task_acceptance_criterion')->where('action', 'deleted')->sole();
        $this->assertSame(['Pai', 'Filho'], [$log->changes['description'][0], $log->changes['children'][0][0]['description']]);
    }

    public function test_a_criterion_of_another_card_is_404(): void
    {
        $outro = $this->tarefa($this->project);
        $criterio = $outro->acceptanceCriteria()->create(['number' => 1, 'description' => 'X', 'test_result_id' => TestResult::PENDING]);

        $this->actingAs($this->dev)->patch($this->url("/criterios/{$criterio->id}"), ['description' => 'Y'])->assertNotFound();
        $this->actingAs($this->dev)->delete($this->url("/criterios/{$criterio->id}"))->assertNotFound();
        $this->assertSame('X', $criterio->fresh()->description);
    }

    /* ---------------------------------------------------------------- PRs e comentários */

    public function test_pull_request_links_are_unique_per_card_and_record_who(): void
    {
        $url = 'https://github.com/WCJ-Tecnologia/sgm/pull/42';

        $this->actingAs($this->dev)->post($this->url('/prs'), ['url' => $url])->assertSessionHasNoErrors();
        $this->actingAs($this->dev)->post($this->url('/prs'), ['url' => $url])->assertSessionHasErrors(['url' => 'Este PR já está no card']);
        $this->actingAs($this->dev)->post($this->url('/prs'), ['url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');

        $pr = $this->task->pullRequests()->sole();
        $this->assertSame($this->dev->id, $pr->user_id);
        $this->actingAs($this->dev)->delete($this->url("/prs/{$pr->id}"))->assertSessionHasNoErrors();
        $this->assertSame(0, $this->task->pullRequests()->count());
    }

    public function test_the_author_edits_and_deletes_the_own_comment_only(): void
    {
        $this->actingAs($this->dev)->post($this->url('/comentarios'), ['body' => 'Olhei o layout'])->assertSessionHasNoErrors();
        $comment = $this->task->comments()->sole();

        $this->actingAs($this->gestor)->patch($this->url("/comentarios/{$comment->id}"), ['body' => 'Outro'])->assertForbidden();
        $this->actingAs($this->gestor)->delete($this->url("/comentarios/{$comment->id}"))->assertForbidden();
        $this->actingAs($this->dev)->patch($this->url("/comentarios/{$comment->id}"), ['body' => 'Olhei o layout e o fluxo'])->assertSessionHasNoErrors();
        $this->assertSame('Olhei o layout e o fluxo', $comment->fresh()->body);

        $this->actingAs($this->dev)->delete($this->url("/comentarios/{$comment->id}"))->assertSessionHasNoErrors();
        $this->assertSame(0, $this->task->comments()->count());
        $this->assertSame('Olhei o layout e o fluxo', AuditLog::query()->where('auditable_type', 'task_comment')->where('action', 'deleted')->where('outcome', 'success')->sole()->changes['body'][0]);
    }

    /* ---------------------------------------------------------------- anexos */

    public function test_attachments_are_checked_by_size_count_and_bytes_and_download_with_the_original_name(): void
    {
        config(['hub.attachments.max_kb' => 50, 'hub.attachments.max_files' => 2]);

        $this->actingAs($this->dev)->post($this->url('/anexos'), ['files' => [UploadedFile::fake()->create('grande.pdf', 60, 'application/pdf')]])
            ->assertSessionHasErrors('files.0');
        // O falso de teste do Laravel informa o tipo pelo nome; aqui o arquivo é de verdade, e o tipo sai dos bytes.
        $html = tempnam(sys_get_temp_dir(), 'hub');
        file_put_contents($html, '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>');
        $this->actingAs($this->dev)->post($this->url('/anexos'), ['files' => [new UploadedFile($html, 'falso.png', null, null, true)]])
            ->assertSessionHasErrors('files.0');
        $this->actingAs($this->dev)->post($this->url('/anexos'), ['files' => array_fill(0, 3, UploadedFile::fake()->image('a.png'))])
            ->assertSessionHasErrors('files');
        $this->assertSame(0, TaskAttachment::query()->count());

        $this->actingAs($this->dev)->post($this->url('/anexos'), ['files' => [UploadedFile::fake()->create('Relatório final.pdf', 10, 'application/pdf')]])
            ->assertSessionHasNoErrors();
        $file = TaskAttachment::query()->sole();
        $this->assertSame(['Relatório final.pdf', $this->dev->id], [$file->original_name, $file->user_id]);

        // O navegador usa o nome em UTF-8 (filename*); o sem acento é a reserva do cabeçalho.
        $download = $this->actingAs($this->dev)->get($this->url("/anexos/{$file->id}"))
            ->assertOk()->assertDownload('Relatorio final.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString("filename*=utf-8''Relat%C3%B3rio%20final.pdf", $download->headers->get('Content-Disposition'));
        $this->actingAs(User::factory()->developer()->create())->get($this->url("/anexos/{$file->id}"))->assertForbidden();
    }
}
