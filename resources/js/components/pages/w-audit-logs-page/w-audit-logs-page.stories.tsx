import type { Meta, StoryObj } from '@storybook/react-vite';

import { AUDIT_FILTERS, AUDIT_OPTIONS, LOGS, onePage } from '../../../../../.storybook/fixtures';
import { WAuditLogsPage } from './w-audit-logs-page';

const meta = {
    title: 'Pages/Auditoria',
    component: WAuditLogsPage,
    tags: ['autodocs'],
    argTypes: { logs: { control: 'object' }, filters: { control: 'object' }, options: { control: 'object' }, record: { control: 'text' } },
    args: { logs: onePage(LOGS), filters: AUDIT_FILTERS, options: AUDIT_OPTIONS, record: null },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/audit-logs' } },
} satisfies Meta<typeof WAuditLogsPage>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const SoNegados: Story = {
    args: { logs: onePage(LOGS.filter((log) => log.outcome === 'denied')), filters: { ...AUDIT_FILTERS, outcome: 'denied' } },
};
export const HistoricoDeUmaFicha: Story = {
    args: {
        logs: onePage(LOGS.filter((log) => log.auditable_id === 3)),
        filters: { ...AUDIT_FILTERS, from: null, type: 'user', id: 3 },
        record: 'Usuário Carla Dias',
    },
};
export const Vazia: Story = { args: { logs: onePage([]) } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
