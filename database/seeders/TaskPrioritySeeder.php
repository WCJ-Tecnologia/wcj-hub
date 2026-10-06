<?php

namespace Database\Seeders;

use App\Domains\Projetos\Models\TaskPriority;
use Database\Seeders\Concerns\SemeiaDominioFechado;
use Illuminate\Database\Seeder;

/** task_priorities (P19). */
class TaskPrioritySeeder extends Seeder
{
    use SemeiaDominioFechado;

    public function run(): void
    {
        $this->semear(TaskPriority::class, [
            TaskPriority::LOW => ['slug' => 'low', 'label' => 'Baixa'],
            TaskPriority::NORMAL => ['slug' => 'normal', 'label' => 'Normal'],
            TaskPriority::HIGH => ['slug' => 'high', 'label' => 'Alta'],
            TaskPriority::URGENT => ['slug' => 'urgent', 'label' => 'Urgente'],
        ]);
    }
}
