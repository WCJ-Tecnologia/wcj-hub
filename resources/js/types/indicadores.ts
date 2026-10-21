/**
 * Contrato dos indicadores e do histórico do card (F7 §02, §03, §05, §06) entre o front e o
 * `IndicatorsScreen` / `TaskHistoryScreen` do B7. Tudo só leitura: nada aqui se grava. Datas de
 * negócio em `AAAA-MM-DD` (a tela formata com `dateBr`); instantes já formatados, `DD/MM/AAAA HH:MM`,
 * no fuso de São Paulo; dias úteis com uma casa decimal; horas em número decimal.
 */

import type { StageRoleSlug, TaskRef } from '@/types/quadro';

export type IndicatorsTab = 'situacao' | 'tempo' | 'horas' | 'pessoas' | 'qualidade' | 'minhas';

export interface IndicatorsProject {
    id: number;
    key_prefix: string;
    name: string;
    client: string;
    finished: boolean;
}

/** `sprint`: o id de uma sprint, `todas` ou `fora` (de sprint). `due`: o prazo de *Minhas tarefas e horas*. */
export interface IndicatorsFilters {
    tab: IndicatorsTab;
    search: string;
    sprint: string;
    developer_id: number | null;
    type_id: number | null;
    area_id: number | null;
    due: 'late' | 'until' | 'none' | null;
    until: string | null;
}

export interface IndicatorsOptions {
    sprints: { id: number; name: string; open: boolean }[];
    developers: { id: number; name: string }[];
    types: { id: number; label: string }[];
    areas: { id: number; name: string }[];
}

export interface StageRef {
    id: number;
    name: string;
    role: StageRoleSlug;
}

/** O que falta para a data prevista existir (P28). */
export type DueMissing = 'developer' | 'hours' | 'daily_hours';

/** Aba *Situação* (F7 §03): o projeto inteiro, fora do arquivo. */
export interface SituationTab {
    by_stage: (StageRef & { count: number })[];
    late: { task: TaskRef; developer: string | null; due_on: string; paused_days: number; delay_days: number }[];
    upcoming: { task: TaskRef; developer: string | null; due_on: string }[];
    paused: { task: TaskRef; developer: string | null; reason: string | null; since: string | null; by: string | null }[];
    without_due: { task: TaskRef; missing: DueMissing[] }[];
}

/** Aba *Tempo por etapa* (P11): uma linha por papel, na ordem do quadro. */
export interface StageTimeTab {
    rows: { role: StageRoleSlug; label: string; stages: string[]; tasks: number; total_days: number; average_days: number }[];
}

export interface HoursRow {
    task: TaskRef;
    developer: string | null;
    stage: StageRef;
    archived: boolean;
    estimated: number | null;
    logged: number;
    /** Lançadas − previstas; null sem previstas. */
    difference: number | null;
    /** Dias úteis em desenvolvimento × carga do dev (R17); null sem dev ou sem carga. */
    development_hours: number | null;
}

/** Aba *Horas* (P27, P30, R17). O total de previstas não conta a arquivada (F7 §10.5). */
export interface HoursTab {
    rows: HoursRow[];
    totals: { estimated: number; logged: number; difference: number; development_hours: number };
}

export interface PersonRow {
    /** null = a linha *Sem dev*, sempre a última. */
    person: { id: number; name: string } | null;
    daily_hours: number | null;
    tasks: number;
    estimated: number;
    logged: number;
    development_hours: number;
    late: number;
    paused: number;
}

/** Aba *Por pessoa* (P53, F7 §10.4). */
export interface PeopleTab {
    rows: PersonRow[];
}

/** Aba *Qualidade* (P25). */
export interface QualityTab {
    total: number;
    rows: { task: TaskRef; origin: TaskRef; created_on: string; stage: StageRef; archived: boolean }[];
}

export interface MyTaskRow {
    task: TaskRef;
    role: 'developer' | 'qa' | 'both';
    stage: StageRef;
    due_on: string | null;
    missing: DueMissing[];
    /** null sem data prevista; 0 = no prazo. */
    delay_days: number | null;
    estimated: number | null;
    logged_by_me: number;
    /** Só onde sou dev. */
    development_hours: number | null;
}

/** Aba *Minhas tarefas e horas* (P53, P54). */
export interface MyTasksTab {
    rows: MyTaskRow[];
}

/** O que `/projetos/{id}/indicadores` recebe: só a aba pedida vem preenchida. */
export interface IndicatorsPageData {
    project: IndicatorsProject;
    tabs: IndicatorsTab[];
    filters: IndicatorsFilters;
    options: IndicatorsOptions;
    today: string;
    situation: SituationTab | null;
    stage_time: StageTimeTab | null;
    hours: HoursTab | null;
    people: PeopleTab | null;
    quality: QualityTab | null;
    mine: MyTasksTab | null;
}

/** Uma etapa como estava no movimento; `removed` = foi removida do quadro depois (P12). */
export interface HistoryStage {
    name: string;
    role: StageRoleSlug;
    removed: boolean;
}

/** Um movimento da linha do tempo (F7 §02). A primeira linha é a criação (`from` nulo). */
export interface HistoryMovement {
    id: number;
    at: string;
    by: { name: string; deactivated: boolean };
    /** "Tela" ou "IA: Claude" (IA11, R14). */
    channel: string;
    from: HistoryStage | null;
    to: HistoryStage;
    /** Dias úteis na etapa de destino até o movimento seguinte; `open` = até agora. */
    days: number | null;
    open: boolean;
    rework: { origin: string | null; reason: string | null; text: string | null } | null;
    pause_reason: string | null;
}

/** O resumo no topo da linha do tempo (F7 §02). */
export interface HistorySummary {
    stage: { name: string; role: StageRoleSlug; since: string | null };
    due_on: string | null;
    missing: DueMissing[];
    /** null em card pronto ou arquivado (F7 §10.6). */
    delay_days: number | null;
    done_on: string | null;
    estimated_hours: number | null;
    logged_hours: number;
    development_days: number;
    development_hours: number | null;
    by_role: { role: StageRoleSlug; label: string; days: number }[];
    origin: TaskRef | null;
    production_bugs: TaskRef[];
    archived: { on: string | null; reason: string | null; by: string | null } | null;
}

export interface TaskHistoryPageData {
    project: { id: number; key_prefix: string; name: string; client: string };
    task: TaskRef;
    summary: HistorySummary;
    movements: HistoryMovement[];
    /** *Ver na auditoria*, só para o Administrador (F7 §04). */
    audit_url: string | null;
}
