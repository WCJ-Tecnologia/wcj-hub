<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TimeEntry;
use App\Domains\Projetos\Services\ProjectMemberService;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * TimeEntryPolicy (spec b5-sprint-e-horas: HRS-01, HRS-02; F5 §09; P30, C14): quem vê o projeto da
 * tarefa lança as próprias horas; o dono corrige e exclui as próprias enquanto está no projeto; o
 * gestor do projeto e o Administrador corrigem e excluem as de qualquer um. Toda negação vira
 * linha `denied`, e nada muda.
 */
class TimeEntryPolicyTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private Project $project;

    private User $gestor;

    private User $dev;

    private User $outroDev;

    private Task $task;

    private TimeEntry $doOutro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearProjetos();
        $this->gestor = User::factory()->projectManager()->create();
        $this->dev = User::factory()->developer()->create();
        $this->outroDev = User::factory()->developer()->create();
        $this->project = $this->projeto(['manager_id' => $this->gestor->id]);
        $this->alocar($this->project, $this->dev, $this->outroDev);
        $this->task = $this->tarefa($this->project);
        $this->doOutro = $this->task->timeEntries()->create(['user_id' => $this->outroDev->id, 'worked_on' => '2026-10-06', 'hours' => 2.5]);
    }

    private function lancar(User $user): TestResponse
    {
        return $this->actingAs($user)->post("/projetos/{$this->project->id}/tarefas/{$this->task->id}/horas", ['worked_on' => '2026-10-07', 'hours' => 1.5]);
    }

    /** @return array<string, array{string}> */
    public static function quemVe(): array
    {
        return ['dev alocado' => ['dev'], 'gestor responsável' => ['gestor'], 'administrador' => ['admin']];
    }

    #[DataProvider('quemVe')]
    public function test_who_sees_the_project_logs_own_hours(string $papel): void
    {
        $user = match ($papel) {
            'dev' => $this->dev,
            'gestor' => $this->gestor,
            'admin' => User::factory()->admin()->create(),
        };

        $this->lancar($user)->assertSessionHasNoErrors()->assertSessionHas('success');

        $entry = TimeEntry::query()->latest('id')->first();
        $this->assertSame([$user->id, '1.50', '2026-10-07'], [$entry->user_id, $entry->hours, $entry->worked_on->toDateString()]);
    }

    public function test_the_person_in_the_request_is_ignored_it_is_always_who_is_logged_in(): void
    {
        $this->actingAs($this->gestor)
            ->post("/projetos/{$this->project->id}/tarefas/{$this->task->id}/horas", ['worked_on' => '2026-10-07', 'hours' => 3, 'user_id' => $this->dev->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->gestor->id, TimeEntry::query()->latest('id')->value('user_id'));
    }

    /** @return array<string, array{string}> */
    public static function quemNaoVe(): array
    {
        return ['dev não alocado' => ['dev'], 'gestor que não gere' => ['gestor'], 'comercial' => ['comercial']];
    }

    #[DataProvider('quemNaoVe')]
    public function test_who_does_not_see_the_project_cannot_log_hours(string $papel): void
    {
        $user = match ($papel) {
            'dev' => User::factory()->developer()->create(),
            'gestor' => User::factory()->projectManager()->create(),
            'comercial' => User::factory()->sales()->create(),
        };

        $this->lancar($user)->assertForbidden();

        $this->assertSame(1, TimeEntry::query()->count());
        $log = AuditLog::query()->where('outcome', 'denied')->sole();
        $this->assertSame(['time_entry', 'created'], [$log->auditable_type, $log->action]);
    }

    public function test_owner_corrects_and_deletes_own_entry(): void
    {
        $this->actingAs($this->outroDev)->put("/horas/{$this->doOutro->id}", ['worked_on' => '2026-10-05', 'hours' => 3])->assertSessionHas('success');
        $this->assertSame('3.00', $this->doOutro->fresh()->hours);

        $this->actingAs($this->outroDev)->delete("/horas/{$this->doOutro->id}")->assertSessionHas('success');
        $this->assertNull(TimeEntry::query()->find($this->doOutro->id));
    }

    /** @return array<string, array{string}> */
    public static function quemCorrigeDeOutro(): array
    {
        return ['gestor responsável' => ['gestor'], 'gestor alocado' => ['alocado'], 'administrador' => ['admin']];
    }

    #[DataProvider('quemCorrigeDeOutro')]
    public function test_manager_and_admin_correct_and_delete_anyones_entry(string $papel): void
    {
        $user = match ($papel) {
            'gestor' => $this->gestor,
            'alocado' => tap(User::factory()->projectManager()->create(), fn (User $user) => $this->alocar($this->project, $user)),
            'admin' => User::factory()->admin()->create(),
        };

        $this->actingAs($user)->put("/horas/{$this->doOutro->id}", ['worked_on' => '2026-10-05', 'hours' => 4])->assertSessionHas('success');
        $this->assertSame([$this->outroDev->id, '4.00'], [$this->doOutro->fresh()->user_id, $this->doOutro->fresh()->hours]);

        $this->actingAs($user)->delete("/horas/{$this->doOutro->id}")->assertSessionHas('success');
        $this->assertNull(TimeEntry::query()->find($this->doOutro->id));
    }

    public function test_developer_cannot_correct_nor_delete_someone_elses_entry(): void
    {
        $this->assertCannotTouch($this->dev);
    }

    public function test_owner_who_left_the_project_cannot_correct_nor_delete_anymore(): void
    {
        app(ProjectMemberService::class)->remove($this->project, $this->outroDev, $this->gestor);

        $this->assertCannotTouch($this->outroDev);
    }

    public function test_project_manager_who_does_not_manage_cannot_correct(): void
    {
        $this->assertCannotTouch(User::factory()->projectManager()->create());
    }

    private function assertCannotTouch(User $user): void
    {
        $antes = $this->doOutro->fresh()->only(['worked_on', 'hours', 'user_id', 'task_id']);

        $this->actingAs($user)->put("/horas/{$this->doOutro->id}", ['worked_on' => '2026-10-05', 'hours' => 9])->assertForbidden();
        $this->actingAs($user)->delete("/horas/{$this->doOutro->id}")->assertForbidden();

        $this->assertSame($antes, $this->doOutro->fresh()->only(['worked_on', 'hours', 'user_id', 'task_id']));
        $this->assertSame(['updated', 'deleted'], AuditLog::query()->where('outcome', 'denied')->where('auditable_type', 'time_entry')->orderBy('id')->pluck('action')->all());
        $this->assertSame(0, AuditLog::query()->where('outcome', 'success')->where('auditable_type', 'time_entry')->count());
    }

    public function test_my_hours_is_for_everyone_but_sales(): void
    {
        foreach ([$this->dev, $this->gestor, User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get('/minhas-horas')->assertOk();
        }

        $comercial = User::factory()->sales()->create();
        $this->actingAs($comercial)->get('/minhas-horas')->assertForbidden();
        $log = AuditLog::query()->where('outcome', 'denied')->sole();
        $this->assertSame(['time_entry', 'viewed', $comercial->id], [$log->auditable_type, $log->action, $log->user_id]);
    }

    public function test_missing_entry_is_404_before_the_policy(): void
    {
        $this->actingAs($this->dev)->put('/horas/999999', ['worked_on' => '2026-10-05', 'hours' => 1])->assertNotFound();
        $this->assertSame(0, AuditLog::query()->count());
    }
}
