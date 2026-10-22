<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;

/**
 * "Nunca usado" do card (P26; docs/banco.md, tasks, "tirar do quadro"): só o card nunca usado se
 * exclui de verdade; o resto se arquiva. Um lugar só para a ficha (esconder o *Excluir*) e para o
 * TaskArchiveService (recusar, com a linha travada).
 */
class TaskUsageQuery
{
    /**
     * As FKs para `tasks` que saem junto com o card nunca usado (cascade): não são uso. O movimento
     * conta à parte: o da criação sai junto, o segundo já é uso. Toda FK nova para `tasks` entra
     * aqui ou em USES, e o TaskUsageQueryTest falha até alguém decidir (B4 e B6 vão criar).
     */
    public const GOES_ALONG = [
        'task_acceptance_criteria.task_id',
        'task_participants.task_id',
        'task_stage_movements.task_id',
    ];

    /** As FKs para `tasks` cuja linha faz do card um card usado (banco.md), pela relação do Task. */
    public const USES = [
        'time_entries.task_id' => 'timeEntries',
        'task_comments.task_id' => 'comments',
        'task_attachments.task_id' => 'attachments',
        'task_pull_requests.task_id' => 'pullRequests',
        'tasks.origin_task_id' => 'productionBugs',
        'tasks.parent_task_id' => 'children',
    ];

    /**
     * Uma consulta só. Fora de sprint também é condição: o card comum que entra na sprint já se
     * moveu, mas o de suporte entra sem trocar de coluna (P52), e P26 diz "não saiu do Backlog".
     */
    public function isUnused(Task $task): bool
    {
        if ($task->cross_tested_at !== null || $task->sprint_id !== null) {
            return false;
        }

        $row = Task::query()
            ->whereKey($task->id)
            ->withCount('movements')
            ->withExists(array_values(self::USES))
            ->firstOrFail();

        if ($row->movements_count > 1) {
            return false;
        }

        foreach (self::USES as $relation) {
            if ($row->getAttribute(str($relation)->snake()->append('_exists')->toString())) {
                return false;
            }
        }

        return true;
    }
}
