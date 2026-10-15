<?php

namespace Database\Factories;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Services\BoardStageService;
use App\Domains\Projetos\Services\TaskTypeService;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Projeto sem quadro. Com o quadro padrão e os 5 tipos padrão: ->withBoard(), que usa os mesmos
 * Services da tela, e pede os papéis de etapa semeados (StageRoleSeeder).
 *
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'manager_id' => User::factory()->projectManager(),
            'key_prefix' => fake()->unique()->regexify('[A-Z]{3}[0-9]{2}'),
            'name' => fake()->unique()->catchPhrase(),
            'starts_on' => now('America/Sao_Paulo')->toDateString(),
            'due_on' => null,
        ];
    }

    public function withBoard(): static
    {
        return $this->afterCreating(function (Project $project) {
            app(BoardStageService::class)->createDefaultBoard($project);
            app(TaskTypeService::class)->createDefaults($project);
        });
    }

    public function finished(): static
    {
        return $this->state(fn () => ['finished_at' => now()]);
    }
}
