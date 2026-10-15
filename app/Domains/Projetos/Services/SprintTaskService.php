<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Platform\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pôr e tirar card da sprint (P45, P52; F5 §04). Pôr preenche sprint_id e, se o card está na
 * Fila, o leva à etapa de papel sprint (To do); tirar limpa sprint_id e devolve à Fila. O card de
 * suporte só liga e desliga, sem trocar de coluna. A mesma ordem de trava do SprintService:
 * projeto, depois tarefa. O quadro do B3 chama addTask() no arrastar do Backlog para o To do e
 * addMany() no *Montar sprint*, com várias tarefas do Backlog de uma vez (D-GER-35).
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

    /**
     * Montar a sprint em lote pelo quadro (D-GER-35): as tarefas do Backlog entram juntas
     * na sprint aberta e vão para a etapa de papel sprint (To do), cada uma com o mesmo efeito do
     * arrastar (P45): movimento, fim da coluna e auditoria da tarefa. Sem sprint aberta, cria a
     * $newSprint na mesma transação (R5), e se alguém abriu uma no meio, as tarefas entram nela
     * (`joined_existing`, F5 §07). Tudo ou nada: se alguma não está mais no Backlog (arquivada, já
     * numa sprint, fora da Fila), nada grava e o erro do campo `task_ids` diz quais. As tarefas
     * travam pela ordem do id, para duas montagens ao mesmo tempo não se cruzarem; entram no
     * To do pela ordem da chave.
     *
     * @param  list<int>  $taskIds
     * @param  array{name: string, goal: ?string, starts_on: string, ends_on: string}|null  $newSprint
     * @return array{sprint: Sprint, created: bool, joined_existing: bool, count: int}
     */
    public function addMany(Project $project, array $taskIds, User $actor, ?array $newSprint = null): array
    {
        return $this->sprints->writeSprint($newSprint ?? [], fn () => DB::transaction(function () use ($project, $taskIds, $actor, $newSprint) {
            $locked = $this->sprints->lockProject($project);
            $ids = array_values(array_unique($taskIds));
            $tasks = Task::query()
                ->with(['boardStage', 'taskType', 'sprint:id,name'])
                ->whereKey($ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($tasks->contains(fn (Task $task) => $task->project_id !== $locked->id)) {
                throw new DomainException('A tarefa não é deste projeto.');
            }
            if ($tasks->count() !== count($ids)) {
                throw ValidationException::withMessages(['task_ids' => 'Alguma tarefa selecionada não existe mais. Nada foi gravado.']);
            }

            $refused = $tasks->sortBy('number')->map(fn (Task $task) => $this->notInBacklog($task, $locked))->filter()->values();
            if ($refused->isNotEmpty()) {
                throw ValidationException::withMessages(['task_ids' => 'Nada foi gravado: '.$refused->implode('; ').'.']);
            }

            $sprint = $this->sprints->openSprint($locked->id);
            $created = false;
            if ($sprint === null) {
                if ($newSprint === null) {
                    throw new DomainException("O projeto {$locked->key_prefix} não tem sprint aberta. Crie a sprint pela tela.");
                }
                $sprint = $this->sprints->insertLocked($locked, $newSprint, $actor);
                $created = true;
            }

            $todo = $this->mover->roleStage($locked->id, StageRole::SPRINT);
            foreach ($tasks->sortBy('number') as $task) {
                $this->mover->relink($task, $sprint, $this->mover->isSupport($task) ? null : $todo, $actor);
            }

            return ['sprint' => $sprint, 'created' => $created, 'joined_existing' => ! $created && $newSprint !== null, 'count' => $tasks->count()];
        }));
    }

    /** Por que a tarefa travada não está mais no Backlog (fila, fora de sprint, não arquivada), ou null. */
    private function notInBacklog(Task $task, Project $project): ?string
    {
        $key = "{$project->key_prefix}-{$task->number}";

        return match (true) {
            $task->archived_at !== null => "{$key} está arquivada",
            $task->sprint_id !== null => "{$key} já está na {$task->sprint->name}",
            $task->boardStage->stage_role_id !== StageRole::QUEUE => "{$key} não está mais no Backlog",
            default => null,
        };
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
