/** Dados de exemplo dos indicadores e do histórico (F7), no formato do IndicatorsScreen / TaskHistoryScreen. */
import type {
    HistoryMovement,
    HistorySummary,
    HoursTab,
    IndicatorsFilters,
    IndicatorsOptions,
    IndicatorsProject,
    MyTasksTab,
    PeopleTab,
    QualityTab,
    SituationTab,
    StageRef,
    StageTimeTab,
    TaskHistoryPageData,
} from '@/types/indicadores';
import type { TaskRef } from '@/types/quadro';

export const INDICATORS_PROJECT: IndicatorsProject = {
    id: 1,
    key_prefix: 'SGM',
    name: 'Gestão de multas',
    client: '[Exemplo] Rezende Bouw',
    finished: false,
};

const ref = (number: number, title: string): TaskRef => ({
    id: number,
    key: `SGM-${number}`,
    title,
    url: `/projetos/1/cards/SGM-${number}`,
});

const stage = (id: number, name: string, role: StageRef['role']): StageRef => ({ id, name, role });
const BACKLOG = stage(2, 'Backlog', 'queue');
const PAUSED = stage(4, 'Paused', 'paused');
const DOING = stage(5, 'Doing', 'development');
const REVIEW = stage(6, 'Code-review', 'code_review');
const DONE = stage(8, 'Done', 'done');

export const INDICATORS_OPTIONS: IndicatorsOptions = {
    sprints: [
        { id: 5, name: 'Sprint 5', open: true },
        { id: 4, name: 'Sprint 4', open: false },
        { id: 3, name: 'Sprint 3', open: false },
    ],
    developers: [
        { id: 2, name: 'Ana Lima' },
        { id: 3, name: 'Bruno Costa' },
        { id: 4, name: 'Quésia Rocha' },
    ],
    types: [
        { id: 1, label: 'Bug' },
        { id: 2, label: 'Desenvolvimento' },
        { id: 3, label: 'Melhoria' },
    ],
    areas: [
        { id: 1, name: 'Financeiro' },
        { id: 2, name: 'Portal' },
    ],
};

export const INDICATORS_FILTERS: IndicatorsFilters = {
    tab: 'situacao',
    search: '',
    sprint: '5',
    developer_id: null,
    type_id: null,
    area_id: null,
    due: null,
    until: null,
};

export const SITUATION: SituationTab = {
    by_stage: [
        { ...stage(1, 'Support', 'support'), count: 1 },
        { ...BACKLOG, count: 14 },
        { ...stage(3, 'To do', 'sprint'), count: 3 },
        { ...PAUSED, count: 1 },
        { ...DOING, count: 4 },
        { ...REVIEW, count: 2 },
        { ...stage(7, 'In test', 'test'), count: 1 },
        { ...DONE, count: 9 },
    ],
    late: [
        { task: ref(17, 'Prazo de recurso por órgão'), developer: 'Ana Lima', due_on: '2026-10-09', paused_days: 1.5, delay_days: 1.5 },
        { task: ref(12, 'Importar multas do Detran'), developer: 'Bruno Costa', due_on: '2026-10-13', paused_days: 0, delay_days: 1 },
    ],
    upcoming: [
        { task: ref(21, 'Relatório de medição'), developer: 'Quésia Rocha', due_on: '2026-10-15' },
        { task: ref(18, 'Filtro por placa'), developer: 'Ana Lima', due_on: '2026-10-16' },
    ],
    paused: [
        {
            task: ref(9, 'Integração com o banco'),
            developer: 'Bruno Costa',
            reason: 'Aguardando o token do cliente',
            since: '13/10/2026 09:40',
            by: 'Rafael Garcia',
        },
    ],
    without_due: [{ task: ref(23, 'Ajuste no cadastro de veículos'), missing: ['developer', 'hours'] }],
};

export const SITUATION_VAZIA: SituationTab = {
    by_stage: SITUATION.by_stage.map((row) => ({ ...row, count: 0 })),
    late: [],
    upcoming: [],
    paused: [],
    without_due: [],
};

export const STAGE_TIME: StageTimeTab = {
    rows: [
        { role: 'queue', label: 'Fila', stages: ['Backlog'], tasks: 12, total_days: 58, average_days: 4.8 },
        { role: 'sprint', label: 'Sprint', stages: ['To do'], tasks: 12, total_days: 9.5, average_days: 0.8 },
        { role: 'paused', label: 'Pausa', stages: ['Paused'], tasks: 3, total_days: 4.5, average_days: 1.5 },
        { role: 'development', label: 'Desenvolvimento', stages: ['Doing'], tasks: 12, total_days: 31, average_days: 2.6 },
        { role: 'code_review', label: 'Code review', stages: ['Code-review'], tasks: 10, total_days: 11, average_days: 1.1 },
        { role: 'test', label: 'Teste', stages: ['In test', 'Homologação'], tasks: 8, total_days: 12.5, average_days: 1.6 },
    ],
};

export const HOURS: HoursTab = {
    rows: [
        {
            task: ref(17, 'Prazo de recurso por órgão'),
            developer: 'Ana Lima',
            stage: DOING,
            archived: false,
            estimated: 16,
            logged: 18.5,
            difference: 2.5,
            development_hours: 44,
        },
        {
            task: ref(18, 'Filtro por placa'),
            developer: 'Ana Lima',
            stage: REVIEW,
            archived: false,
            estimated: 8,
            logged: 6,
            difference: -2,
            development_hours: 12,
        },
        {
            task: ref(21, 'Relatório de medição'),
            developer: 'Quésia Rocha',
            stage: DOING,
            archived: false,
            estimated: 4,
            logged: 0,
            difference: -4,
            development_hours: 6,
        },
        {
            task: ref(15, 'Cadastro duplicado'),
            developer: 'Bruno Costa',
            stage: BACKLOG,
            archived: true,
            estimated: 40,
            logged: 3,
            difference: -37,
            development_hours: 0,
        },
        {
            task: ref(23, 'Ajuste no cadastro de veículos'),
            developer: null,
            stage: DOING,
            archived: false,
            estimated: null,
            logged: 2,
            difference: null,
            development_hours: null,
        },
    ],
    totals: { estimated: 28, logged: 29.5, difference: 1.5, development_hours: 62 },
};

export const PEOPLE: PeopleTab = {
    rows: [
        {
            person: { id: 2, name: 'Ana Lima' },
            daily_hours: 8,
            tasks: 4,
            estimated: 34,
            logged: 27,
            development_hours: 56,
            late: 1,
            paused: 1,
        },
        {
            person: { id: 3, name: 'Bruno Costa' },
            daily_hours: null,
            tasks: 2,
            estimated: 12,
            logged: 3,
            development_hours: 0,
            late: 1,
            paused: 0,
        },
        {
            person: { id: 4, name: 'Quésia Rocha' },
            daily_hours: 4,
            tasks: 1,
            estimated: 4,
            logged: 2,
            development_hours: 6,
            late: 0,
            paused: 0,
        },
        { person: null, daily_hours: null, tasks: 3, estimated: 5, logged: 0, development_hours: 0, late: 0, paused: 0 },
    ],
};

export const QUALITY: QualityTab = {
    total: 2,
    rows: [
        {
            task: ref(24, 'Boleto sai com valor zerado'),
            origin: ref(11, 'Emissão de boleto'),
            created_on: '2026-10-10',
            stage: DOING,
            archived: false,
        },
        {
            task: ref(19, 'Placa Mercosul não importa'),
            origin: ref(12, 'Importar multas do Detran'),
            created_on: '2026-10-02',
            stage: DONE,
            archived: false,
        },
    ],
};

export const MY_TASKS: MyTasksTab = {
    rows: [
        {
            task: ref(17, 'Prazo de recurso por órgão'),
            role: 'developer',
            stage: DOING,
            due_on: '2026-10-09',
            missing: [],
            delay_days: 1.5,
            estimated: 16,
            logged_by_me: 18.5,
            development_hours: 44,
        },
        {
            task: ref(21, 'Relatório de medição'),
            role: 'qa',
            stage: DOING,
            due_on: '2026-10-15',
            missing: [],
            delay_days: 0,
            estimated: 4,
            logged_by_me: 0,
            development_hours: null,
        },
        {
            task: ref(18, 'Filtro por placa'),
            role: 'developer',
            stage: REVIEW,
            due_on: '2026-10-16',
            missing: [],
            delay_days: 0,
            estimated: 8,
            logged_by_me: 6,
            development_hours: 12,
        },
        {
            task: ref(9, 'Integração com o banco'),
            role: 'both',
            stage: PAUSED,
            due_on: null,
            missing: ['hours'],
            delay_days: null,
            estimated: null,
            logged_by_me: 1,
            development_hours: 0,
        },
    ],
};

export const HISTORY_SUMMARY: HistorySummary = {
    stage: { name: 'Doing', role: 'development', since: '08/10/2026 09:00' },
    due_on: '2026-10-09',
    missing: [],
    delay_days: 1.5,
    done_on: null,
    estimated_hours: 16,
    logged_hours: 18.5,
    development_days: 5.5,
    development_hours: 44,
    by_role: [
        { role: 'queue', label: 'Fila', days: 1 },
        { role: 'sprint', label: 'Sprint', days: 0.5 },
        { role: 'development', label: 'Desenvolvimento', days: 5.5 },
        { role: 'code_review', label: 'Code review', days: 1 },
        { role: 'paused', label: 'Pausa', days: 1.5 },
    ],
    origin: null,
    production_bugs: [ref(24, 'Boleto sai com valor zerado')],
    archived: null,
};

export const HISTORY_MOVEMENTS: HistoryMovement[] = [
    {
        id: 1,
        at: '05/10/2026 09:00',
        by: { name: 'Rafael Garcia', deactivated: false },
        channel: 'Tela',
        from: null,
        to: { name: 'Backlog', role: 'queue', removed: false },
        days: 1,
        open: false,
        rework: null,
        pause_reason: null,
    },
    {
        id: 2,
        at: '06/10/2026 09:00',
        by: { name: 'Rafael Garcia', deactivated: false },
        channel: 'Tela',
        from: { name: 'Backlog', role: 'queue', removed: false },
        to: { name: 'To do', role: 'sprint', removed: false },
        days: 0.5,
        open: false,
        rework: null,
        pause_reason: null,
    },
    {
        id: 3,
        at: '06/10/2026 21:00',
        by: { name: 'Bruno Costa', deactivated: true },
        channel: 'Tela',
        from: { name: 'To do', role: 'sprint', removed: false },
        to: { name: 'Doing', role: 'development', removed: false },
        days: 1,
        open: false,
        rework: null,
        pause_reason: null,
    },
    {
        id: 4,
        at: '07/10/2026 21:00',
        by: { name: 'Ana Lima', deactivated: false },
        channel: 'IA: Claude',
        from: { name: 'Doing', role: 'development', removed: false },
        to: { name: 'Code-review', role: 'code_review', removed: false },
        days: 1,
        open: false,
        rework: null,
        pause_reason: null,
    },
    {
        id: 5,
        at: '08/10/2026 21:00',
        by: { name: 'Ana Lima', deactivated: false },
        channel: 'Tela',
        from: { name: 'Code-review', role: 'code_review', removed: false },
        to: { name: 'Doing', role: 'development', removed: false },
        days: 1.5,
        open: false,
        rework: { origin: 'Code review', reason: 'Outro', text: 'Faltou o teste do prazo' },
        pause_reason: null,
    },
    {
        id: 6,
        at: '12/10/2026 09:00',
        by: { name: 'Rafael Garcia', deactivated: false },
        channel: 'Tela',
        from: { name: 'Doing', role: 'development', removed: false },
        to: { name: 'Em espera', role: 'paused', removed: true },
        days: 1.5,
        open: false,
        rework: null,
        pause_reason: 'Aguardando o token do cliente',
    },
    {
        id: 7,
        at: '13/10/2026 21:00',
        by: { name: 'Rafael Garcia', deactivated: false },
        channel: 'Tela',
        from: { name: 'Em espera', role: 'paused', removed: true },
        to: { name: 'Doing', role: 'development', removed: false },
        days: 3,
        open: true,
        rework: null,
        pause_reason: null,
    },
];

export const TASK_HISTORY: TaskHistoryPageData = {
    project: { id: 1, key_prefix: 'SGM', name: 'Gestão de multas', client: '[Exemplo] Rezende Bouw' },
    task: ref(17, 'Prazo de recurso por órgão'),
    summary: HISTORY_SUMMARY,
    movements: HISTORY_MOVEMENTS,
    audit_url: '/admin/audit-logs?type=task&id=17',
};
