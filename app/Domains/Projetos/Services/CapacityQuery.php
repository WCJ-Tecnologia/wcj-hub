<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Support\DiasUteis;

/**
 * Painel *Capacidade* da sprint (P48, P49, R9; F5 §03 "Como o painel conta"). Só referência: nada
 * no sistema lê este número para recusar (P49). Entra todo dev que é dev de alguma tarefa da
 * sprint; o QA não entra. Faltam do dev = soma, nas tarefas dele, de previstas − todas as horas
 * lançadas na tarefa (por qualquer pessoa), cada tarefa nunca abaixo de 0; a tarefa já em etapa de
 * papel done conta 0 (F5 §10.10); a arquivada não conta (P26). Capacidade = carga diária × dias
 * úteis da sprint inteira. Uma consulta só para as tarefas, com a soma das horas.
 */
class CapacityQuery
{
    /**
     * @return array{
     *     business_days: int,
     *     developers: list<array{id: int, name: string, daily_hours: ?float, capacity: ?float, remaining: float, difference: ?float}>,
     *     without_developer: array{tasks: int, hours: float},
     *     without_hours: array{tasks: int},
     * }
     */
    public function forSprint(Sprint $sprint): array
    {
        $businessDays = DiasUteis::between($sprint->starts_on->toDateString(), $sprint->ends_on->toDateString());

        $tasks = Task::query()
            ->where('sprint_id', $sprint->id)
            ->whereNull('archived_at')
            ->with(['developer:id,name,daily_hours', 'boardStage:id,stage_role_id'])
            ->withSum('timeEntries as logged_hours', 'hours')
            ->get(['id', 'developer_id', 'board_stage_id', 'estimated_hours']);

        $developers = [];
        $withoutDeveloper = ['tasks' => 0, 'hours' => 0.0];
        $withoutHours = ['tasks' => 0];

        foreach ($tasks as $task) {
            $estimated = $task->estimated_hours === null ? null : (float) $task->estimated_hours;
            if ($estimated === null) {
                $withoutHours['tasks']++;
            }

            if ($task->developer === null) {
                $withoutDeveloper['tasks']++;
                $withoutDeveloper['hours'] += $estimated ?? 0.0;

                continue;
            }

            $dev = $task->developer;
            $developers[$dev->id] ??= ['id' => $dev->id, 'name' => $dev->name, 'daily_hours' => $dev->daily_hours === null ? null : (float) $dev->daily_hours, 'remaining' => 0.0];
            $developers[$dev->id]['remaining'] += $this->remaining($task, $estimated);
        }

        $rows = array_map(function (array $row) use ($businessDays) {
            $capacity = $row['daily_hours'] === null ? null : round($row['daily_hours'] * $businessDays, 2);
            $remaining = round($row['remaining'], 2);

            return [...$row, 'capacity' => $capacity, 'remaining' => $remaining, 'difference' => $capacity === null ? null : round($capacity - $remaining, 2)];
        }, array_values($developers));

        usort($rows, fn (array $a, array $b) => [$b['remaining'], $a['name']] <=> [$a['remaining'], $b['name']]);

        return [
            'business_days' => $businessDays,
            'developers' => $rows,
            'without_developer' => ['tasks' => $withoutDeveloper['tasks'], 'hours' => round($withoutDeveloper['hours'], 2)],
            'without_hours' => $withoutHours,
        ];
    }

    private function remaining(Task $task, ?float $estimated): float
    {
        if ($estimated === null || $task->boardStage->stage_role_id === StageRole::DONE) {
            return 0.0;
        }

        return max(0.0, $estimated - (float) $task->getAttribute('logged_hours'));
    }
}
