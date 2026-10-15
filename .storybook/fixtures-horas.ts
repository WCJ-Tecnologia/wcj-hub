/** Dados de exemplo de Minhas horas (F5), no formato do TimeEntryPresenter. */
import type { TimeEntryFilters, TimeEntryRow, TimeEntryTaskOption, TimeEntryTotals } from '@/types/horas';

const sgm = { id: 1, key_prefix: 'SGM', name: 'Gestão de multas' };
const rez = { id: 2, key_prefix: 'REZ', name: 'Portal Rezende' };
const ref = (prefix: string, projectId: number, number: number, title: string) => ({
    id: projectId * 100 + number,
    key: `${prefix}-${number}`,
    title,
    url: `/projetos/${projectId}/cards/${prefix}-${number}`,
});
const can = { update: true, delete: true };

export const TIME_ENTRIES: TimeEntryRow[] = [
    { id: 11, worked_on: '2026-10-15', hours: 2.5, project: sgm, task: ref('SGM', 1, 17, 'Prazo de recurso por órgão'), can },
    { id: 10, worked_on: '2026-10-15', hours: 4, project: rez, task: ref('REZ', 2, 3, 'Relatório de medição'), can },
    { id: 9, worked_on: '2026-10-14', hours: 6, project: sgm, task: ref('SGM', 1, 17, 'Prazo de recurso por órgão'), can },
    { id: 8, worked_on: '2026-10-13', hours: 1.5, project: sgm, task: ref('SGM', 1, 18, 'Filtro por placa'), can },
];

export const TIME_ENTRY_TOTALS: TimeEntryTotals = {
    days: [
        { date: '2026-10-15', hours: 6.5 },
        { date: '2026-10-14', hours: 6 },
        { date: '2026-10-13', hours: 1.5 },
    ],
    total: 14,
};

export const TIME_ENTRY_TASKS: TimeEntryTaskOption[] = [
    { ...ref('SGM', 1, 17, 'Prazo de recurso por órgão'), project_id: 1, parent_key: null, has_children: false, mine: true },
    { ...ref('SGM', 1, 18, 'Filtro por placa'), project_id: 1, parent_key: null, has_children: false, mine: true },
    { ...ref('REZ', 2, 3, 'Relatório de medição'), project_id: 2, parent_key: null, has_children: false, mine: false },
];

export const TIME_ENTRY_PROJECTS = [sgm, rez];

export const TIME_ENTRY_FILTERS: TimeEntryFilters = { from: '2026-10-12', to: '2026-10-18', project_id: null, search: '' };
