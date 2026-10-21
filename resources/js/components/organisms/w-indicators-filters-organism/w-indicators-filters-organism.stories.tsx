import type { Meta, StoryObj } from '@storybook/react-vite';

import { INDICATORS_FILTERS, INDICATORS_OPTIONS } from '../../../../../.storybook/fixtures-indicadores';
import { WIndicatorsFiltersOrganism } from './w-indicators-filters-organism';

const meta = {
    title: 'Organisms/Indicadores/Filtros',
    component: WIndicatorsFiltersOrganism,
    tags: ['autodocs'],
    args: { projectId: 1, filters: { ...INDICATORS_FILTERS, tab: 'horas' }, options: INDICATORS_OPTIONS, variant: 'project' },
    parameters: { inertia: { url: '/projetos/1/indicadores?aba=horas' } },
} satisfies Meta<typeof WIndicatorsFiltersOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const DoProjeto: Story = {};
export const MinhasTarefas: Story = {
    args: { variant: 'mine', filters: { ...INDICATORS_FILTERS, tab: 'minhas', due: 'until', until: '2026-10-16' } },
};
