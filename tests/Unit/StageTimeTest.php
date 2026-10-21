<?php

namespace Tests\Unit;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Services\StageTime;
use App\Domains\Projetos\Services\TaskTimes;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * As contas puras dos indicadores (F7 §06 "Como as contas são feitas", §10.1; P53, R16, P11). Sem o
 * app e sem banco (a suíte Unit roda antes das migrações no CI): o fuso vai explícito.
 */
class StageTimeTest extends TestCase
{
    private const ZONE = 'America/Sao_Paulo';

    private function at(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, self::ZONE)->utc();
    }

    /** @return array<string, array{string, string, float}> */
    public static function diasUteis(): array
    {
        return [
            // F7 §06: da segunda às 9h à terça às 21h são 1,5 dia útil.
            'um dia e meio' => ['2026-10-05 09:00', '2026-10-06 21:00', 1.5],
            'mesmo instante' => ['2026-10-05 09:00', '2026-10-05 09:00', 0.0],
            'fim antes do início' => ['2026-10-06 09:00', '2026-10-05 09:00', 0.0],
            'sexta ao meio-dia até segunda ao meio-dia pula o fim de semana' => ['2026-10-09 12:00', '2026-10-12 12:00', 1.0],
            'só fim de semana' => ['2026-10-10 08:00', '2026-10-11 20:00', 0.0],
            'o feriado de 12/10 conta como dia útil (P29)' => ['2026-10-12 00:00', '2026-10-13 00:00', 1.0],
            'uma semana inteira' => ['2026-10-05 00:00', '2026-10-12 00:00', 5.0],
            'seis horas' => ['2026-10-07 10:00', '2026-10-07 16:00', 0.25],
        ];
    }

    #[DataProvider('diasUteis')]
    public function test_business_days_count_only_monday_to_friday_in_the_local_zone(string $from, string $to, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, StageTime::businessDays($this->at($from), $this->at($to), self::ZONE), 0.0001);
    }

    public function test_intervals_run_from_each_arrival_to_the_next_and_the_last_until_now(): void
    {
        $arrivals = [
            ['stage_id' => 1, 'role_id' => StageRole::QUEUE, 'at' => $this->at('2026-10-05 09:00')],
            ['stage_id' => 3, 'role_id' => StageRole::DEVELOPMENT, 'at' => $this->at('2026-10-06 09:00')],
            ['stage_id' => 7, 'role_id' => StageRole::PAUSED, 'at' => $this->at('2026-10-07 09:00')],
        ];

        $intervals = StageTime::intervals($arrivals, $this->at('2026-10-08 21:00'), self::ZONE);

        $this->assertCount(3, $intervals);
        $this->assertSame([1.0, 1.0, 1.5], array_map(fn (array $i) => round($i['days'], 4), $intervals));
        $this->assertSame([false, false, true], array_column($intervals, 'open'));
        $this->assertTrue($intervals[0]['to']->equalTo($arrivals[1]['at']));
    }

    public function test_time_adds_up_by_stage_role_and_leaves_done_out(): void
    {
        $intervals = [
            ['role_id' => StageRole::DEVELOPMENT, 'days' => 1.0],
            ['role_id' => StageRole::CODE_REVIEW, 'days' => 0.5],
            // Duas etapas de desenvolvimento somam juntas (P11).
            ['role_id' => StageRole::DEVELOPMENT, 'days' => 2.0],
            ['role_id' => StageRole::DONE, 'days' => 40.0],
        ];

        $this->assertSame([StageRole::DEVELOPMENT => 3.0, StageRole::CODE_REVIEW => 0.5], StageTime::byRole($intervals));
    }

    public function test_pause_before_development_is_not_discounted(): void
    {
        $intervals = [
            ['role_id' => StageRole::PAUSED, 'from' => $this->at('2026-10-05 09:00'), 'to' => $this->at('2026-10-06 09:00')],
            ['role_id' => StageRole::DEVELOPMENT, 'from' => $this->at('2026-10-06 09:00'), 'to' => $this->at('2026-10-07 09:00')],
            // Esta pausa começa antes do marco: só conta a parte depois dele.
            ['role_id' => StageRole::PAUSED, 'from' => $this->at('2026-10-07 09:00'), 'to' => $this->at('2026-10-08 21:00')],
        ];

        $this->assertEqualsWithDelta(0.0, StageTime::pausedSince($intervals, null, self::ZONE), 0.0001);
        $this->assertEqualsWithDelta(1.5, StageTime::pausedSince($intervals, $this->at('2026-10-06 09:00'), self::ZONE), 0.0001);
        $this->assertEqualsWithDelta(0.5, StageTime::pausedSince($intervals, $this->at('2026-10-08 09:00'), self::ZONE), 0.0001);
    }

    /** @return array<string, array{?string, string, float, ?float}> */
    public static function atrasos(): array
    {
        return [
            // F7 §06: prevista para sexta 09/10, hoje quarta 14/10 = 3 dias úteis passados; 1,5 em pausa; atraso 1,5.
            'exemplo do F7' => ['2026-10-09', '2026-10-14', 1.5, 1.5],
            'sem pausa' => ['2026-10-09', '2026-10-14', 0.0, 3.0],
            'vence hoje: no prazo' => ['2026-10-14', '2026-10-14', 0.0, 0.0],
            'vence na frente: no prazo' => ['2026-10-16', '2026-10-14', 0.0, 0.0],
            'pausa cobre o atraso: nunca abaixo de zero' => ['2026-10-09', '2026-10-14', 3.4, 0.0],
            'sem data prevista' => [null, '2026-10-14', 0.0, null],
            'o sábado e o domingo não contam' => ['2026-10-09', '2026-10-11', 0.0, 0.0],
        ];
    }

    #[DataProvider('atrasos')]
    public function test_delay_counts_business_days_after_the_due_date_minus_the_pause(?string $due, string $today, float $paused, ?float $expected): void
    {
        $this->assertSame($expected, StageTime::delay($due, $today, $paused));
    }

    public function test_task_times_reads_the_milestones_from_the_intervals(): void
    {
        $now = $this->at('2026-10-09 12:00');
        $times = new TaskTimes(StageTime::intervals([
            ['stage_id' => 1, 'role_id' => StageRole::QUEUE, 'at' => $this->at('2026-10-05 09:00')],
            ['stage_id' => 3, 'role_id' => StageRole::DEVELOPMENT, 'at' => $this->at('2026-10-06 09:00')],
            ['stage_id' => 7, 'role_id' => StageRole::PAUSED, 'at' => $this->at('2026-10-06 21:00')],
            ['stage_id' => 3, 'role_id' => StageRole::DEVELOPMENT, 'at' => $this->at('2026-10-07 09:00')],
            ['stage_id' => 6, 'role_id' => StageRole::DONE, 'at' => $this->at('2026-10-08 09:00')],
        ], $now, self::ZONE), $now, self::ZONE);

        $this->assertTrue($times->firstDevelopmentAt()->equalTo($this->at('2026-10-06 09:00')));
        $this->assertTrue($times->doneAt()->equalTo($this->at('2026-10-08 09:00')));
        $this->assertEqualsWithDelta(1.5, $times->daysIn(StageRole::DEVELOPMENT), 0.0001);
        $this->assertEqualsWithDelta(0.5, $times->pausedSinceDevelopment(), 0.0001);
        $this->assertSame(StageRole::DONE, $times->current()['role_id']);
        $this->assertTrue($times->leftOrigin());
        $this->assertArrayNotHasKey(StageRole::DONE, $times->byRole());
    }

    public function test_a_card_that_never_left_the_backlog_has_one_open_interval(): void
    {
        $now = $this->at('2026-10-09 12:00');
        $times = new TaskTimes(StageTime::intervals([
            ['stage_id' => 1, 'role_id' => StageRole::QUEUE, 'at' => $this->at('2026-10-05 09:00')],
        ], $now, self::ZONE), $now, self::ZONE);

        $this->assertFalse($times->leftOrigin());
        $this->assertNull($times->firstDevelopmentAt());
        $this->assertNull($times->doneAt());
        $this->assertSame(0.0, $times->pausedSinceDevelopment());
    }
}
