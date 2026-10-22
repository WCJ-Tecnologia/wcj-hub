<?php

namespace App\Domains\Projetos\Policies;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Platform\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Card no quadro (escopo B3; F3 §09; P13, P26, P45, P52, P56, C14). Não tem conta própria de
 * "alocado" nem de "gestor": pergunta à ProjectPolicy (`view` = qualquer alocado, `manage` = quem
 * gere) e, para a sprint, à SprintPolicy. Projeto encerrado e card arquivado não são permissão: o
 * TaskService recusa com mensagem (P7, P26). Lançar horas é da TimeEntryPolicy (B5). Spec
 * b3-quadro-e-card (trilho completo).
 */
class TaskPolicy
{
    /** Card rápido no Backlog (P56): qualquer alocado. */
    public function create(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    /** Card de suporte (P52; F3 §10.8): quem gere. */
    public function createSupport(User $user, Project $project): bool
    {
        return $user->can('manage', $project);
    }

    /** Pôr na sprint e levar ao Backlog (P45): a mesma regra da tela da sprint. */
    public function manageSprint(User $user, Project $project): bool
    {
        return $user->can('manageTasks', [Sprint::class, $project]);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can('view', $task->project);
    }

    /** Ficha, etiquetas, participantes (F3 §09 "Editar a ficha"): qualquer alocado. */
    public function update(User $user, Task $task): bool
    {
        return $user->can('view', $task->project);
    }

    /**
     * Mudar de etapa ou de posição (P13): qualquer alocado. Levar o card do Backlog para outra
     * coluna, ou de outra coluna para o Backlog, é pôr ou tirar da sprint: só quem gere (P45).
     * Reordenar dentro do Backlog não muda a sprint. Sem destino, confere só o acesso.
     */
    public function move(User $user, Task $task, ?BoardStage $to = null): Response
    {
        if (! $user->can('view', $task->project)) {
            return Response::deny();
        }

        $from = $task->boardStage;
        $crossesBacklog = $to !== null && $to->id !== $from->id
            && ($from->stage_role_id === StageRole::QUEUE || $to->stage_role_id === StageRole::QUEUE);

        if ($crossesBacklog && ! $this->manageSprint($user, $task->project)) {
            return Response::deny('Só o gestor do projeto e o Administrador põem card na sprint ou o levam para o Backlog.');
        }

        return Response::allow();
    }

    /**
     * Propor horas (P27; F3 §04, F5 §02): quem vê o projeto e não gere. Quem gere define direto e
     * decide a proposta, pela SprintPolicy::manageTasks (spec b3-ficha).
     */
    public function proposeHours(User $user, Task $task): bool
    {
        return $user->can('view', $task->project) && ! $this->manageSprint($user, $task->project);
    }

    public function comment(User $user, Task $task): bool
    {
        return $user->can('view', $task->project);
    }

    public function attach(User $user, Task $task): bool
    {
        return $user->can('view', $task->project);
    }

    /** Arquivar e desarquivar (P26): quem gere. */
    public function archive(User $user, Task $task): bool
    {
        return $user->can('manage', $task->project);
    }

    /** Exclusão de verdade: quem gere; "só o nunca usado" é do TaskArchiveService, pelo TaskUsageQuery (G2, P26). */
    public function delete(User $user, Task $task): bool
    {
        return $user->can('manage', $task->project);
    }
}
