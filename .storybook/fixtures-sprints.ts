/** Dados de exemplo da tela de sprints (F5), no formato do SprintScreen / SprintPresenter. */
import type { SelectedSprint, SprintClosing, SprintFields, SprintFilters, SprintProject, SprintRow, SprintTaskRow } from '@/types/sprints';

export const SPRINT_PROJECT: SprintProject = {
    id: 1,
    key_prefix: 'SGM',
    name: 'Gestão de multas',
    client: '[Exemplo] Rezende Bouw',
    finished: false,
    sprint_days: 10,
};

const CAN_ALL = { update: true, close: false, reopen: false, delete: false };

export const SPRINT_ABERTA: SprintRow = {
    id: 5,
    name: 'Sprint 5',
    goal: 'Fechar o fluxo de recurso de multa',
    starts_on: '2026-10-06',
    ends_on: '2026-10-19',
    business_days: 10,
    open: true,
    closed_at: null,
    tasks_done: 2,
    tasks_total: 6,
    can: { ...CAN_ALL, close: true },
};

export const SPRINTS_ENCERRADAS: SprintRow[] = [
    {
        id: 4,
        name: 'Sprint 4',
        goal: 'Login novo',
        starts_on: '2026-09-22',
        ends_on: '2026-10-05',
        business_days: 10,
        open: false,
        closed_at: '05/10/2026',
        tasks_done: 7,
        tasks_total: 7,
        can: { ...CAN_ALL, reopen: false },
    },
    {
        id: 3,
        name: 'Sprint 3',
        goal: null,
        starts_on: '2026-09-08',
        ends_on: '2026-09-21',
        business_days: 10,
        open: false,
        closed_at: '21/09/2026',
        tasks_done: 5,
        tasks_total: 5,
        can: CAN_ALL,
    },
];

export const SPRINTS: SprintRow[] = [SPRINT_ABERTA, ...SPRINTS_ENCERRADAS];

const stage = (id: number, name: string, role: SprintTaskRow['stage']['role']) => ({ id, name, role });
const ana = { id: 2, name: 'Ana Souza' };
const bruno = { id: 3, name: 'Bruno Lima' };
const quesia = { id: 4, name: 'Quésia Reis' };
const task = (number: number, title: string) => ({ id: number, key: `SGM-${number}`, title, url: `/projetos/1/cards/SGM-${number}` });

export const SPRINT_TASKS: SprintTaskRow[] = [
    {
        ...task(21, 'Tela de recurso de multa'),
        stage: stage(3, 'To do', 'sprint'),
        developer: ana,
        qa: quesia,
        estimated_hours: 16,
        proposed_hours: null,
        logged_hours: 0,
        due_on: null,
    },
    {
        ...task(22, 'Anexar documento ao recurso'),
        stage: stage(3, 'To do', 'sprint'),
        developer: null,
        qa: null,
        estimated_hours: null,
        proposed_hours: null,
        logged_hours: 0,
        due_on: null,
    },
    {
        ...task(17, 'Prazo de recurso por órgão'),
        stage: stage(5, 'Doing', 'development'),
        developer: ana,
        qa: quesia,
        estimated_hours: 16,
        proposed_hours: 24,
        logged_hours: 11.5,
        due_on: '2026-10-09',
    },
    {
        ...task(18, 'Filtro por placa'),
        stage: stage(5, 'Doing', 'development'),
        developer: bruno,
        qa: null,
        estimated_hours: 50,
        proposed_hours: null,
        logged_hours: 4,
        due_on: '2026-10-22',
    },
    {
        ...task(15, 'Exportar multas do mês'),
        stage: stage(8, 'Done', 'done'),
        developer: bruno,
        qa: quesia,
        estimated_hours: 8,
        proposed_hours: null,
        logged_hours: 9,
        due_on: '2026-10-07',
    },
    {
        ...task(14, 'Ajuste no e-mail de aviso'),
        stage: stage(8, 'Done', 'done'),
        developer: ana,
        qa: quesia,
        estimated_hours: 2,
        proposed_hours: null,
        logged_hours: 2,
        due_on: '2026-10-06',
    },
];

export const SELECTED_SPRINT: SelectedSprint = {
    ...SPRINT_ABERTA,
    tasks: SPRINT_TASKS,
    capacity: {
        business_days: 10,
        developers: [
            { id: 3, name: 'Bruno Lima', daily_hours: 4, capacity: 40, remaining: 46, difference: -6 },
            { id: 2, name: 'Ana Souza', daily_hours: 8, capacity: 80, remaining: 20.5, difference: 59.5 },
            { id: 6, name: 'Caio Prado', daily_hours: null, capacity: null, remaining: 0, difference: null },
        ],
        without_developer: { tasks: 1, hours: 0 },
        without_hours: { tasks: 1 },
    },
    options: {
        developers: [ana, bruno],
        stages: [
            stage(1, 'Support', 'support'),
            stage(2, 'Backlog', 'queue'),
            stage(3, 'To do', 'sprint'),
            stage(5, 'Doing', 'development'),
            stage(8, 'Done', 'done'),
        ].map(({ id, name }) => ({ id, name })),
    },
};

export const SPRINT_PROPOSTA: SprintFields = { name: 'Sprint 6', goal: '', starts_on: '2026-10-20', ends_on: '2026-11-02' };

export const SPRINT_CLOSING: SprintClosing = {
    sprint_id: 5,
    tasks: SPRINT_TASKS.filter((row) => row.stage.role !== 'done').map((row) => ({
        id: row.id,
        key: row.key,
        title: row.title,
        url: row.url,
        stage: row.stage,
        developer: row.developer?.name ?? null,
    })),
    next: SPRINT_PROPOSTA,
};

export const SPRINT_FILTERS: SprintFilters = { sprint: null, search: '', status: null, developer_id: null, stage_id: null, quick: null };
