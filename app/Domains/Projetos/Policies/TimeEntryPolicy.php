<?php

namespace App\Domains\Projetos\Policies;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TimeEntry;
use App\Platform\Models\User;

/**
 * Horas realizadas (escopo B5; F5 §09; P30, C14). *Minhas horas* é de todo papel menos o
 * Comercial. Lança na tarefa quem vê o projeto dela; o lançamento é sempre de quem está logado
 * (TimeEntryService). Corrige e exclui: o Administrador e quem gere o projeto, qualquer
 * lançamento; o dono, o próprio, enquanto ainda vê o projeto (quem saiu do projeto não escreve
 * nele, P5). Pergunta à ProjectPolicy, sem conta própria de "gestor". Aprovada por Lucas Scholz
 * em 15/10/2026 (spec b5-sprint-e-horas).
 */
class TimeEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSales();
    }

    public function create(User $user, Task $task): bool
    {
        return ! $user->isSales() && $user->can('view', $task->project);
    }

    public function update(User $user, TimeEntry $entry): bool
    {
        return $this->corrects($user, $entry);
    }

    /** Exclusão de verdade do lançamento feito por engano (F5 §10.13): quem pode corrigir. */
    public function delete(User $user, TimeEntry $entry): bool
    {
        return $this->corrects($user, $entry);
    }

    private function corrects(User $user, TimeEntry $entry): bool
    {
        $project = $entry->task->project;

        if ($user->can('manage', $project)) {
            return true;
        }

        return $entry->user_id === $user->id && ! $user->isSales() && $user->can('view', $project);
    }
}
