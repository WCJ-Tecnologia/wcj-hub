<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Notifications\TaskAssigned;
use App\Domains\Projetos\Notifications\TaskReturned;
use App\Domains\Projetos\Services\Concerns\WritesTasks;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use DomainException;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

/**
 * Tirar o card do quadro (escopo B3, PR 3; F3 §04 "Na ficha"; P26, P57; spec b3-arquivar-e-avisos):
 * arquivar o card com histórico, desarquivar e excluir de verdade o nunca usado. Só quem gere o
 * projeto (TaskPolicy::archive e ::delete, no FormRequest e no controller). A mesma ordem de trava
 * de toda escrita de card: a linha do projeto, depois a do card.
 */
class TaskArchiveService
{
    use WritesTasks;

    public const NOT_ARCHIVED = 'O card não está arquivado.';

    public function __construct(
        private readonly AuditService $audit,
        private readonly SprintService $sprints,
        private readonly TaskUsageQuery $usage,
    ) {}

    /**
     * Sai do quadro e das contas de prazo, atraso e capacidade; etapa, sprint e filhos ficam como
     * estão (as horas lançadas seguem nos indicadores, P26; arquivar o pai não mexe nos filhos, P57).
     */
    public function archive(Task $task, User $actor, string $reason): Task
    {
        return DB::transaction(function () use ($task, $actor, $reason) {
            $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);

            $before = $this->archiving($locked);
            $locked->forceFill(['archived_at' => now(), 'archive_reason' => $reason, 'archived_by' => $actor->id])->save();
            $this->audit->log(AuditActionEnum::ARCHIVED, $locked, $before, $this->archiving($locked), $actor->id);

            return $locked;
        });
    }

    /**
     * Volta ao fim do Backlog, fora de sprint (banco.md, tasks). O movimento fica gravado sem
     * retrabalho: desarquivar é decisão do gestor, não volta de desenvolvimento.
     */
    public function unarchive(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            $project = $this->sprints->lockProject($task->project);
            $locked = Task::query()->with('boardStage')->lockForUpdate()->findOrFail($task->id);
            if ($locked->archived_at === null) {
                throw new DomainException(self::NOT_ARCHIVED);
            }

            $before = [...$this->snapshot($locked), ...$this->archiving($locked)];
            $from = $locked->boardStage;
            $queue = $this->stageOfRole($project, StageRole::QUEUE);

            $locked->forceFill([
                'archived_at' => null,
                'archive_reason' => null,
                'archived_by' => null,
                'sprint_id' => null,
                'board_stage_id' => $queue->id,
                'position' => $this->nextPosition($queue),
            ])->save();
            if ($from->id !== $queue->id) {
                $this->recordMovement($locked, $actor, $from, $queue);
            }

            $this->audit->log(AuditActionEnum::UNARCHIVED, $locked, $before, [...$this->snapshot($locked), ...$this->archiving($locked)], $actor->id);

            return $locked;
        });
    }

    /**
     * Só o nunca usado (TaskUsageQuery), conferido com a linha travada. Critérios, participações e
     * o movimento de criação saem junto (cascade); o número não volta (T4: o contador não recua).
     * Os avisos do card ficam, sem o link da ficha (spec b3-arquivar-e-avisos).
     */
    public function delete(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $this->sprints->lockProject($task->project);
            $locked = Task::query()->with('project')->lockForUpdate()->findOrFail($task->id);
            $key = $locked->key();
            if (! $this->usage->isUnused($locked)) {
                throw new DomainException("{$key} já foi usado: arquive em vez de excluir.");
            }

            $before = [
                ...$this->snapshot($locked),
                'key' => $key,
                'parent_task_id' => $locked->parent_task_id,
                'criteria_count' => $locked->acceptanceCriteria()->count(),
            ];

            DatabaseNotification::query()
                ->whereIn('type', [TaskAssigned::class, TaskReturned::class])
                ->where('data->key', $key)
                ->update(['data->url' => null]);

            $locked->delete();
            $this->audit->log(AuditActionEnum::DELETED, $locked, $before, null, $actor->id);
        });
    }

    /** @return array{archived_at: ?string, archive_reason: ?string, archived_by: ?int} */
    private function archiving(Task $task): array
    {
        return [
            'archived_at' => $task->archived_at?->toIso8601String(),
            'archive_reason' => $task->archive_reason,
            'archived_by' => $task->archived_by,
        ];
    }
}
