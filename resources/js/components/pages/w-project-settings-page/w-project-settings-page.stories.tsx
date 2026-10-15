import type { Meta, StoryObj } from '@storybook/react-vite';

import { asUser, DEVELOPER } from '../../../../../.storybook/fixtures';
import {
    AREAS,
    MEMBERS,
    PEOPLE_OPTIONS,
    SETTINGS_FILTERS,
    SETTINGS_PROJECT,
    STAGE_ROLES,
    STAGES,
} from '../../../../../.storybook/fixtures-configuracao';
import { WProjectSettingsPage } from './w-project-settings-page';

const meta = {
    title: 'Pages/Configuração do projeto',
    component: WProjectSettingsPage,
    tags: ['autodocs'],
    argTypes: {
        project: { control: 'object' },
        members: { control: 'object' },
        stages: { control: 'object' },
        areas: { control: 'object' },
    },
    args: {
        project: SETTINGS_PROJECT,
        members: MEMBERS,
        stages: STAGES,
        areas: AREAS.filter((area) => area.active),
        options: { people: PEOPLE_OPTIONS, stage_roles: STAGE_ROLES },
        filters: SETTINGS_FILTERS,
    },
    parameters: { layout: 'fullscreen', inertia: { url: '/projetos/1/configuracao' } },
} satisfies Meta<typeof WProjectSettingsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Pessoas: Story = {};
export const PessoasVazia: Story = { args: { members: [] } };
export const Etapas: Story = { args: { filters: { ...SETTINGS_FILTERS, tab: 'etapas' } } };
export const Areas: Story = { args: { filters: { ...SETTINGS_FILTERS, tab: 'areas' } } };
export const AreasComDesativadas: Story = { args: { areas: AREAS, filters: { tab: 'areas', show_inactive: true } } };
export const AreasVazia: Story = { args: { areas: [], filters: { ...SETTINGS_FILTERS, tab: 'areas' } } };
export const Padroes: Story = { args: { filters: { ...SETTINGS_FILTERS, tab: 'padroes' } } };
export const DevSoLeitura: Story = {
    args: { project: { ...SETTINGS_PROJECT, can: { manage: false } }, areas: null, options: null },
    parameters: { inertia: { url: '/projetos/1/configuracao', props: asUser(DEVELOPER) } },
};
export const Celular: Story = {
    args: { filters: { ...SETTINGS_FILTERS, tab: 'etapas' } },
    globals: { viewport: { value: 'mobile2', isRotated: false } },
};
