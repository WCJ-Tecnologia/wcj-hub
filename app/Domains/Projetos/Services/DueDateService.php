<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Platform\Models\User;
use App\Support\DiasUteis;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;

/**
 * Data prevista da tarefa (P28, P29, N3; docs/banco.md, tasks.due_on): horas previstas ÷ carga
 * diária do dev, arredondado para cima, em dias úteis de segunda a sexta, contando o dia da 1ª
 * entrada em etapa de papel development como o 1º (F5 §10.9). A data é uma foto: só se refaz ao
 * reestimar, nunca pela troca de dev, pela carga (N3), pela pausa nem pelo retrabalho.
 *
 * Quem grava a tarefa chama apply() e salva (o B3 chama na 1ª entrada em desenvolvimento e ao
 * definir o dev; a mudança de horas previstas passa pelo TaskHoursService).
 */
class DueDateService
{
    /**
     * Preenche tasks.due_on sem salvar. Sem $reestimate, só preenche a data que ainda falta
     * (a tarefa passou a ter dev e horas depois de entrar em Doing, P28); com ele, refaz a
     * conta com as horas novas a partir da mesma 1ª entrada, inclusive para nulo.
     */
    public function apply(Task $task, bool $reestimate = false): void
    {
        if ($task->due_on !== null && ! $reestimate) {
            return;
        }

        $task->due_on = $this->calculate($task);
    }

    /** A conta da P28 com o estado atual da tarefa, `AAAA-MM-DD`; nula sem entrada em Doing, sem dev, sem carga ou sem horas. */
    public function calculate(Task $task): ?string
    {
        $start = $this->firstDevelopmentDay($task);
        $daily = $task->developer_id === null ? null : User::query()->whereKey($task->developer_id)->value('daily_hours');

        if ($start === null || $daily === null || $task->estimated_hours === null) {
            return null;
        }

        return DiasUteis::add($start, $this->days((string) $task->estimated_hours, (string) $daily));
    }

    /**
     * O que falta para a data prevista existir (F7 §02, §07): o dev, as horas previstas ou a carga
     * diária do dev; vazio quando não falta nada (a data então depende só da entrada em
     * desenvolvimento). Lê `developer` já carregado.
     *
     * @return list<'developer'|'hours'|'daily_hours'>
     */
    public function missing(Task $task): array
    {
        $missing = [];
        if ($task->developer === null) {
            $missing[] = 'developer';
        } elseif ($task->developer->daily_hours === null) {
            $missing[] = 'daily_hours';
        }
        if ($task->estimated_hours === null) {
            $missing[] = 'hours';
        }

        return $missing;
    }

    /** O dia (no fuso de quem usa) da 1ª entrada da tarefa em etapa de papel development, ou nulo. */
    public function firstDevelopmentDay(Task $task): ?string
    {
        if (! $task->exists) {
            return null;
        }

        $first = TaskStageMovement::query()
            ->where('task_id', $task->id)
            ->whereHas('toBoardStage', fn ($stage) => $stage->where('stage_role_id', StageRole::DEVELOPMENT))
            ->min('created_at');

        return $first === null ? null : LocalTime::format(CarbonImmutable::parse($first, 'UTC'), 'Y-m-d');
    }

    /** Dias úteis = horas ÷ carga, com a fração arredondada para cima; em centésimos, sem erro de ponto flutuante. */
    private function days(string $hours, string $daily): int
    {
        $hoursCents = (int) round(((float) $hours) * 100);
        $dailyCents = (int) round(((float) $daily) * 100);

        return intdiv($hoursCents + $dailyCents - 1, $dailyCents);
    }
}
