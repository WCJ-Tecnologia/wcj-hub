<?php

namespace Database\Seeders;

use App\Domains\Projetos\Models\ReworkReason;
use Illuminate\Database\Seeder;

/**
 * rework_reasons (P40, R11), lista cadastrável do Administrador. Só na primeira semente: rodar
 * de novo não recria o que ele renomeou ou desativou.
 */
class ReworkReasonSeeder extends Seeder
{
    public function run(): void
    {
        if (ReworkReason::query()->exists()) {
            return;
        }

        foreach ([
            ['label' => 'Ajuste visual', 'requires_text' => false],
            ['label' => 'Regra de negócio', 'requires_text' => false],
            ['label' => 'Erro / bug', 'requires_text' => false],
            ['label' => 'Requisito incompleto ou mal documentado', 'requires_text' => false],
            ['label' => 'Outro', 'requires_text' => true],
        ] as $motivo) {
            ReworkReason::query()->create($motivo);
        }
    }
}
