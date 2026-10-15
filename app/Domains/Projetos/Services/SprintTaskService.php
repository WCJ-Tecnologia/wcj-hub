<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Platform\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Pôr e tirar card da sprint (P45, P52; F5 §04). Pôr preenche sprint_id e, se o card está na
 * Fila, o leva à etapa de papel sprint (To do); tirar limpa sprint_id e devolve à Fila. O card de
 * suporte só liga e desliga, sem trocar de coluna. A mesma ordem de trava do SprintService:
 * projeto, depois tarefa. O quadro do B3 chama addTask() no arrastar do Backlog para o To do.
 */
class SprintTaskService
{
    public function __construct(
        private readonly SprintService $sprints,
        private readonly SprintTaskMover $mover,
    ) {}

    /**
     * Sem sprint aberta, cria a $newSprint na mesma transação (R5); sem $newSprint, recusa (a IA e
     * o quadro sem diálogo). Se alguém abriu uma sprint no meio, o card entra nela e
     * `joined_existing` avisa a tela (F5 §07).
     *
     * @param  array{name: string, goal: ?string, starts_on: string, ends_on: string}|null  $newSprint
     * @return array{sprint: Sprint, created: bool, joined_existing: bool}
     */
    public function addTask(Task $task, User $actor, ?array $newSprint = null): array
    {
        return $this->sprints->writeSprint($newSprint ?? [], fn () => DB::transaction(function () use ($task, $actor, $newSprint) {
            $project = $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task, $project->id);

            $sprint = $this->sprints->openSprint($project->id);
            $created = false;
            if ($sprint === null) {
                if ($newSprint === null) {
                    throw new DomainException("O projeto {$project->key_prefix} não tem sprint aberta. Crie a sprint pela tela.");
                }
                $sprint = $this->sprints->insertLocked($project, $newSprint, $actor);
                $created = true;
            }

            if ($locked->sprint_id !== $sprint->id) {
                $stage = $this->mover->isSupport($locked) || $locked->boardStage->stage_role_id !== StageRole::QUEUE
                    ? null
                    : $this->mover->roleStage($project->id, StageRole::SPRINT);
                $this->mover->relink($locked, $sprint, $stage, $actor);
            }

            return ['sprint' => $sprint, 'created' => $created, 'joined_existing' => ! $created && $newSprint !== null];
        }));
    }

    /** Volta à Fila, fora de sprint; não é retrabalho. Card fora de sprint: nada muda. */
    public function removeTask(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            $project = $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task, $project->id);

            if ($locked->sprint_id === null) {
                return $locked;
            }

            $stage = $this->mover->isSupport($locked) ? null : $this->mover->roleStage($project->id, StageRole::QUEUE);
            $this->mover->relink($locked, null, $stage, $actor);

            return $locked;
        });
    }

    private function lockTask(Task $task, int $projectId): Task
    {
        $locked = Task::query()->with(['boardStage', 'taskType'])->lockForUpdate()->findOrFail($task->id);
        if ($locked->project_id !== $projectId) {
            throw new DomainException('A tarefa não é deste projeto.');
        }
        if ($locked->archived_at !== null) {
            throw new DomainException('O card está arquivado: desarquive antes de pôr na sprint.');
        }

        return $locked;
    }
}
