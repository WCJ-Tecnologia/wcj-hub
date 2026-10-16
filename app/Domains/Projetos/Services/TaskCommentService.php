<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskComment;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Comentários do card (P24; F3 §04 e §10.10): comentar com autor e data; o autor edita e exclui o
 * próprio (TaskCommentPolicy). O antes fica na auditoria; excluir é de verdade (spec b3-ficha).
 * Card arquivado aceita comentário (F3 §11 item 12: a ficha continua abrindo).
 */
class TaskCommentService
{
    public function __construct(private readonly AuditService $audit) {}

    public function add(Task $task, User $actor, string $body): TaskComment
    {
        return DB::transaction(function () use ($task, $actor, $body) {
            $comment = $task->comments()->create(['user_id' => $actor->id, 'body' => $body]);
            $this->audit->log(AuditActionEnum::CREATED, $comment, null, ['task_id' => $task->id, 'body' => $body], $actor->id);

            return $comment;
        });
    }

    public function update(TaskComment $comment, User $actor, string $body): TaskComment
    {
        return DB::transaction(function () use ($comment, $actor, $body) {
            $locked = TaskComment::query()->lockForUpdate()->findOrFail($comment->id);
            $before = ['body' => $locked->body];
            $locked->fill(['body' => $body])->save();
            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, ['body' => $body], $actor->id);
            }

            return $locked;
        });
    }

    public function delete(TaskComment $comment, User $actor): void
    {
        DB::transaction(function () use ($comment, $actor) {
            $locked = TaskComment::query()->lockForUpdate()->findOrFail($comment->id);
            $locked->delete();
            $this->audit->log(AuditActionEnum::DELETED, $locked, ['task_id' => $locked->task_id, 'user_id' => $locked->user_id, 'body' => $locked->body], null, $actor->id);
        });
    }
}
