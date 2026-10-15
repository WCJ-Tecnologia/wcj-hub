<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Services\CapacityQuery;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/** Painel *Capacidade* (P48, P49, R9; F5 §03 "Como o painel conta", §10.10, §10.11). */
class CapacityQueryTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    private function lancar(Task $task, User $user, float $hours): void
    {
        $task->timeEntries()->create(['user_id' => $user->id, 'worked_on' => '2026-10-07', 'hours' => $hours]);
    }

    public function test_counts_remaining_hours_per_developer_against_daily_load_times_business_days(): void
    {
        $this->semearProjetos();
        $project = $this->projeto();
        // 06/10 a 19/10/2026: 10 dias úteis.
        $sprint = $this->sprint($project);
        $ana = User::factory()->developer()->create(['name' => 'Ana', 'daily_hours' => 8]);
        $bruno = User::factory()->developer()->create(['name' => 'Bruno', 'daily_hours' => 4]);
        $semCarga = User::factory()->developer()->create(['name' => 'Caio', 'daily_hours' => null]);
        $qa = User::factory()->developer()->create(['name' => 'Quésia']);
        $outro = User::factory()->developer()->create();
        $todo = $this->etapa($project, StageRole::SPRINT);
        $na = fn (array $attributes, ?int $role = null) => $this->tarefa($project, $role === null ? $todo : $this->etapa($project, $role), ['sprint_id' => $sprint->id, ...$attributes]);

        // Ana: 16 previstas − 4 lançadas por outra pessoa = 12; 5 − 9 lançadas = 0 (nunca abaixo de 0);
        // 10 em Done = 0 (F5 §10.10); arquivada não conta (P26).
        $this->lancar($na(['developer_id' => $ana->id, 'qa_id' => $qa->id, 'estimated_hours' => 16]), $outro, 4);
        $this->lancar($na(['developer_id' => $ana->id, 'estimated_hours' => 5]), $ana, 9);
        $na(['developer_id' => $ana->id, 'estimated_hours' => 10], StageRole::DONE);
        $na(['developer_id' => $ana->id, 'estimated_hours' => 40, 'archived_at' => now(), 'archive_reason' => 'x']);
        // Bruno: 50 previstas, acima da capacidade de 40: só aviso.
        $na(['developer_id' => $bruno->id, 'estimated_hours' => 50]);
        // Caio sem carga: capacidade não calculada (N3), com tarefa sem horas.
        $na(['developer_id' => $semCarga->id]);
        // Sem dev: 2 tarefas, 6 h previstas; uma delas também sem horas.
        $na(['estimated_hours' => 6]);
        $na([]);
        // Tarefa de outra sprint não entra.
        $this->tarefa($project, $todo, ['developer_id' => $ana->id, 'estimated_hours' => 99, 'sprint_id' => $this->sprint($project, ['name' => 'Outra', 'closed_at' => now()])->id]);

        $panel = app(CapacityQuery::class)->forSprint($sprint);

        $this->assertSame(10, $panel['business_days']);
        $this->assertSame([
            ['id' => $bruno->id, 'name' => 'Bruno', 'daily_hours' => 4.0, 'remaining' => 50.0, 'capacity' => 40.0, 'difference' => -10.0],
            ['id' => $ana->id, 'name' => 'Ana', 'daily_hours' => 8.0, 'remaining' => 12.0, 'capacity' => 80.0, 'difference' => 68.0],
            ['id' => $semCarga->id, 'name' => 'Caio', 'daily_hours' => null, 'remaining' => 0.0, 'capacity' => null, 'difference' => null],
        ], $panel['developers']);
        // O QA não entra (P49).
        $this->assertNotContains($qa->id, array_column($panel['developers'], 'id'));
        $this->assertSame(['tasks' => 2, 'hours' => 6.0], $panel['without_developer']);
        $this->assertSame(['tasks' => 2], $panel['without_hours']);
    }

    public function test_empty_sprint_has_no_developers(): void
    {
        $this->semearProjetos();
        $panel = app(CapacityQuery::class)->forSprint($this->sprint($this->projeto(), ['starts_on' => '2026-10-10', 'ends_on' => '2026-10-11']));

        $this->assertSame(0, $panel['business_days']);
        $this->assertSame([], $panel['developers']);
    }
}
