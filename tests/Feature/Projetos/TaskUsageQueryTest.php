<?php

namespace Tests\Feature\Projetos;

use App\Domains\Projetos\Services\TaskUsageQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * O "nunca usado" do card (spec b3-arquivar-e-avisos, EXC-01) é uma lista: toda FK para `tasks`
 * ou conta como uso, ou sai junto com o card. Tabela nova ligada ao card (B4: evidência do QA; B6:
 * pacote) faz este teste falhar até alguém decidir em qual lista ela entra.
 */
class TaskUsageQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_foreign_key_to_tasks_is_either_a_use_or_goes_along_with_the_card(): void
    {
        $fks = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('REFERENCED_TABLE_NAME', 'tasks')
            ->get(['TABLE_NAME as t', 'COLUMN_NAME as c'])
            ->map(fn (object $row) => "{$row->t}.{$row->c}")
            ->sort()
            ->values()
            ->all();

        $classified = [...TaskUsageQuery::GOES_ALONG, ...array_keys(TaskUsageQuery::USES)];
        sort($classified);

        $this->assertSame($classified, $fks, 'FK para tasks sem decisão: entra em TaskUsageQuery::USES ou ::GOES_ALONG');
    }

    public function test_what_goes_along_is_cascade_and_nothing_else_is(): void
    {
        $rules = DB::table('information_schema.REFERENTIAL_CONSTRAINTS as r')
            ->join('information_schema.KEY_COLUMN_USAGE as k', fn ($join) => $join
                ->on('k.CONSTRAINT_NAME', '=', 'r.CONSTRAINT_NAME')
                ->on('k.CONSTRAINT_SCHEMA', '=', 'r.CONSTRAINT_SCHEMA'))
            ->where('r.CONSTRAINT_SCHEMA', DB::raw('DATABASE()'))
            ->where('r.REFERENCED_TABLE_NAME', 'tasks')
            ->get(['k.TABLE_NAME as t', 'k.COLUMN_NAME as c', 'r.DELETE_RULE as rule'])
            ->mapWithKeys(fn (object $row) => ["{$row->t}.{$row->c}" => $row->rule]);

        foreach (TaskUsageQuery::GOES_ALONG as $fk) {
            $this->assertSame('CASCADE', $rules[$fk], "{$fk} sai junto: precisa ser cascade");
        }
    }
}
