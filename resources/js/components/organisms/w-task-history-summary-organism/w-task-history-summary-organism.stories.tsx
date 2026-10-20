import type { Meta, StoryObj } from '@storybook/react-vite';

import { HISTORY_SUMMARY } from '../../../../../.storybook/fixtures-indicadores';
import { WTaskHistorySummaryOrganism } from './w-task-history-summary-organism';

const meta = {
    title: 'Organisms/Indicadores/Resumo do histórico',
    component: WTaskHistorySummaryOrganism,
    tags: ['autodocs'],
    args: { summary: HISTORY_SUMMARY },
} satisfies Meta<typeof WTaskHistorySummaryOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Atrasada: Story = {};
export const Pronta: Story = {
    args: { summary: { ...HISTORY_SUMMARY, stage: { name: 'Done', role: 'done', since: '2026-10-13 18:00' }, delay_days: null, done_on: '2026-10-13' } },
};
export const SemDataPrevista: Story = {
    args: { summary: { ...HISTORY_SUMMARY, due_on: null, missing: ['developer', 'hours'], delay_days: null, development_hours: null, production_bugs: [] } },
};
export const Arquivada: Story = {
    args: {
        summary: {
            ...HISTORY_SUMMARY,
            delay_days: null,
            origin: { id: 11, key: 'SGM-11', title: 'Emissão de boleto', url: '/projetos/1/cards/SGM-11' },
            production_bugs: [],
            archived: { on: '2026-10-14', reason: 'Duplicada da SGM-11', by: 'Rafael Garcia' },
        },
    },
};
