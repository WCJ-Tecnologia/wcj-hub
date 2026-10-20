<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Support\DiasUteis;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/** A marca de atraso do card (P53, R16), no quadro e na ficha: preenche o atributo `late`. */
class LateQuery
{
    /**
     * Atraso (P53, R16): passou da data prevista sem chegar ao pronto, descontados os dias úteis
     * em pausa (cada pausa conta os dias úteis inteiros entre entrar e sair; a conta fina dos
     * indicadores é do F7). Uma consulta para todos os cards com data prevista.
     *
     * @param  Collection<int, Task>  $cards
     */
    public function fill(Collection $cards): void
    {
        $today = DiasUteis::today();
        $candidates = $cards->filter(fn (Task $card) => $card->due_on !== null
            && $card->boardStage->stage_role_id !== StageRole::DONE
            && $card->due_on->toDateString() < $today);

        $movements = $candidates->isEmpty() ? collect() : TaskStageMovement::query()
            ->join('board_stages', 'board_stages.id', '=', 'task_stage_movements.to_board_stage_id')
            ->whereIn('task_stage_movements.task_id', $candidates->pluck('id'))
            ->orderBy('task_stage_movements.created_at')
            ->orderBy('task_stage_movements.id')
            ->get(['task_stage_movements.task_id', 'task_stage_movements.created_at', 'board_stages.stage_role_id'])
            ->groupBy('task_id');

        $candidateIds = $candidates->pluck('id')->flip();
        $cards->each(function (Task $card) use ($candidateIds, $movements, $today) {
            if (! $candidateIds->has($card->id)) {
                $card->setAttribute('late', false);

                return;
            }
            $paused = $this->pausedBusinessDays($movements->get($card->id) ?? collect(), $today);
            $limit = $paused === 0 ? $card->due_on->toDateString() : DiasUteis::add(DiasUteis::next($card->due_on->toDateString()), $paused);
            $card->setAttribute('late', $today > $limit);
        });
    }

    /** @param  BaseCollection<int, TaskStageMovement>  $movements */
    private function pausedBusinessDays(BaseCollection $movements, string $today): int
    {
        $days = 0;
        $since = null;
        foreach ($movements as $movement) {
            $day = LocalTime::format(CarbonImmutable::parse($movement->created_at, 'UTC'), 'Y-m-d');
            if ($since !== null) {
                $days += max(0, DiasUteis::between($since, $day) - 1);
                $since = null;
            }
            if ((int) $movement->stage_role_id === StageRole::PAUSED) {
                $since = $day;
            }
        }

        return $since === null ? $days : $days + max(0, DiasUteis::between($since, $today) - 1);
    }
}
