<?php

namespace Database\Seeders;

use App\Domains\Projetos\Models\TaskType;
use Illuminate\Database\Seeder;

/**
 * task_types (P18), lista cadastrável: o Administrador edita. Os itens com slug são os de que
 * o código depende (improvement, support) e sempre existem; os outros só entram na primeira
 * semente, para a edição do Administrador não ser desfeita numa nova rodada.
 */
class TaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        $primeiraVez = ! TaskType::query()->exists();

        TaskType::query()->firstOrCreate(['slug' => TaskType::IMPROVEMENT], ['label' => 'Melhoria']);

        if ($primeiraVez) {
            TaskType::query()->create(['slug' => null, 'label' => 'Correção de bug']);
        }

        TaskType::query()->firstOrCreate(['slug' => TaskType::SUPPORT], ['label' => 'Suporte']);

        if ($primeiraVez) {
            TaskType::query()->create(['slug' => null, 'label' => 'Mudança (GMUD)']);
        }
    }
}
