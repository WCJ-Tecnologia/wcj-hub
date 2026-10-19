<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * TaskPolicy (spec b3-quadro-e-card: TSK-01 a TSK-04; F3 §09; P13, P45, P52, P56, C14): quem vê
 * o projeto usa o quadro; só quem gere põe card na sprint, leva ao Backlog, cria card de suporte,
 * tipo e área. Toda negação vira linha `denied` com a ação certa, e nada muda.
 */
class TaskPolicyTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $dev;

    private Sprint $sprint;

    private Task $backlog;

    private Task $todo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
        $this->alocar($this->project, $this->dev);
        $this->sprint = $this->sprint($this->project);
        $this->backlog = $this->tarefa($this->project);
        $this->todo = $this->tarefa($this->project, $this->etapa($this->project, StageRole::SPRINT), ['sprint_id' => $this->sprint->id]);
    }

    /** @return array<string, array{string}> */
    public static function quemNaoVe(): array
    {
        return ['dev não alocado' => ['dev'], 'gestor que não gere' => ['gestor'], 'comercial' => ['comercial']];
    }

    /** @return array<string, array{string}> */
    public static function quemGere(): array
    {
        return ['administrador' => ['admin'], 'gestor responsável' => ['gestor'], 'gestor alocado' => ['alocado']];
    }

    private function estranho(string $papel): User
    {
        return match ($papel) {
            'dev' => User::factory()->developer()->create(),
            'gestor' => User::factory()->projectManager()->create(),
            'comercial' => User::factory()->sales()->create(),
        };
    }

    private function quemGereNo(string $papel): User
    {
        return match ($papel) {
            'admin' => User::factory()->admin()->create(),
            'gestor' => $this->gestor,
            'alocado' => tap(User::factory()->projectManager()->create(), fn (User $user) => $this->alocar($this->project, $user)),
        };
    }

    private function url(string $suffix = ''): string
    {
        return "/projetos/{$this->project->id}{$suffix}";
    }

    private function card(Task $task, string $suffix = ''): string
    {
        return $this->url("/cards/{$this->project->key_prefix}-{$task->number}{$suffix}");
    }

    private function mover(User $user, Task $task, int $role, array $extra = []): TestResponse
    {
        return $this->actingAs($user)->post($this->card($task, '/mover'), ['stage_id' => $this->etapa($this->project, $role)->id, ...$extra]);
    }

    /** @return array<string, mixed> */
    private function retrato(): array
    {
        return [
            'tasks' => Task::query()->orderBy('id')->get(['id', 'board_stage_id', 'sprint_id', 'position', 'task_type_id', 'project_area_id', 'task_priority_id'])->toArray(),
            'movements' => TaskStageMovement::query()->count(),
            'participants' => DB::table('task_participants')->count(),
            'types' => DB::table('task_types')->count(),
            'areas' => DB::table('project_areas')->count(),
        ];
    }

    /**
     * TSK-04 AC 1 e 2 (Verifier rodada 1, Fix 4; L-015): a requisição grava exatamente uma linha nova
     * de auditoria, `denied`, desta pessoa, com a ação e o alvo que a spec fecha (`task` com o id do
     * card; `task` com id nulo antes de o card existir; `project` em tipo e área). Nenhuma `success`.
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

    public function test_allocated_developer_sees_the_board_with_dev_abilities(): void
    {
        $this->actingAs($this->dev)->get($this->url('/quadro'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('projects/board')
                ->has('cards', 2)
                ->where('abilities', [
                    'create_card' => true, 'create_support' => false, 'manage_sprint' => false, 'move' => true,
                    'archive' => false, 'manage_types' => false, 'manage_areas' => false,
                ]));
    }

    #[DataProvider('quemGere')]
    public function test_who_manages_sees_the_board_with_every_ability(string $papel): void
    {
        $this->actingAs($this->quemGereNo($papel))->get($this->url('/quadro'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('abilities', [
                'create_card' => true, 'create_support' => true, 'manage_sprint' => true, 'move' => true,
                'archive' => true, 'manage_types' => true, 'manage_areas' => true,
            ]));
    }

    #[DataProvider('quemNaoVe')]
    public function test_who_does_not_see_the_project_gets_403_everywhere_and_nothing_changes(string $papel): void
    {
        $user = $this->estranho($papel);
        $antes = $this->retrato();
        $todo = $this->todo->id;

        $this->assertOneDenied($user, fn () => $this->actingAs($user)->get($this->url('/quadro')), 'viewed', 'project', $this->project->id, 'quadro')->assertForbidden();
        $this->assertOneDenied($user, fn () => $this->actingAs($user)->get($this->card($this->todo)), 'viewed', 'task', $todo, 'ficha')->assertForbidden();
        $this->assertOneDenied($user, fn () => $this->actingAs($user)->post($this->url('/cards'), ['title' => 'Intruso']), 'created', 'task', null, 'card rápido')->assertForbidden();
        $this->assertOneDenied($user, fn () => $this->mover($user, $this->todo, StageRole::DEVELOPMENT), 'moved', 'task', $todo, 'mover')->assertForbidden();
        $this->assertOneDenied($user, fn () => $this->actingAs($user)->patch($this->card($this->todo), ['priority' => 'urgent']), 'updated', 'task', $todo, 'etiqueta')->assertForbidden();
        $this->assertOneDenied($user, fn () => $this->actingAs($user)->post($this->card($this->todo, '/participantes'), ['user_id' => $this->dev->id, 'add' => true]), 'updated', 'task', $todo, 'participante')->assertForbidden();

        $this->assertSame($antes, $this->retrato());
    }

    /** TSK-01 (Verifier rodada 1, Fix 2): `view(Task)` pela rota do card, que até o PR 2 volta ao quadro com o aviso. */
    public function test_who_sees_the_project_opens_the_card_link_and_lands_on_the_board_with_the_notice(): void
    {
        $chave = "{$this->project->key_prefix}-{$this->todo->number}";

        foreach ([$this->dev, $this->quemGereNo('admin'), $this->gestor, $this->quemGereNo('alocado')] as $user) {
            $this->actingAs($user)->get($this->card($this->todo))
                ->assertRedirect($this->url('/quadro'))
                ->assertSessionHas('warning', "A ficha de {$chave} ainda não está pronta: chega no próximo PR do quadro.");
        }
        $this->assertSame(0, AuditLog::query()->where('outcome', 'denied')->count());

        $outro = $this->projeto();
        $deOutro = $this->tarefa($outro);
        $this->actingAs($this->gestor)->get($this->url("/cards/{$outro->key_prefix}-{$deOutro->number}"))->assertNotFound();
        $this->actingAs($this->gestor)->get($this->url("/cards/{$this->project->key_prefix}-999"))->assertNotFound();
    }

    /**
     * Premissa "Abilities da TaskPolicy" (Verifier rodada 1, Fix 3): comentar, anexar, arquivar e
     * excluir ainda não têm rota (PR 2 e 3), mas já decidem pela ProjectPolicy: vê × gere × de fora.
     */
    public function test_comment_attach_archive_and_delete_decide_by_the_project_policy_before_their_routes_exist(): void
    {
        $pessoas = [
            'dev alocado' => $this->dev,
            'administrador' => $this->quemGereNo('admin'),
            'gestor responsável' => $this->gestor,
            'gestor alocado' => $this->quemGereNo('alocado'),
            'dev de fora' => $this->estranho('dev'),
            'gestor de fora' => $this->estranho('gestor'),
            'comercial' => $this->estranho('comercial'),
        ];
        $decide = fn (string $ability) => array_map(fn (User $user) => Gate::forUser($user)->allows($ability, $this->todo), $pessoas);

        foreach (['comment', 'attach'] as $ability) {
            $this->assertSame(array_combine(array_keys($pessoas), [true, true, true, true, false, false, false]), $decide($ability), $ability);
        }
        foreach (['archive', 'delete'] as $ability) {
            $this->assertSame(array_combine(array_keys($pessoas), [false, true, true, true, false, false, false]), $decide($ability), $ability);
        }
    }

    public function test_allocated_developer_creates_moves_reorders_and_tags(): void
    {
        $this->actingAs($this->dev)->post($this->url('/cards'), ['title' => 'Do dev'])->assertSessionHasNoErrors()->assertRedirect();
        $this->mover($this->dev, $this->todo, StageRole::DEVELOPMENT)->assertSessionHasNoErrors()->assertRedirect();

        $novo = Task::query()->where('title', 'Do dev')->sole();
        // Reordenar dentro do Backlog não muda a sprint: qualquer alocado (F3 §04).
        $this->mover($this->dev, $novo, StageRole::QUEUE, ['sequence' => [$novo->id, $this->backlog->id]])->assertSessionHasNoErrors();
        $this->actingAs($this->dev)->patch($this->card($this->todo), ['priority' => 'high'])->assertSessionHasNoErrors();
        $this->actingAs($this->dev)->post($this->card($this->todo, '/participantes'), ['user_id' => $this->dev->id, 'add' => true])->assertSessionHasNoErrors();

        $this->assertSame(StageRole::DEVELOPMENT, $this->todo->fresh()->boardStage->stage_role_id);
        $this->assertSame([1, 2], [$novo->fresh()->position, $this->backlog->fresh()->position]);
        $this->assertSame('high', $this->todo->fresh()->taskPriority->slug);
        $this->assertSame([$this->dev->id], $this->todo->fresh()->participants->pluck('id')->all());
    }

    public function test_developer_does_not_put_on_the_sprint_nor_take_back_to_the_backlog(): void
    {
        $antes = $this->retrato();

        foreach ([[$this->backlog, StageRole::SPRINT, 'Backlog → To do'], [$this->backlog, StageRole::DEVELOPMENT, 'Backlog → Doing'], [$this->todo, StageRole::QUEUE, 'To do → Backlog']] as [$task, $role, $caso]) {
            $this->assertOneDenied($this->dev, fn () => $this->mover($this->dev, $task, $role), 'moved', 'task', $task->id, $caso)->assertForbidden();
        }

        $this->assertSame($antes, $this->retrato());
    }

    public function test_developer_does_not_create_support_card_nor_types_nor_areas(): void
    {
        $contato = Contact::factory()->create(['client_id' => $this->project->client_id]);
        $area = $this->project->areas()->create(['name' => 'Financeiro']);
        $bug = $this->project->taskTypes()->where('label', 'Correção de bug')->value('id');
        $antes = $this->retrato();
        $dev = $this->dev;
        $projeto = $this->project->id;

        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->post($this->url('/cards/suporte'), $this->suporte($contato)), 'created', 'task', null, 'suporte')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->post($this->url('/tipos'), ['name' => 'Pesquisa', 'color' => 'pink']), 'updated', 'project', $projeto, 'tipo novo')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->put($this->url("/tipos/{$this->tipo($this->project, 'improvement')}"), ['name' => 'X', 'color' => null]), 'updated', 'project', $projeto, 'editar tipo')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->post($this->url("/tipos/{$bug}/deactivate")), 'updated', 'project', $projeto, 'desativar tipo')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->delete($this->url("/tipos/{$bug}")), 'updated', 'project', $projeto, 'excluir tipo')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->post($this->url('/areas'), ['name' => 'Vendas', 'color' => 'red']), 'updated', 'project', $projeto, 'área nova')->assertForbidden();
        $this->assertOneDenied($dev, fn () => $this->actingAs($dev)->put($this->url("/areas/{$area->id}"), ['name' => 'Fin', 'color' => 'red']), 'updated', 'project', $projeto, 'editar área')->assertForbidden();

        $this->assertSame($antes, $this->retrato());
    }

    #[DataProvider('quemGere')]
    public function test_who_manages_puts_on_the_sprint_takes_back_and_creates_support_types_and_areas(string $papel): void
    {
        $user = $this->quemGereNo($papel);
        $contato = Contact::factory()->create(['client_id' => $this->project->client_id]);

        $this->mover($user, $this->backlog, StageRole::SPRINT)->assertSessionHasNoErrors();
        $this->mover($user, $this->todo, StageRole::QUEUE)->assertSessionHasNoErrors();
        $this->actingAs($user)->post($this->url('/cards/suporte'), $this->suporte($contato))->assertSessionHasNoErrors();
        $this->actingAs($user)->post($this->url('/tipos'), ['name' => 'Pesquisa', 'color' => 'pink'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post($this->url('/areas'), ['name' => 'Vendas', 'color' => 'red'])->assertSessionHasNoErrors();

        $this->assertSame([$this->sprint->id, StageRole::SPRINT], [$this->backlog->fresh()->sprint_id, $this->backlog->fresh()->boardStage->stage_role_id]);
        $this->assertSame([null, StageRole::QUEUE], [$this->todo->fresh()->sprint_id, $this->todo->fresh()->boardStage->stage_role_id]);
        $this->assertSame(1, Task::query()->whereNotNull('requester_contact_id')->count());
        $this->assertSame('pink', $this->project->taskTypes()->where('label', 'Pesquisa')->value('color'));
        $this->assertSame('red', $this->project->areas()->where('name', 'Vendas')->value('color'));
    }

    public function test_a_card_key_of_another_project_is_404_before_the_policy(): void
    {
        $outro = $this->projeto();
        $deOutro = $this->tarefa($outro);

        $this->actingAs($this->gestor)->patch($this->url("/cards/{$outro->key_prefix}-{$deOutro->number}"), ['priority' => 'low'])->assertNotFound();
        $this->actingAs($this->gestor)->patch($this->url("/cards/{$this->project->key_prefix}-999"), ['priority' => 'low'])->assertNotFound();
        $this->assertSame(0, AuditLog::query()->where('outcome', 'denied')->count());
    }

    public function test_losing_the_project_manager_role_denies_on_the_next_request(): void
    {
        $this->mover($this->gestor, $this->backlog, StageRole::SPRINT)->assertSessionHasNoErrors();
        $this->gestor->forceFill(['role_id' => User::factory()->developer()->make()->role_id])->save();

        $this->mover($this->gestor->fresh(), $this->todo, StageRole::QUEUE)->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function suporte(Contact $contato): array
    {
        return [
            'title' => 'Cliente pediu relatório', 'requester_contact_id' => $contato->id,
            'contact_channel_id' => 1, 'requested_at' => '2026-10-15 09:30', 'priority' => 'normal',
        ];
    }
}
