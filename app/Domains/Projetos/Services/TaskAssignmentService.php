<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\ProjectMember;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Notifications\TaskAssigned;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Dev, QA e prioridade da tarefa (P19, P20; F3 §02), o mínimo que o *Montar sprint* do quadro
 * precisa (D-GER-35) e que a ficha do card (B3) reaproveita. Só muda a chave que vier: ausente fica
 * como está, nula limpa (dev e QA; P20 deixa os dois vazios). Dev e QA são pessoas ativas alocadas
 * no projeto da tarefa (P5, R15), conferidas aqui com a linha travada. Definir o dev preenche a data
 * prevista que ainda falta (P28: nunca refaz pela troca de dev). Uma linha de auditoria com o antes e
 * o depois (G3).
 *
 * P22: quem passa a ser dev ou QA recebe aviso no sistema e por e-mail (`TaskAssigned`, B3), depois do
 * commit, igual na ficha e no *Montar sprint*; quem se define a si mesmo não (F3 §10.5).
 */
class TaskAssignmentService
{
    public const NOT_ALLOCATED = 'não está alocado neste projeto';

    /** @var array<string, int> */
    public const PRIORITIES = [
        'low' => TaskPriority::LOW,
        'normal' => TaskPriority::NORMAL,
        'high' => TaskPriority::HIGH,
        'urgent' => TaskPriority::URGENT,
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly DueDateService $dueDates,
    ) {}

    /**
     * @param  array{developer_id?: ?int, qa_id?: ?int, priority?: string}  $changes
     */
    public function update(Task $task, array $changes, User $actor): Task
    {
        return DB::transaction(function () use ($task, $changes, $actor) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->project_id !== $task->project_id) {
                throw new DomainException('A tarefa não é deste projeto.');
            }

            $this->assertAllocated($locked, array_filter([$changes['developer_id'] ?? null, $changes['qa_id'] ?? null]));

            $before = $this->snapshot($locked);
            if (array_key_exists('developer_id', $changes)) {
                $locked->developer_id = $changes['developer_id'];
            }
            if (array_key_exists('qa_id', $changes)) {
                $locked->qa_id = $changes['qa_id'];
            }
            if (array_key_exists('priority', $changes)) {
                $locked->task_priority_id = self::PRIORITIES[$changes['priority']]
                    ?? throw new DomainException('Prioridade desconhecida.');
            }
            if ($locked->isDirty('developer_id')) {
                $this->dueDates->apply($locked);
            }
            $locked->save();

            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);
                // P22: o dev e o QA novos são avisados depois do commit; quem se define não (F3 §10.5).
                foreach (['developer_id' => TaskAssigned::DEVELOPER, 'qa_id' => TaskAssigned::QA] as $column => $role) {
                    if ($locked->wasChanged($column) && $locked->{$column} !== null && $locked->{$column} !== $actor->id) {
                        $assigned = $locked;
                        DB::afterCommit(fn () => User::query()->find($assigned->{$column})?->notify(new TaskAssigned($assigned->loadMissing('project'), $actor, $role)));
                    }
                }
            }

            return $locked;
        });
    }

    /**
     * As pessoas ativas alocadas no projeto, das dadas (P5). Uma consulta.
     *
     * @param  list<int>  $userIds
     * @return list<int>
     */
    public function allocated(int $projectId, array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return ProjectMember::query()
            ->where('project_id', $projectId)
            ->whereIn('user_id', array_values(array_unique($userIds)))
            ->whereIn('user_id', User::query()->active()->select('id'))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @param  array<int, int>  $userIds */
    private function assertAllocated(Task $task, array $userIds): void
    {
        $missing = array_diff($userIds, $this->allocated($task->project_id, array_values($userIds)));
        if ($missing !== []) {
            $name = User::query()->whereKey(reset($missing))->value('name') ?? 'A pessoa';
            throw new DomainException("{$name} ".self::NOT_ALLOCATED.'.');
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(Task $task): array
    {
        return [
            'developer_id' => $task->developer_id,
            'qa_id' => $task->qa_id,
            'task_priority_id' => $task->task_priority_id,
            'due_on' => $task->due_on?->toDateString(),
        ];
    }
}
