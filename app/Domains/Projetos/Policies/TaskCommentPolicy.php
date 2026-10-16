<?php

namespace App\Domains\Projetos\Policies;

use App\Domains\Projetos\Models\TaskComment;
use App\Platform\Models\User;

/**
 * Comentário do card (P24; F3 §10.10; spec b3-ficha): só o autor edita e exclui o próprio, e só
 * enquanto ainda vê o projeto (quem saiu do projeto não mexe mais, como o lançamento de horas do
 * B5). O Administrador e o gestor não mexem no comentário dos outros. Comentar é a
 * TaskPolicy::comment.
 */
class TaskCommentPolicy
{
    public function update(User $user, TaskComment $comment): bool
    {
        return $this->ownsAndSees($user, $comment);
    }

    public function delete(User $user, TaskComment $comment): bool
    {
        return $this->ownsAndSees($user, $comment);
    }

    private function ownsAndSees(User $user, TaskComment $comment): bool
    {
        return $comment->user_id === $user->id && $user->can('view', $comment->task->project);
    }
}
