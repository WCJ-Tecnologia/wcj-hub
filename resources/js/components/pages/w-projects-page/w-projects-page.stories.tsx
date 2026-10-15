import type { Meta, StoryObj } from '@storybook/react-vite';

import { asUser, CLIENTS, COUNTRIES, DEVELOPER, onePage, PROJECT_FILTERS, PROJECTS, USERS } from '../../../../../.storybook/fixtures';
import { WProjectsPage } from './w-projects-page';

const OPTIONS = {
    clients: CLIENTS.map(({ id, name }) => ({ id, name })),
    managers: USERS.filter((user) => user.role_id !== 3).map(({ id, name }) => ({ id, name })),
};

const meta = {
    title: 'Pages/Projetos',
    component: WProjectsPage,
    tags: ['autodocs'],
    argTypes: {
        projects: { control: 'object' },
        filters: { control: 'object' },
        filterOptions: { control: 'object' },
        formOptions: { control: 'object' },
    },
    args: {
        projects: onePage(PROJECTS.filter((project) => project.finished_at === null)),
        filters: PROJECT_FILTERS,
        filterOptions: OPTIONS,
        formOptions: OPTIONS,
        today: '2026-10-09',
        countries: COUNTRIES,
    },
    parameters: { layout: 'fullscreen', inertia: { url: '/projetos' } },
} satisfies Meta<typeof WProjectsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComEncerrados: Story = { args: { projects: onePage(PROJECTS), filters: { ...PROJECT_FILTERS, finished: true } } };
export const Vazia: Story = { args: { projects: onePage([]) } };
export const DevSemProjeto: Story = {
    args: { projects: onePage([]), formOptions: null },
    parameters: { inertia: { url: '/projetos', props: asUser(DEVELOPER) } },
};
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
