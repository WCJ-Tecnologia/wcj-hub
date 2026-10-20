<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskPullRequest;
use App\Domains\Projetos\Services\Concerns\WritesTasks;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\Concerns\RefusesDuplicates;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Links de PR do card (P23; F3 §04): colar e remover. O mesmo link duas vezes no card é recusado
 * (unique do banco). Remover é exclusão de verdade (spec b3-ficha), com o antes na auditoria.
 */
class TaskPullRequestService
{
    use RefusesDuplicates, WritesTasks;

    public const REPEATED = 'Este PR já está no card';

    public function __construct(
        private readonly AuditService $audit,
        private readonly SprintService $sprints,
    ) {}

    public function add(Task $task, User $actor, string $url): TaskPullRequest
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($task, $actor, $url) {
            $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);
            if ($locked->pullRequests()->where('url', $url)->exists()) {
                throw ValidationException::withMessages(['url' => self::REPEATED]);
            }

            $pr = $locked->pullRequests()->create(['url' => $url, 'user_id' => $actor->id]);
            $this->audit->log(AuditActionEnum::CREATED, $pr, null, ['task_id' => $pr->task_id, 'url' => $pr->url], $actor->id);

            return $pr;
        }), fn (UniqueConstraintViolationException $e) => str_contains($e->getMessage(), 'task_pull_requests_task_id_url_unique')
            ? ValidationException::withMessages(['url' => self::REPEATED]) : null);
    }

    public function remove(TaskPullRequest $pr, User $actor): void
    {
        DB::transaction(function () use ($pr, $actor) {
            $this->sprints->lockProject($pr->task->project);
            $this->lockTask($pr->task);
            $pr->delete();
            $this->audit->log(AuditActionEnum::DELETED, $pr, ['task_id' => $pr->task_id, 'url' => $pr->url], null, $actor->id);
        });
    }
}
