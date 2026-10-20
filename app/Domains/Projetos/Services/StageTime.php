<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\StageRole;
use App\Support\DiasUteis;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;

/**
 * As contas dos indicadores (F7 §06 "Como as contas são feitas"), puras: recebem os movimentos
 * do card e devolvem números. Nada aqui lê o banco; quem lê é o StageTimeQuery.
 *
 * Um "dia útil" é um dia de segunda a sexta no fuso de quem usa (P29), inteiro ou fração: da
 * segunda às 9h à terça às 21h são 1,5 dia útil (F7 §06, §10.1). Sábado e domingo não contam.
 */
final class StageTime
{
    private const DAY_SECONDS = 86400;

    /**
     * Dias úteis, com fração, entre dois instantes (UTC): só o tempo que cai de segunda a sexta,
     * no fuso de quem usa. Fim antes do início: 0.
     */
    public static function businessDays(CarbonImmutable $from, CarbonImmutable $to): float
    {
        $zone = LocalTime::zone();
        $from = $from->setTimezone($zone);
        $to = $to->setTimezone($zone);
        if ($to->lte($from)) {
            return 0.0;
        }

        $total = 0.0;
        for ($day = $from->startOfDay(); $day->lt($to); $day = $day->addDay()) {
            if (! $day->isWeekday()) {
                continue;
            }
            $start = $from->greaterThan($day) ? $from : $day;
            $end = $to->lessThan($day->addDay()) ? $to : $day->addDay();
            $total += $start->diffInSeconds($end, true) / self::DAY_SECONDS;
        }

        return $total;
    }

    /**
     * Os intervalos do card, um por movimento: da chegada à etapa até o movimento seguinte (ou até
     * `$now`, na etapa atual). Cada movimento é `[stage_id, role_id, at]` em ordem cronológica.
     *
     * @param  list<array{stage_id: int, role_id: int, at: CarbonImmutable}>  $arrivals
     * @return list<array{stage_id: int, role_id: int, from: CarbonImmutable, to: CarbonImmutable, open: bool, days: float}>
     */
    public static function intervals(array $arrivals, CarbonImmutable $now): array
    {
        $intervals = [];
        $count = count($arrivals);
        foreach ($arrivals as $index => $arrival) {
            $open = $index === $count - 1;
            $to = $open ? $now : $arrivals[$index + 1]['at'];
            $intervals[] = [
                'stage_id' => $arrival['stage_id'],
                'role_id' => $arrival['role_id'],
                'from' => $arrival['at'],
                'to' => $to,
                'open' => $open,
                'days' => self::businessDays($arrival['at'], $to),
            ];
        }

        return $intervals;
    }

    /**
     * Dias úteis somados por papel de etapa (P11): renomear a etapa não muda a conta, e duas
     * etapas com o mesmo papel somam juntas. O papel pronto fica fora, porque não tem fim.
     *
     * @param  list<array{role_id: int, days: float}>  $intervals
     * @return array<int, float> papel → dias
     */
    public static function byRole(array $intervals): array
    {
        $totals = [];
        foreach ($intervals as $interval) {
            if ($interval['role_id'] === StageRole::DONE) {
                continue;
            }
            $totals[$interval['role_id']] = ($totals[$interval['role_id']] ?? 0.0) + $interval['days'];
        }

        return $totals;
    }

    /**
     * Dias úteis em etapa de pausa a partir de um instante (a 1ª entrada em desenvolvimento, R16);
     * sem o instante, nada é descontado.
     *
     * @param  list<array{role_id: int, from: CarbonImmutable, to: CarbonImmutable}>  $intervals
     */
    public static function pausedSince(array $intervals, ?CarbonImmutable $since): float
    {
        if ($since === null) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($intervals as $interval) {
            if ($interval['role_id'] !== StageRole::PAUSED) {
                continue;
            }
            $from = $interval['from']->greaterThan($since) ? $interval['from'] : $since;
            $total += self::businessDays($from, $interval['to']);
        }

        return $total;
    }

    /**
     * Atraso (P53, R16): dias úteis passados depois da data prevista, contando hoje, menos os dias
     * úteis em pausa desde a 1ª entrada em desenvolvimento, nunca abaixo de zero: positivo =
     * atrasada; zero = no prazo. Nulo sem data prevista. Em pausa, o atraso não cresce.
     */
    public static function delay(?string $dueOn, string $today, float $pausedDays): ?float
    {
        if ($dueOn === null) {
            return null;
        }

        return max(0.0, self::round(DiasUteis::between(DiasUteis::next($dueOn), $today) - $pausedDays));
    }

    /** Uma casa decimal (F7 §10.1). */
    public static function round(float $days): float
    {
        return round($days, 1);
    }
}
