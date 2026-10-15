<?php

namespace Tests\Unit;

use App\Support\DiasUteis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Dias úteis seg–sex, sem feriado (P29), com os exemplos do F5; as mesmas contas do lib/dias-uteis.ts. */
class DiasUteisTest extends TestCase
{
    /** @return array<string, array{string, int, string}> */
    public static function somas(): array
    {
        return [
            // F5 §01: terça 06/10 com 10 dias úteis → segunda 19/10.
            'sprint padrão' => ['2026-10-06', 10, '2026-10-19'],
            // F5 §02: entrada na quinta 08/10, 2 dias → sexta 09/10; 4 dias → terça 13/10 (12/10 conta, P29).
            'dev de 8 h' => ['2026-10-08', 2, '2026-10-09'],
            'dev de 4 h' => ['2026-10-08', 4, '2026-10-13'],
            'um dia é o próprio início' => ['2026-10-08', 1, '2026-10-08'],
            // Começo no fim de semana conta a partir da segunda (F5 §10.9).
            'sábado' => ['2026-10-10', 1, '2026-10-12'],
            'domingo' => ['2026-10-11', 3, '2026-10-14'],
            'zero dias' => ['2026-10-08', 0, '2026-10-08'],
        ];
    }

    #[DataProvider('somas')]
    public function test_add_conta_o_inicio_como_primeiro_dia_util(string $start, int $days, string $expected): void
    {
        $this->assertSame($expected, DiasUteis::add($start, $days));
    }

    public function test_between_conta_seg_a_sex_inclusive(): void
    {
        $this->assertSame(10, DiasUteis::between('2026-10-06', '2026-10-19'));
        $this->assertSame(1, DiasUteis::between('2026-10-08', '2026-10-08'));
        $this->assertSame(0, DiasUteis::between('2026-10-10', '2026-10-11'));
        $this->assertSame(0, DiasUteis::between('2026-10-19', '2026-10-06'));
    }

    public function test_next_e_o_primeiro_dia_util_depois(): void
    {
        // P50: a próxima sprint começa no 1º dia útil depois do fim da anterior.
        $this->assertSame('2026-10-20', DiasUteis::next('2026-10-19'));
        $this->assertSame('2026-10-12', DiasUteis::next('2026-10-09'));
        $this->assertSame('2026-10-12', DiasUteis::next('2026-10-10'));
    }
}
