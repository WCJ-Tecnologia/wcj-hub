<?php

namespace App\Domains\Projetos\Services\Concerns;

use App\Domains\Projetos\Models\Task;

/** A referência de um card (chave, título, link) e a etapa dele, como as listas dos indicadores mandam (F7 §03). */
trait PresentsTaskRefs
{
    /** @return array{id: int, key: string, title: string, url: string} */
    private function ref(Task $task): array
    {
        return ['id' => $task->id, 'key' => $task->key(), 'title' => $task->title, 'url' => $task->url()];
    }

    /** @return array{id: int, name: string, role: string} */
    private function stage(Task $task): array
    {
        return ['id' => $task->boardStage->id, 'name' => $task->boardStage->name, 'role' => $task->boardStage->stageRole->slug];
    }
}
