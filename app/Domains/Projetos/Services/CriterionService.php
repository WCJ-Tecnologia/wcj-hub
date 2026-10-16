<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Services\Concerns\WritesTasks;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Critérios de aceite do card (P17, P36, P37; T1, T9; F3 §02 "Critério de aceite"). O número é
 * estável: o novo entra no fim (do card, ou do pai, no subitem) com a linha da tarefa travada, e
 * excluir não renumera. Editar um critério já testado o volta para A testar (P37). Excluir é de
 * verdade e só enquanto ele e cada subitem estão A testar (T1; spec b3-ficha).
 */
class CriterionService
{
    use WritesTasks;

    public function __construct(
        private readonly AuditService $audit,
        private readonly SprintService $sprints,
    ) {}

    public function add(Task $task, User $actor, string $description, ?int $parentId): TaskAcceptanceCriterion
    {
        return DB::transaction(function () use ($task, $actor, $description, $parentId) {
            $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);

            if ($parentId !== null) {
                $parent = TaskAcceptanceCriterion::query()->where('task_id', $locked->id)->whereNull('parent_id')->find($parentId);
                if ($parent === null) {
                    throw ValidationException::withMessages(['parent_id' => 'Escolha um critério deste card (um nível de subitem).']);
                }
            }

            $number = (int) TaskAcceptanceCriterion::query()->where('task_id', $locked->id)
                ->where('parent_key', $parentId ?? 0)->max('number') + 1;
            $criterion = TaskAcceptanceCriterion::query()->create([
                'task_id' => $locked->id, 'parent_id' => $parentId, 'number' => $number,
                'description' => $description, 'test_result_id' => TestResult::PENDING,
            ]);
            $this->audit->log(AuditActionEnum::CREATED, $criterion, null, $this->snapshot($criterion), $actor->id);

            return $criterion;
        });
    }

    public function update(TaskAcceptanceCriterion $criterion, User $actor, string $description): TaskAcceptanceCriterion
    {
        return DB::transaction(function () use ($criterion, $actor, $description) {
            $this->sprints->lockProject($criterion->task->project);
            $this->lockTask($criterion->task);
            $locked = TaskAcceptanceCriterion::query()->lockForUpdate()->findOrFail($criterion->id);

            $before = $this->snapshot($locked);
            $locked->description = $description;
            if ($locked->isDirty('description') && $locked->test_result_id !== TestResult::PENDING) {
                $locked->test_result_id = TestResult::PENDING;
            }
            $locked->save();
            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);
            }

            return $locked;
        });
    }

    /** T1: só enquanto ele e cada subitem estão A testar; os subitens saem junto (cascade). */
    public function delete(TaskAcceptanceCriterion $criterion, User $actor): void
    {
        DB::transaction(function () use ($criterion, $actor) {
            $this->sprints->lockProject($criterion->task->project);
            $this->lockTask($criterion->task);
            $locked = TaskAcceptanceCriterion::query()->with(['children', 'parent'])->lockForUpdate()->findOrFail($criterion->id);

            $tested = collect([$locked, ...$locked->children])->contains(fn ($item) => $item->test_result_id !== TestResult::PENDING);
            if ($tested) {
                throw new DomainException("{$this->label($locked)} já foi testado: edite em vez de excluir.");
            }

            $before = [...$this->snapshot($locked), 'children' => $locked->children->map(fn ($item) => $this->snapshot($item))->all()];
            $locked->delete();
            $this->audit->log(AuditActionEnum::DELETED, $locked, $before, null, $actor->id);
        });
    }

    private function label(TaskAcceptanceCriterion $criterion): string
    {
        return $criterion->parent === null ? "CA_{$criterion->number}" : "CA_{$criterion->parent->number}.{$criterion->number}";
    }

    /** @return array<string, mixed> */
    private function snapshot(TaskAcceptanceCriterion $criterion): array
    {
        return [
            'task_id' => $criterion->task_id, 'parent_id' => $criterion->parent_id, 'number' => $criterion->number,
            'description' => $criterion->description, 'test_result_id' => $criterion->test_result_id,
        ];
    }
}
