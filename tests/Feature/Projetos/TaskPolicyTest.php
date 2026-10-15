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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function assertNegado(User $user, string $action): void
    {
        $this->assertTrue(
            AuditLog::query()->where('user_id', $user->id)->where('outcome', 'denied')->where('action', $action)->exists(),
            "faltou a linha denied '{$action}'",
        );
        $this->assertFalse(AuditLog::query()->where('user_id', $user->id)->where('outcome', 'success')->exists(), 'nada de success');
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

        $this->actingAs($user)->get($this->url('/quadro'))->assertForbidden();
        $this->actingAs($user)->post($this->url('/cards'), ['title' => 'Intruso'])->assertForbidden();
        $this->mover($user, $this->todo, StageRole::DEVELOPMENT)->assertForbidden();
        $this->actingAs($user)->patch($this->card($this->todo), ['priority' => 'urgent'])->assertForbidden();
        $this->actingAs($user)->post($this->card($this->todo, '/participantes'), ['user_id' => $this->dev->id, 'add' => true])->assertForbidden();

        $this->assertSame($antes, $this->retrato());
        $this->assertNegado($user, 'viewed');
        $this->assertNegado($user, 'created');
        $this->assertNegado($user, 'moved');
        $this->assertNegado($user, 'updated');
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

        $this->mover($this->dev, $this->backlog, StageRole::SPRINT)->assertForbidden();
        $this->mover($this->dev, $this->backlog, StageRole::DEVELOPMENT)->assertForbidden();
        $this->mover($this->dev, $this->todo, StageRole::QUEUE)->assertForbidden();

        $this->assertSame($antes, $this->retrato());
        $this->assertNegado($this->dev, 'moved');
    }

    public function test_developer_does_not_create_support_card_nor_types_nor_areas(): void
    {
        $contato = Contact::factory()->create(['client_id' => $this->project->client_id]);
        $area = $this->project->areas()->create(['name' => 'Financeiro']);
        $antes = $this->retrato();

        $this->actingAs($this->dev)->post($this->url('/cards/suporte'), $this->suporte($contato))->assertForbidden();
        $this->actingAs($this->dev)->post($this->url('/tipos'), ['name' => 'Pesquisa', 'color' => 'pink'])->assertForbidden();
        $this->actingAs($this->dev)->put($this->url("/tipos/{$this->tipo($this->project, 'improvement')}"), ['name' => 'X', 'color' => null])->assertForbidden();
        $this->actingAs($this->dev)->post($this->url('/areas'), ['name' => 'Vendas', 'color' => 'red'])->assertForbidden();
        $this->actingAs($this->dev)->put($this->url("/areas/{$area->id}"), ['name' => 'Fin', 'color' => 'red'])->assertForbidden();

        $this->assertSame($antes, $this->retrato());
        $this->assertNegado($this->dev, 'created');
        $this->assertNegado($this->dev, 'updated');
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
