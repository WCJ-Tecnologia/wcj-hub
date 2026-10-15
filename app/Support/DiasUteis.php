<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Dias úteis de segunda a sexta, sem feriado (P29), sobre datas de negócio `AAAA-MM-DD`. É o
 * espelho do `resources/js/lib/dias-uteis.ts`: a tela propõe com aquele, o servidor grava com
 * este, e as duas contas são as mesmas (os testes usam os exemplos do F5).
 */
final class DiasUteis
{
    /**
     * O último dos `$days` dias úteis contando a partir de `$start`, inclusive (P28, P46). Início
     * num dia útil conta como o 1º; no sábado ou no domingo, começa a contar na segunda. Ex.:
     * terça 2026-10-06 com 10 dias → segunda 2026-10-19. Menos de 1 dia devolve o próprio início.
     */
    public static function add(string $start, int $days): string
    {
        $current = CarbonImmutable::parse($start);
        if ($days < 1) {
            return $current->toDateString();
        }

        $counted = $current->isWeekday() ? 1 : 0;
        while ($counted < $days) {
            $current = $current->addDay();
            if ($current->isWeekday()) {
                $counted++;
            }
        }

        return $current->toDateString();
    }

    /** Dias de segunda a sexta entre `$start` e `$end`, os dois inclusive (P48). Fim antes do início: 0. */
    public static function between(string $start, string $end): int
    {
        $from = CarbonImmutable::parse($start);
        $to = CarbonImmutable::parse($end);
        if ($to->lt($from)) {
            return 0;
        }

        $count = 0;
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            if ($day->isWeekday()) {
                $count++;
            }
        }

        return $count;
    }

    /** O 1º dia útil depois de `$date` (P50: o início da próxima sprint). */
    public static function next(string $date): string
    {
        return self::add(CarbonImmutable::parse($date)->addDay()->toDateString(), 1);
    }

    /** Hoje, no fuso de quem usa (data de negócio, banco.md §0). */
    public static function today(): string
    {
        return CarbonImmutable::now(LocalTime::zone())->toDateString();
    }
}
