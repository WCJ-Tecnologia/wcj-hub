<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\StageRole;
use Carbon\CarbonImmutable;

/**
 * O que os movimentos de um card dizem sobre tempo (F7 §06), já calculado pelo StageTime a partir
 * do StageTimeQuery: os intervalos por etapa, o total por papel, a 1ª entrada em desenvolvimento,
 * a pausa que o atraso desconta e a última chegada ao pronto. Só leitura.
 */
final class TaskTimes
{
    /**
     * @param  list<array{stage_id: int, role_id: int, from: CarbonImmutable, to: CarbonImmutable, open: bool, days: float}>  $intervals
     */
    public function __construct(
        public readonly array $intervals,
        public readonly CarbonImmutable $now,
    ) {}

    /** @return array<int, float> papel → dias úteis (sem o pronto) */
    public function byRole(): array
    {
        return StageTime::byRole($this->intervals);
    }

    public function daysIn(int $role): float
    {
        return $this->byRole()[$role] ?? 0.0;
    }

    /** A 1ª chegada a uma etapa de papel desenvolvimento (P28), ou nulo. */
    public function firstDevelopmentAt(): ?CarbonImmutable
    {
        foreach ($this->intervals as $interval) {
            if ($interval['role_id'] === StageRole::DEVELOPMENT) {
                return $interval['from'];
            }
        }

        return null;
    }

    /** Dias úteis em pausa desde a 1ª entrada em desenvolvimento: o que o atraso desconta (R16). */
    public function pausedSinceDevelopment(): float
    {
        return StageTime::pausedSince($this->intervals, $this->firstDevelopmentAt());
    }

    /** A última chegada a uma etapa de papel pronto (F7 §02 "Concluída em"), ou nulo. */
    public function doneAt(): ?CarbonImmutable
    {
        $at = null;
        foreach ($this->intervals as $interval) {
            if ($interval['role_id'] === StageRole::DONE) {
                $at = $interval['from'];
            }
        }

        return $at;
    }

    /** O intervalo aberto: a etapa atual e desde quando. */
    public function current(): ?array
    {
        $last = $this->intervals === [] ? null : $this->intervals[array_key_last($this->intervals)];

        return $last !== null && $last['open'] ? $last : null;
    }

    /** O card saiu de onde nasceu (Backlog ou Support) ao menos uma vez (F7 §03 "Tempo por etapa"). */
    public function leftOrigin(): bool
    {
        return count($this->intervals) > 1;
    }
}
