<?php

namespace App\Domains\Projetos\Policies;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Platform\Models\User;

/**
 * Sprint (escopo B5; F5 §09; P45, P49, P50). Não tem conta própria de "gestor": pergunta à
 * ProjectPolicy, para não haver um segundo jeito. Vê as sprints quem vê o projeto (`view`);
 * cria, edita, encerra, reabre, exclui, põe e tira card, estima na linha e vê a capacidade quem
 * gere o projeto (`manage`: Administrador, ou Gestor de projetos responsável ou alocado). As
 * regras de estado (uma aberta, reabrir sem outra aberta, excluir só a aberta sem tarefa) são do
 * SprintService. Projeto encerrado não trava nada aqui (premissa do kanban). Aprovada por Lucas
 * Scholz em 15/10/2026 (spec b5-sprint-e-horas).
 */
class SprintPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return $user->can('view', $sprint->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->can('manage', $project);
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return $user->can('manage', $sprint->project);
    }

    public function close(User $user, Sprint $sprint): bool
    {
        return $user->can('manage', $sprint->project);
    }

    public function reopen(User $user, Sprint $sprint): bool
    {
        return $user->can('manage', $sprint->project);
    }

    /** Exclusão de verdade: quem gere; "só a aberta sem tarefa" é do SprintService (G2). */
    public function delete(User $user, Sprint $sprint): bool
    {
        return $user->can('manage', $sprint->project);
    }

    /** Pôr e tirar card, horas previstas na linha e decidir a reestimativa proposta (P27, P45). */
    public function manageTasks(User $user, Project $project): bool
    {
        return $user->can('manage', $project);
    }

    /** O painel Capacidade é referência para o gestor; o dev não vê (F5 §09, §10.17). */
    public function viewCapacity(User $user, Project $project): bool
    {
        return $user->can('manage', $project);
    }
}
