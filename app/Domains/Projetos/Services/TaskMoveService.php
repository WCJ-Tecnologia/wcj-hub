<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Notifications\TaskReturned;
use App\Domains\Projetos\Services\Concerns\WritesTasks;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mover o card (escopo B3, PR 1; F3 §04; P13, P14, P15, P22, P28, P34, P37, P39, P40, P45, R5).
 * Mudar de etapa só por aqui (ou pelo SprintTaskService, que este chama ao cruzar o Backlog): o
 * movimento e a auditoria andam juntos. Nada trava o fluxo por falta de dev, horas, PR ou parecer
 * (premissa de Projetos). Trava a linha do projeto e depois a do card, como toda escrita de card.
 */
class TaskMoveService
{
    use WritesTasks;

    public function __construct(
        private readonly AuditService $audit,
        private readonly MovementReasons $reasons,
        private readonly SprintService $sprints,
        private readonly SprintTaskService $sprintTasks,
        private readonly DueDateService $dueDates,
    ) {}

    /**
     * Leva o card para a etapa e o deixa onde foi solto (P13, P14; F3 §03, decisão de 14/10).
     *
     * - Sair do Backlog é pôr na sprint aberta (P45): o SprintTaskService leva ao To do e, sem
     *   sprint aberta, cria a `new_sprint` na mesma transação (R5). Destino além do To do grava os
     *   dois movimentos (F3 §10.2). Entrar no Backlog tira da sprint, sem contar retrabalho.
     * - Ir para a pausa pede o motivo quando o projeto exige (P15).
     * - A volta de code review, teste ou pronto para sprint ou desenvolvimento pede origem e
     *   motivo, conta um ciclo, limpa o teste cruzado, volta o roteiro para A testar e avisa o dev
     *   (P22, P34, P37, P39); com "foi engano", grava o movimento sem nada disso.
     * - A 1ª entrada em desenvolvimento calcula a data prevista (P28).
     * - `sequence` (os ids da coluna de destino na ordem da tela) renumera a ordem manual; sem ele,
     *   o card entra no fim (*Mover para…*).
     *
     * @param  array{sequence?: ?list<int>, pause_reason?: ?string, mistake?: bool, rework_origin_id?: ?int, rework_reason_id?: ?int, rework_reason_text?: ?string, new_sprint?: ?array{name: string, goal: ?string, starts_on: string, ends_on: string}}  $data
     */
    public function move(Task $task, BoardStage $to, User $actor, array $data = [], ?int $aiAuthorizationId = null): Task
    {
        $returned = null;

        $moved = DB::transaction(function () use ($task, $to, $actor, $data, $aiAuthorizationId, &$returned) {
            $project = $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);

            $target = BoardStage::query()->findOrFail($to->id);
            $this->assertSameProject($locked, $target->project_id, 'stage_id');
            if ($target->archived_at !== null) {
                throw ValidationException::withMessages(['stage_id' => 'Esta etapa foi removida do quadro.']);
            }

            $from = $locked->boardStage;
            if ($from->id === $target->id) {
                $this->reorder($locked, $target, $data['sequence'] ?? null, $actor);

                return $locked;
            }

            // Os campos pedidos vêm antes de qualquer escrita: faltou um, nada muda.
            $fields = $this->reasons->resolve($project, $from, $target, $data);
            $before = $this->snapshot($locked);

            if ($from->stage_role_id === StageRole::QUEUE) {
                $this->sprintTasks->addTask($locked, $actor, $data['new_sprint'] ?? null);
                $locked = $this->lockTask($locked);
            } elseif ($target->stage_role_id === StageRole::QUEUE) {
                $this->sprintTasks->removeTask($locked, $actor);
                $locked = $this->lockTask($locked);
            }

            $current = $locked->boardStage;
            if ($current->id !== $target->id) {
                $isRework = $fields['rework_origin_id'] !== null;
                if ($isRework) {
                    // P34, P37: a volta limpa a marca de teste cruzado e o roteiro inteiro volta a testar.
                    $locked->cross_tested_by = null;
                    $locked->cross_tested_at = null;
                    $locked->acceptanceCriteria()->update(['test_result_id' => TestResult::PENDING]);
                }

                $locked->board_stage_id = $target->id;
                $locked->position = $this->nextPosition($target);
                $locked->save();
                $this->recordMovement($locked, $actor, $current, $target, $fields, $aiAuthorizationId);

                if ($target->stage_role_id === StageRole::DEVELOPMENT) {
                    $this->dueDates->apply($locked);
                    $locked->save();
                }

                if ($isRework && $locked->developer_id !== null && $locked->developer_id !== $actor->id) {
                    $returned = [$locked->developer_id, $target->name, $fields];
                }
            }

            $this->audit->log(AuditActionEnum::MOVED, $locked, $before, [...$this->snapshot($locked), ...$fields], $actor->id, $aiAuthorizationId);
            $this->reorder($locked, $target, $data['sequence'] ?? null, $actor, audit: false);

            return $locked;
        });

        // O aviso sai depois do commit: movimento desfeito não avisa ninguém (P22). Dev desativado
        // segue no card (G2), mas não recebe e-mail (CR-1-06).
        if ($returned !== null) {
            [$developerId, $stageName, $fields] = $returned;
            User::query()->active()->find($developerId)?->notify(new TaskReturned($moved, $actor, $stageName, $fields));
        }

        return $moved;
    }

    /**
     * Ordem manual da coluna (P13): os ids de `sequence` ficam na ordem da tela; os cards da coluna
     * que a tela não mostrava (filtro) guardam o lugar entre eles. Renumera 1..n com as linhas
     * travadas; só grava o que mudou.
     *
     * @param  list<int>|null  $sequence
     */
    private function reorder(Task $task, BoardStage $stage, ?array $sequence, User $actor, bool $audit = true): void
    {
        if ($sequence === null) {
            return;
        }

        $column = Task::query()
            ->where('board_stage_id', $stage->id)
            ->whereNull('archived_at')
            ->orderBy('position')
            ->orderBy('number')
            ->lockForUpdate()
            ->get(['id', 'position']);

        $inColumn = $column->pluck('id')->all();
        $wanted = array_values(array_unique(array_filter($sequence, fn ($id) => in_array($id, $inColumn, true))));
        if (! in_array($task->id, $wanted, true)) {
            throw ValidationException::withMessages(['sequence' => 'A ordem enviada não tem o card movido.']);
        }

        // Cada card visível ocupa, na ordem nova, o lugar de um card visível da ordem antiga; o
        // escondido fica onde estava. Sobra sempre o último da sequência, que entra no fim.
        $queue = $wanted;
        $order = [];
        foreach ($inColumn as $id) {
            if ($id === $task->id) {
                continue;
            }
            $order[] = in_array($id, $wanted, true) ? array_shift($queue) : $id;
        }
        $order = [...$order, ...$queue];

        $old = $task->position;
        $positions = $column->pluck('position', 'id');
        foreach ($order as $index => $id) {
            if ($positions[$id] !== $index + 1) {
                Task::query()->whereKey($id)->update(['position' => $index + 1]);
            }
        }

        $task->refresh();
        if ($audit && $task->position !== $old) {
            $this->audit->log(AuditActionEnum::UPDATED, $task, ['position' => $old], ['position' => $task->position], $actor->id);
        }
    }

    /** R15: o que o card aponta é do projeto dele. Asserção no Service, sem FK composta. */
    private function assertSameProject(Task $task, int $projectId, string $field): void
    {
        if ($projectId !== $task->project_id) {
            throw ValidationException::withMessages([$field => 'Escolha um item deste projeto.']);
        }
    }
}
