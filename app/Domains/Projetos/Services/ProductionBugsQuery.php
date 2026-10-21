<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use Illuminate\Database\Eloquent\Collection;

/**
 * Bugs de produção do projeto (P25; F7 §03 "Qualidade"): todo card ligado a uma tarefa original,
 * do mais recente para o mais antigo, com a original e a etapa atual. O arquivado fica, com a
 * marca: o bug aconteceu. Não é retrabalho de ninguém.
 */
class ProductionBugsQuery
{
    /** @return Collection<int, Task> */
    public function forProject(Project $project): Collection
    {
        return $project->tasks()
            ->whereNotNull('origin_task_id')
            ->with(['originTask:id,project_id,number,title', 'originTask.project:id,key_prefix', 'boardStage:id,name,stage_role_id', 'boardStage.stageRole:id,slug'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
