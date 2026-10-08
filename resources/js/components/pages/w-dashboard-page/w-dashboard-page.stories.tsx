import type { Meta, StoryObj } from '@storybook/react-vite';

import { WDashboardPage } from './w-dashboard-page';

const ACTIVITY = [
    { id: 12, user: 'Ana Souza', action_label: 'Alterado', entity: 'user', entity_id: 3, created_at: '02/10/2026 14:10' },
    { id: 11, user: 'Ana Souza', action_label: 'Criado', entity: 'user', entity_id: 4, created_at: '02/10/2026 11:02' },
    { id: 10, user: 'Bruno Lima', action_label: 'Login', entity: 'user', entity_id: 2, created_at: '02/10/2026 08:57' },
];

const meta = {
    title: 'Pages/Dashboard',
    component: WDashboardPage,
    tags: ['autodocs'],
    argTypes: { summary: { control: 'object' }, activity: { control: 'object' } },
    args: { summary: { users_active: 12, users_total: 14 }, activity: ACTIVITY },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/dashboard' } },
} satisfies Meta<typeof WDashboardPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const SemAtividade: Story = { args: { activity: [] } };
