<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;

/**
 * Atraso de um card nos indicadores (P53, R16; F7 §06): hoje − data prevista em dias úteis,
 * descontada a pausa desde a 1ª entrada em desenvolvimento. Não existe para o card pronto nem
 * para o arquivado (P26, F7 §10.6). O card precisa vir com `boardStage.stage_role_id`.
 */
class DelayQuery
{
    /** Dias úteis de atraso (0 = no prazo); nulo sem data prevista, pronto ou arquivado. */
    public function delay(Task $task, TaskTimes $times, string $today): ?float
    {
        if ($task->due_on === null || $task->archived_at !== null || $task->boardStage->stage_role_id === StageRole::DONE) {
            return null;
        }

        return StageTime::delay($task->due_on->toDateString(), $today, $times->pausedSinceDevelopment());
    }

    public function isLate(Task $task, TaskTimes $times, string $today): bool
    {
        return ($this->delay($task, $times, $today) ?? 0.0) > 0;
    }
}
