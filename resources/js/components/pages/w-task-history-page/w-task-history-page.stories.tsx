import type { Meta, StoryObj } from '@storybook/react-vite';

import { asUser, DEVELOPER } from '../../../../../.storybook/fixtures';
import { HISTORY_MOVEMENTS, TASK_HISTORY } from '../../../../../.storybook/fixtures-indicadores';
import { WTaskHistoryPage } from './w-task-history-page';

const meta = {
    title: 'Pages/Histórico do card',
    component: WTaskHistoryPage,
    tags: ['autodocs'],
    args: TASK_HISTORY,
    parameters: { layout: 'fullscreen', inertia: { url: '/projetos/1/cards/SGM-17/historico' } },
} satisfies Meta<typeof WTaskHistoryPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Administrador: Story = {};
export const Dev: Story = { args: { audit_url: null }, parameters: { inertia: { props: asUser(DEVELOPER) } } };
export const SoACriacao: Story = {
    args: {
        audit_url: null,
        movements: [{ ...HISTORY_MOVEMENTS[0], days: 6, open: true }],
        summary: { ...TASK_HISTORY.summary, stage: { name: 'Backlog', role: 'queue', since: '2026-10-05 09:00' }, due_on: null, missing: [], delay_days: null, development_hours: null, development_days: 0, by_role: [{ role: 'queue', label: 'Fila', days: 6 }], production_bugs: [] },
    },
};
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
