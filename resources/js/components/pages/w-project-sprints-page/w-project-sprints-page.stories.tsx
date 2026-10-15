import type { Meta, StoryObj } from '@storybook/react-vite';

import { asUser, DEVELOPER } from '../../../../../.storybook/fixtures';
import {
    SELECTED_SPRINT,
    SPRINT_CLOSING,
    SPRINT_FILTERS,
    SPRINT_PROJECT,
    SPRINT_PROPOSTA,
    SPRINTS,
    SPRINTS_ENCERRADAS,
} from '../../../../../.storybook/fixtures-sprints';
import { WProjectSprintsPage } from './w-project-sprints-page';

const meta = {
    title: 'Pages/Sprints do projeto',
    component: WProjectSprintsPage,
    tags: ['autodocs'],
    args: {
        project: SPRINT_PROJECT,
        sprints: SPRINTS,
        selected: SELECTED_SPRINT,
        closing: SPRINT_CLOSING,
        proposal: null,
        can: { manage_tasks: true },
        filters: SPRINT_FILTERS,
        today: '2026-10-15',
    },
    parameters: { layout: 'fullscreen', inertia: { url: '/projetos/1/sprints' } },
} satisfies Meta<typeof WProjectSprintsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const GestorComSprintAberta: Story = {};
export const SemSprintAberta: Story = {
    args: {
        sprints: SPRINTS_ENCERRADAS,
        selected: { ...SELECTED_SPRINT, ...SPRINTS_ENCERRADAS[0], capacity: null, tasks: [] },
        closing: null,
        proposal: SPRINT_PROPOSTA,
    },
};
export const NenhumaSprint: Story = {
    args: { sprints: [], selected: null, closing: null, proposal: { ...SPRINT_PROPOSTA, name: 'Sprint 1' } },
};
export const DevSoLeitura: Story = {
    args: {
        sprints: SPRINTS.map((row) => ({ ...row, can: { update: false, close: false, reopen: false, delete: false } })),
        selected: { ...SELECTED_SPRINT, capacity: null, can: { update: false, close: false, reopen: false, delete: false } },
        closing: null,
        can: { manage_tasks: false },
    },
    parameters: { inertia: { url: '/projetos/1/sprints', props: asUser(DEVELOPER) } },
};
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
