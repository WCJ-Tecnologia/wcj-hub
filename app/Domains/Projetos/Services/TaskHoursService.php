<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Horas previstas da tarefa pelo gestor e pelo Administrador (P27, P28; F5 §04): definir ou
 * reestimar direto, e aprovar ou recusar a reestimativa que o dev propôs. Toda mudança das horas
 * refaz a data prevista a partir da mesma 1ª entrada em desenvolvimento (DueDateService) e fica
 * em audit_logs com o antes (P27, G3). A tela da sprint usa hoje; a ficha do card (B3) usa o mesmo.
 */
class TaskHoursService
{
    public const NO_PROPOSAL = 'Não há reestimativa proposta nesta tarefa.';

    public function __construct(
        private readonly AuditService $audit,
        private readonly DueDateService $dueDates,
    ) {}

    /** Nulo limpa as horas (e com elas a data prevista). O mesmo valor não grava nada. */
    public function estimate(Task $task, ?string $hours, User $actor): Task
    {
        return $this->write($task, $actor, function (Task $locked) use ($hours) {
            $locked->estimated_hours = $hours;
        });
    }

    /**
     * O dev propõe (P27; F3 §04): a proposta fica ao lado das horas, à espera de quem gere; horas
     * e data prevista não mudam. Propor de novo troca a proposta; nulo a retira.
     */
    public function propose(Task $task, ?string $hours, User $actor): Task
    {
        return $this->write($task, $actor, function (Task $locked) use ($hours, $actor) {
            $locked->proposed_hours = $hours;
            $locked->proposed_by = $hours === null ? null : $actor->id;
        });
    }

    /** A proposta vira as horas previstas e sai (P27). */
    public function approveProposal(Task $task, User $actor): Task
    {
        return $this->write($task, $actor, function (Task $locked) {
            $this->assertProposal($locked);
            $locked->estimated_hours = $locked->proposed_hours;
            $locked->proposed_hours = null;
            $locked->proposed_by = null;
        });
    }

    /** Recusar só limpa a proposta: horas e data ficam (P27). */
    public function rejectProposal(Task $task, User $actor): Task
    {
        return $this->write($task, $actor, function (Task $locked) {
            $this->assertProposal($locked);
            $locked->proposed_hours = null;
            $locked->proposed_by = null;
        });
    }

    /** @param  \Closure(Task): void  $change */
    private function write(Task $task, User $actor, \Closure $change): Task
    {
        return DB::transaction(function () use ($task, $actor, $change) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->project_id !== $task->project_id) {
                throw new DomainException('A tarefa não é deste projeto.');
            }

            $before = $this->snapshot($locked);
            $change($locked);
            if ($locked->isDirty('estimated_hours')) {
                $this->dueDates->apply($locked, reestimate: true);
            }
            $locked->save();

            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);
            }

            return $locked;
        });
    }

    private function assertProposal(Task $task): void
    {
        if ($task->proposed_hours === null) {
            throw new DomainException(self::NO_PROPOSAL);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(Task $task): array
    {
        return [
            'estimated_hours' => $task->estimated_hours,
            'proposed_hours' => $task->proposed_hours,
            'proposed_by' => $task->proposed_by,
            'due_on' => $task->due_on?->toDateString(),
        ];
    }
}
