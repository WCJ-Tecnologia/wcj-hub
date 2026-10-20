<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TimeEntry;

/**
 * O realizado (P30, R17; F7 §06): as horas lançadas em `time_entries`, somadas por card e por
 * pessoa numa consulta só, e, ao lado, o tempo em desenvolvimento estimado pelo quadro: dias
 * úteis em etapa de papel desenvolvimento × carga diária do dev atual (F7 §10.3). Os dois nunca
 * somam.
 */
class EffortQuery
{
    /**
     * @param  list<int>  $taskIds
     * @return array<int, array<int, float>> id do card → (id da pessoa → horas)
     */
    public function loggedByTaskAndUser(array $taskIds): array
    {
        if ($taskIds === []) {
            return [];
        }

        $rows = TimeEntry::query()
            ->whereIn('task_id', $taskIds)
            ->groupBy('task_id', 'user_id')
            ->selectRaw('task_id, user_id, SUM(hours) as hours')
            ->get();

        $logged = [];
        foreach ($rows as $row) {
            $logged[(int) $row->task_id][(int) $row->user_id] = (float) $row->hours;
        }

        return $logged;
    }

    /**
     * @param  array<int, float>  $byUser  as horas de um card por pessoa
     */
    public static function total(array $byUser): float
    {
        return round(array_sum($byUser), 2);
    }

    /** Tempo em desenvolvimento em horas (R17); nulo sem dev ou sem carga diária. O card precisa vir com `developer`. */
    public function developmentHours(Task $task, TaskTimes $times): ?float
    {
        $daily = $task->developer?->daily_hours;
        if ($daily === null) {
            return null;
        }

        return round($times->daysIn(StageRole::DEVELOPMENT) * (float) $daily, 2);
    }
}
