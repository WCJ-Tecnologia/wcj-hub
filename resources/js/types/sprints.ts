/**
 * Contrato da tela de sprints (F5 §01 a §05) entre o front e o `SprintScreen` / `SprintPresenter`
 * do B5. Datas de negócio em `AAAA-MM-DD` (a tela formata com `dateBr`); horas em número decimal.
 */

import type { StageRoleSlug, TaskRef } from '@/types/quadro';

/** Os campos de uma sprint, como a tela manda (F5 §02). `goal` vazio vai como `''`. */
export interface SprintFields {
    name: string;
    goal: string;
    starts_on: string;
    ends_on: string;
}

/** Uma linha da lista de sprints (F5 §03). */
export interface SprintRow {
    id: number;
    name: string;
    goal: string | null;
    starts_on: string;
    ends_on: string;
    /** Seg–sex entre início e fim, inclusive (P48). */
    business_days: number;
    open: boolean;
    /** `DD/MM/AAAA` no fuso de São Paulo; null = aberta. */
    closed_at: string | null;
    /** Tarefas prontas de total, sem as arquivadas (F5 §03). */
    tasks_done: number;
    tasks_total: number;
    /** Decidido pela `SprintPolicy`; a tela só esconde botão. */
    can: { update: boolean; close: boolean; reopen: boolean; delete: boolean };
}

/** Uma tarefa da sprint (F5 §03 "Tarefas da sprint"). */
export interface SprintTaskRow extends TaskRef {
    stage: { id: number; name: string; role: StageRoleSlug };
    developer: { id: number; name: string } | null;
    qa: { id: number; name: string } | null;
    estimated_hours: number | null;
    /** Reestimativa proposta pelo dev, à espera do gestor (P27). */
    proposed_hours: number | null;
    /** Soma dos lançamentos de qualquer pessoa (P30). */
    logged_hours: number;
    /** Data prevista (P28); null quando não calculada. */
    due_on: string | null;
}

/** Painel *Capacidade* (P48, P49): só para quem gere o projeto. */
export interface CapacityPanel {
    business_days: number;
    developers: {
        id: number;
        name: string;
        /** Carga diária (N3); null = capacidade não calculada. */
        daily_hours: number | null;
        capacity: number | null;
        remaining: number;
        /** Capacidade − faltam; negativa só como aviso (P49). */
        difference: number | null;
    }[];
    without_developer: { tasks: number; hours: number };
    without_hours: { tasks: number };
}

/** A sprint escolhida na tela: a linha, as tarefas, a capacidade e as opções dos filtros. */
export interface SelectedSprint extends SprintRow {
    tasks: SprintTaskRow[];
    capacity: CapacityPanel | null;
    options: { developers: { id: number; name: string }[]; stages: { id: number; name: string }[] };
}

/** Uma tarefa que não chegou a `Done`, no diálogo de encerrar (F5 §02). */
export interface PendingTask extends TaskRef {
    stage: { id: number; name: string; role: StageRoleSlug };
    developer: string | null;
}

/** O diálogo de encerrar: só vem para quem pode encerrar a sprint aberta. */
export interface SprintClosing {
    sprint_id: number;
    tasks: PendingTask[];
    /** A próxima sprint proposta: início no 1º dia útil depois do fim (P50). */
    next: SprintFields;
}

export type SprintDestination = 'next' | 'backlog';

export type SprintQuickFilter = 'no_developer' | 'no_hours';

/** Filtros da tela (F5 §05), como o controller lê da URL. */
export interface SprintFilters {
    sprint: number | null;
    search: string;
    status: 'open' | 'closed' | null;
    developer_id: number | null;
    stage_id: number | null;
    quick: SprintQuickFilter | null;
}

export interface SprintProject {
    id: number;
    key_prefix: string;
    name: string;
    client: string;
    finished: boolean;
    /** Duração padrão em dias úteis (P46). */
    sprint_days: number;
}
