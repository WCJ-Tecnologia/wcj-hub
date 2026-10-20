<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\StageRole;
use Illuminate\Support\Facades\DB;

/**
 * Progresso do card pai (P57; F3 §03 e §06): quantos filhos estão a fazer, em andamento e
 * concluídos, pelo papel da etapa de cada filho. Filho arquivado não conta. Nunca gravado: uma
 * consulta só para todos os pais da tela (escopo B3, benchmark do quadro).
 */
class ChildProgressQuery
{
    /** Fila, sprint e suporte: ainda não começou. */
    private const TODO = [StageRole::QUEUE, StageRole::SPRINT, StageRole::SUPPORT];

    /**
     * @param  list<int>  $taskIds  os cards da tela; só os que têm filhos voltam
     * @return array<int, array{todo: int, doing: int, done: int}>
     */
    public function forParents(array $taskIds): array
    {
        if ($taskIds === []) {
            return [];
        }

        $rows = DB::table('tasks')
            ->join('board_stages', 'board_stages.id', '=', 'tasks.board_stage_id')
            ->whereIn('tasks.parent_task_id', $taskIds)
            ->whereNull('tasks.archived_at')
            ->groupBy('tasks.parent_task_id', 'board_stages.stage_role_id')
            ->get(['tasks.parent_task_id as parent', 'board_stages.stage_role_id as role', DB::raw('count(*) as total')]);

        $progress = [];
        foreach ($rows as $row) {
            $bucket = match (true) {
                (int) $row->role === StageRole::DONE => 'done',
                in_array((int) $row->role, self::TODO, true) => 'todo',
                default => 'doing',
            };
            $progress[$row->parent] ??= ['todo' => 0, 'doing' => 0, 'done' => 0];
            $progress[$row->parent][$bucket] += (int) $row->total;
        }

        return $progress;
    }
}
