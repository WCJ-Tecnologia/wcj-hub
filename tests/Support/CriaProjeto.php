<?php

namespace Tests\Support;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Models\TaskType;
use App\Platform\Models\User;
use Database\Seeders\ReworkReasonSeeder;
use Database\Seeders\StageRoleSeeder;
use Database\Seeders\TaskPrioritySeeder;
use Database\Seeders\TaskTypeSeeder;

/**
 * Montagem dos testes de Projetos: domínios semeados, projeto com o quadro padrão, alocação e
 * tarefa. A tarefa é gravada direto (o card é do B3), com o número pelo contador do projeto.
 */
trait CriaProjeto
{
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
