<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lê `task_stage_movements` de um lote de cards numa consulta só e entrega a cada card o seu
 * TaskTimes (F7 §06; P11, P14, P53). A conta é do StageTime; aqui só se lê e se agrupa.
 */
class StageTimeQuery
{
    /**
     * @param  list<int>  $taskIds
     * @return array<int, TaskTimes> id do card → tempos (todo card tem ao menos a criação)
     */
    public function forTasks(array $taskIds, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        if ($taskIds === []) {
            return [];
        }

        $rows = TaskStageMovement::query()
            ->join('board_stages', 'board_stages.id', '=', 'task_stage_movements.to_board_stage_id')
            ->whereIn('task_stage_movements.task_id', $taskIds)
            ->orderBy('task_stage_movements.created_at')
            ->orderBy('task_stage_movements.id')
            ->get(['task_stage_movements.task_id', 'task_stage_movements.created_at', 'board_stages.id as stage_id', 'board_stages.stage_role_id']);

        $arrivals = [];
        foreach ($rows as $row) {
            $arrivals[$row->task_id][] = [
                'stage_id' => (int) $row->stage_id,
                'role_id' => (int) $row->stage_role_id,
                'at' => CarbonImmutable::parse($row->created_at, 'UTC'),
            ];
        }

        $times = [];
        foreach ($taskIds as $id) {
            $times[$id] = new TaskTimes(StageTime::intervals($arrivals[$id] ?? [], $now), $now);
        }

        return $times;
    }

    /**
     * Os movimentos de um card com tudo que a linha do tempo mostra (F7 §02): quem, o canal, as
     * etapas (inclusive as removidas, pelo nome que tinham), a origem e o motivo do retrabalho.
     *
     * @return Collection<int, TaskStageMovement>
     */
    public function movements(Task $task): Collection
    {
        return $task->movements()
            ->with([
                'movedBy:id,name,deactivated_at',
                'aiAuthorization.aiAssistant:id,label',
                'fromBoardStage:id,name,stage_role_id,archived_at', 'fromBoardStage.stageRole:id,slug',
                'toBoardStage:id,name,stage_role_id,archived_at', 'toBoardStage.stageRole:id,slug',
                'reworkOrigin:id,label',
                'reworkReason:id,label',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
