/**
 * Contrato de *Minhas horas* (F5 §02 a §05) entre o front e o `TimeEntryPresenter` do B5. Datas em
 * `AAAA-MM-DD`; horas em número decimal.
 */

import type { TaskPickRef, TaskRef } from '@/types/quadro';

/** Um lançamento meu (F5 §03 "Minhas horas"). */
export interface TimeEntryRow {
    id: number;
    worked_on: string;
    hours: number;
    project: { id: number; key_prefix: string; name: string };
    task: TaskRef;
    /** Decidido pela `TimeEntryPolicy`: o dono corrige enquanto está no projeto (P30). */
    can: { update: boolean; delete: boolean };
}

export interface TimeEntryTotals {
    /** Do dia mais recente para o mais antigo, só os dias com lançamento. */
    days: { date: string; hours: number }[];
    total: number;
}

/** Tarefa que posso escolher ao lançar: dos projetos que vejo, sem as arquivadas. */
export interface TimeEntryTaskOption extends TaskPickRef {
    project_id: number;
    /** Sou dev ou QA dela: aparece primeiro na busca (F5 §02). */
    mine: boolean;
}

export interface TimeEntryFilters {
    from: string;
    to: string;
    project_id: number | null;
    search: string;
}

/** O que o formulário de lançar e corrigir manda (F5 §02). A pessoa é sempre quem está logado. */
export interface TimeEntryFormData {
    worked_on: string;
    hours: string;
}
