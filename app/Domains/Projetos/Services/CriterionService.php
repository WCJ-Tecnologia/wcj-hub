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
 * Critérios de aceite do card (P17, P36, P37; T1, T9; F3 §02 "Critério de aceite") e o resultado de
 * cada um como item do roteiro de QA (B4; F4 §02, §04). O número é estável: o novo entra no fim (do
 * card, ou do pai, no subitem) com a linha da tarefa travada, e excluir não renumera. Editar um
 * critério já testado o volta para A testar e limpa a última marcação (P37). Excluir é de verdade e
 * só enquanto ele e cada subitem estão A testar e sem evidência (T1; specs b3-ficha e b4-teste-e-qa).
 */
class CriterionService
{
    use WritesTasks;

    /** O resultado na tela (`CriterionResult` do kit) e o id em `test_results`: `passed` aparece como `approved`. */
    public const RESULTS = ['pending' => TestResult::PENDING, 'approved' => TestResult::PASSED, 'failed' => TestResult::FAILED];

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
                // P37: volta sozinho para A testar; ninguém marcou, então a última marcação sai.
                $locked->forceFill(['test_result_id' => TestResult::PENDING, 'marked_by' => null, 'marked_at' => null]);
            }
            $locked->save();
            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);
            }

            return $locked;
        });
    }

    /**
     * Marcar o resultado do item (P36; F4 §04): grava o resultado e a última marcação (quem e
     * quando), inclusive A testar marcado à mão. O mesmo resultado de novo não muda nada nem grava
     * auditoria. Pai e subitem se marcam à parte (F4 §10.4); reprovar não move o card (premissa).
     */
    public function setResult(TaskAcceptanceCriterion $criterion, User $actor, string $result): TaskAcceptanceCriterion
    {
        return DB::transaction(function () use ($criterion, $actor, $result) {
            $this->sprints->lockProject($criterion->task->project);
            $this->lockTask($criterion->task);
            $locked = TaskAcceptanceCriterion::query()->lockForUpdate()->findOrFail($criterion->id);

            $resultId = self::RESULTS[$result];
            if ($locked->test_result_id === $resultId) {
                return $locked;
            }

            $before = $this->snapshot($locked);
            $locked->forceFill(['test_result_id' => $resultId, 'marked_by' => $actor->id, 'marked_at' => now()])->save();
            $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);

            return $locked;
        });
    }

    /**
     * T1: só enquanto ele e cada subitem estão A testar e sem evidência (a evidência é teste, F4
     * §10.5, e a FK do anexo é restrict); os subitens saem junto (cascade).
     */
    public function delete(TaskAcceptanceCriterion $criterion, User $actor): void
    {
        DB::transaction(function () use ($criterion, $actor) {
            $this->sprints->lockProject($criterion->task->project);
            $this->lockTask($criterion->task);
            $locked = TaskAcceptanceCriterion::query()->with(['children', 'parent'])->withExists('evidences')->lockForUpdate()->findOrFail($criterion->id);
            $locked->children->loadExists('evidences');

            $tested = collect([$locked, ...$locked->children])
                ->contains(fn ($item) => $item->test_result_id !== TestResult::PENDING || $item->evidences_exists);
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
            'marked_by' => $criterion->marked_by, 'marked_at' => $criterion->marked_at?->toIso8601String(),
        ];
    }
}
