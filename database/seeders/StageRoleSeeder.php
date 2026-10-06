<?php

namespace Database\Seeders;

use App\Domains\Projetos\Models\StageRole;
use Database\Seeders\Concerns\SemeiaDominioFechado;
use Illuminate\Database\Seeder;

/**
 * stage_roles (P10, P11). Os ids 1, 2 e 8 são lidos pela coluna gerada
 * board_stages.single_role_key: não mudam.
 */
class StageRoleSeeder extends Seeder
{
    use SemeiaDominioFechado;

    public function run(): void
    {
        $this->semear(StageRole::class, [
            StageRole::QUEUE => ['slug' => 'queue', 'label' => 'Fila'],
            StageRole::SPRINT => ['slug' => 'sprint', 'label' => 'Sprint'],
            StageRole::DEVELOPMENT => ['slug' => 'development', 'label' => 'Desenvolvimento'],
            StageRole::CODE_REVIEW => ['slug' => 'code_review', 'label' => 'Code review'],
            StageRole::TEST => ['slug' => 'test', 'label' => 'Teste'],
            StageRole::DONE => ['slug' => 'done', 'label' => 'Pronto'],
            StageRole::PAUSED => ['slug' => 'paused', 'label' => 'Pausa'],
            StageRole::SUPPORT => ['slug' => 'support', 'label' => 'Suporte'],
        ]);
    }
}
