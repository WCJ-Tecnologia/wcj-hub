<?php

namespace App\Domains\Projetos\Policies;

use App\Domains\Projetos\Models\Project;
use App\Platform\Models\User;

/**
 * Projeto (escopo B2; F2 §09; P1, P5, C14). "Gere" = Administrador, ou Gestor de projetos que é
 * o responsável (manager_id) ou está alocado. Quem perdeu o papel de gestor deixa de gerir,
 * mesmo sendo o responsável: o F2 §09 nega configuração ao dev e o Comercial não entra em
 * Projetos (premissa do PR). Project::scopeVisibleTo espelha view.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSales();
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSales()) {
            return false;
        }

        return $this->manages($user, $project) || $project->hasMember($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isProjectManager();
    }

    /** Editar, encerrar, reabrir e toda a configuração (pessoas, etapas, áreas, padrões). */
    public function manage(User $user, Project $project): bool
    {
        return $user->isAdmin() || $this->manages($user, $project);
    }

    /**
     * As abas de indicadores do projeto e o `status_do_projeto` pela IA (F7 §09; P53, P54): o gestor
     * do projeto e o Administrador. O dev vê só *Minhas tarefas e horas*, que pede `view`.
     */
    public function viewIndicators(User $user, Project $project): bool
    {
        return $this->manage($user, $project);
    }

    /** Só o projeto nunca usado, e só o Administrador (G2, F2 §10.9); o "nunca usado" é do Service. */
    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    private function manages(User $user, Project $project): bool
    {
        if (! $user->isProjectManager()) {
            return false;
        }

        return $project->manager_id === $user->id || $project->hasMember($user);
    }
}
