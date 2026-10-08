import type { Meta, StoryObj } from '@storybook/react-vite';

import { LOGS, onePage } from '../../../../../.storybook/fixtures';
import { WAuditLogsPage } from './w-audit-logs-page';

const ACTIONS = [
    { value: 'created', label: 'Criado' },
    { value: 'updated', label: 'Alterado' },
    { value: 'deleted', label: 'Excluído' },
    { value: 'login', label: 'Login' },
];

const meta = {
    title: 'Pages/Logs de auditoria',
    component: WAuditLogsPage,
    tags: ['autodocs'],
    argTypes: { logs: { control: 'object' }, filters: { control: 'object' }, actionOptions: { control: 'object' } },
    args: { logs: onePage(LOGS), filters: { action: null, from: null, to: null }, actionOptions: ACTIONS },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/audit-logs' } },
} satisfies Meta<typeof WAuditLogsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const FiltradoPorAcao: Story = { args: { logs: onePage(LOGS.slice(0, 1)), filters: { action: 'updated', from: null, to: null } } };
export const Vazia: Story = { args: { logs: onePage([]) } };
