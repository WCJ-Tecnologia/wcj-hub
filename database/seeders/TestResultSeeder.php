<?php

namespace Database\Seeders;

use App\Domains\Projetos\Models\TestResult;
use Database\Seeders\Concerns\SemeiaDominioFechado;
use Illuminate\Database\Seeder;

/** test_results (P36, P37). O id 1 é o default de task_acceptance_criteria.test_result_id. */
class TestResultSeeder extends Seeder
{
    use SemeiaDominioFechado;

    public function run(): void
    {
        $this->semear(TestResult::class, [
            TestResult::PENDING => ['slug' => 'pending', 'label' => 'A testar'],
            TestResult::PASSED => ['slug' => 'passed', 'label' => 'Aprovado'],
            TestResult::FAILED => ['slug' => 'failed', 'label' => 'Reprovado'],
        ]);
    }
}
