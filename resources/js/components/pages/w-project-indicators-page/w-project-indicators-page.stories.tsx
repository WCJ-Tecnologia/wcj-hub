import type { Meta, StoryObj } from '@storybook/react-vite';

import { asUser, DEVELOPER } from '../../../../../.storybook/fixtures';
import { HOURS, INDICATORS_FILTERS, INDICATORS_OPTIONS, INDICATORS_PROJECT, MY_TASKS, PEOPLE, QUALITY, SITUATION, STAGE_TIME } from '../../../../../.storybook/fixtures-indicadores';
import { WProjectIndicatorsPage } from './w-project-indicators-page';

const ALL_TABS = ['situacao', 'tempo', 'horas', 'pessoas', 'qualidade', 'minhas'] as const;
const EMPTY = { situation: null, stage_time: null, hours: null, people: null, quality: null, mine: null };

const meta = {
    title: 'Pages/Indicadores do projeto',
    component: WProjectIndicatorsPage,
    tags: ['autodocs'],
    args: {
        project: INDICATORS_PROJECT,
        tabs: [...ALL_TABS],
        filters: INDICATORS_FILTERS,
        options: INDICATORS_OPTIONS,
        today: '2026-10-14',
        ...EMPTY,
        situation: SITUATION,
    },
    parameters: { layout: 'fullscreen', inertia: { url: '/projetos/1/indicadores' } },
} satisfies Meta<typeof WProjectIndicatorsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Situacao: Story = {};
export const TempoPorEtapa: Story = { args: { filters: { ...INDICATORS_FILTERS, tab: 'tempo' }, ...EMPTY, stage_time: STAGE_TIME } };
export const Horas: Story = { args: { filters: { ...INDICATORS_FILTERS, tab: 'horas' }, ...EMPTY, hours: HOURS } };
export const PorPessoa: Story = { args: { filters: { ...INDICATORS_FILTERS, tab: 'pessoas' }, ...EMPTY, people: PEOPLE } };
export const Qualidade: Story = { args: { filters: { ...INDICATORS_FILTERS, tab: 'qualidade' }, ...EMPTY, quality: QUALITY } };
export const DevSoMinhasTarefas: Story = {
    args: { tabs: ['minhas'], filters: { ...INDICATORS_FILTERS, tab: 'minhas' }, ...EMPTY, mine: MY_TASKS },
    parameters: { inertia: { url: '/projetos/1/indicadores?aba=minhas', props: asUser(DEVELOPER) } },
};
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
