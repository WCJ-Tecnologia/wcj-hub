<?php

namespace Tests\Support;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TaskType;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Database\Seeders\ReworkReasonSeeder;
use Database\Seeders\StageRoleSeeder;
use Database\Seeders\TaskPrioritySeeder;
use Database\Seeders\TaskTypeSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Montagem de projeto nos testes, do Núcleo (B1) e de Projetos (B2).
 *
 * Núcleo: `project()` e `supportCard()` gravam direto na tabela, para o cliente com projeto ser
 * "já usado" (G2) e desativar mesmo com projeto aberto (N11), e para o contato citado num card de
 * suporte (P52). "Gestor do cliente" não sai daqui: vem do contrato ClientManagement (dublê
 * ClientManagementFalso ou a implementação real de Projetos).
 *
 * Projetos: domínios semeados (`semearProjetos()`), projeto com o quadro padrão pela factory
 * (`projeto()`), alocação, etapa e tarefa (gravada direto, com o número pelo contador: o card é
 * do B3).
 */
trait CriaProjeto
{
    private int $prefixosDeProjeto = 0;

    /** @param  list<User>  $members */
    private function project(Client $client, User $manager, array $members = [], mixed $finishedAt = null): Project
    {
        $project = Project::query()->create([
            'client_id' => $client->id,
            'manager_id' => $manager->id,
            'key_prefix' => 'P'.++$this->prefixosDeProjeto,
            'name' => 'Projeto '.$this->prefixosDeProjeto,
            'starts_on' => now('America/Sao_Paulo')->toDateString(),
            'finished_at' => $finishedAt,
        ]);
        $project->users()->attach(array_map(fn (User $user) => $user->id, $members));

        return $project;
    }

    /**
     * Card de suporte que cita o contato em "quem pediu" (P52): o "já usado" do contato (G2).
     * O B3 ainda não tem Service de card: grava direto na tabela.
     */
    private function supportCard(Contact $requester): int
    {
        $this->seed([StageRoleSeeder::class, TaskTypeSeeder::class, TaskPrioritySeeder::class]);
        $project = $this->project($requester->client, User::factory()->projectManager()->create());
        $stage = DB::table('board_stages')->insertGetId([
            'project_id' => $project->id, 'stage_role_id' => StageRole::SUPPORT, 'name' => 'Suporte', 'position' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('tasks')->insertGetId([
            'project_id' => $project->id, 'number' => 1, 'board_stage_id' => $stage,
            'task_type_id' => TaskType::query()->where('slug', TaskType::SUPPORT)->value('id'),
            'task_priority_id' => TaskPriority::NORMAL, 'title' => 'Pedido do cliente',
            'requester_contact_id' => $requester->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function semearProjetos(): void
    {
        $this->seed([StageRoleSeeder::class, TaskPrioritySeeder::class, TaskTypeSeeder::class, ReworkReasonSeeder::class]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function projeto(array $attributes = []): Project
    {
        return Project::factory()->withBoard()->create($attributes);
    }

    protected function alocar(Project $project, User ...$users): void
    {
        foreach ($users as $user) {
            $project->members()->create(['user_id' => $user->id]);
        }
    }

    protected function etapa(Project $project, int $role): BoardStage
    {
        return $project->boardStages()->where('stage_role_id', $role)->whereNull('archived_at')->firstOrFail();
    }

    /**
     * Sprint gravada direto (B5): aberta por padrão, de 06/10 a 19/10/2026 (10 dias úteis).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function sprint(Project $project, array $attributes = []): Sprint
    {
        return $project->sprints()->create([
            'name' => 'Sprint '.($project->sprints()->count() + 1),
            'starts_on' => '2026-10-06',
            'ends_on' => '2026-10-19',
            ...$attributes,
        ]);
    }

    /**
     * O movimento que leva a tarefa a uma etapa, no instante dado (UTC), como o B3 vai gravar: é
     * a fonte da 1ª entrada em desenvolvimento da data prevista (P28).
     */
    protected function moverEm(Task $task, BoardStage $to, string $utc, ?User $by = null): void
    {
        TaskStageMovement::query()->forceCreate([
            'task_id' => $task->id,
            'moved_by' => ($by ?? User::factory()->create())->id,
            'from_board_stage_id' => $task->board_stage_id,
            'to_board_stage_id' => $to->id,
            'created_at' => $utc,
        ]);
        $task->forceFill(['board_stage_id' => $to->id])->save();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function tarefa(Project $project, ?BoardStage $stage = null, array $attributes = []): Task
    {
        $project->increment('last_task_number');

        return Task::query()->create([
            'project_id' => $project->id,
            'number' => $project->last_task_number,
            'board_stage_id' => ($stage ?? $this->etapa($project, StageRole::QUEUE))->id,
            'task_type_id' => TaskType::query()->where('slug', TaskType::IMPROVEMENT)->value('id'),
            'task_priority_id' => TaskPriority::NORMAL,
            'title' => 'Tarefa '.$project->last_task_number,
            ...$attributes,
        ]);
    }
}
