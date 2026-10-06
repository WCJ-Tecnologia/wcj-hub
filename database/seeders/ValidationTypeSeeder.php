<?php

namespace Database\Seeders;

use App\Domains\Projetos\Models\ValidationType;
use Database\Seeders\Concerns\SemeiaDominioFechado;
use Illuminate\Database\Seeder;

/** validation_types (P39, P43): as origens de retrabalho. */
class ValidationTypeSeeder extends Seeder
{
    use SemeiaDominioFechado;

    public function run(): void
    {
        $this->semear(ValidationType::class, [
            ValidationType::CODE_REVIEW => ['slug' => 'code_review', 'label' => 'Code review'],
            ValidationType::CROSS_TEST => ['slug' => 'cross_test', 'label' => 'Teste cruzado'],
            ValidationType::QA => ['slug' => 'qa', 'label' => 'QA'],
            ValidationType::HOMOLOGATION => ['slug' => 'homologation', 'label' => 'Homologação'],
        ]);
    }
}
