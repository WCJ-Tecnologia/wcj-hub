import type { Meta, StoryObj } from '@storybook/react-vite';

import { NOTIFICATIONS, NOTIFICATIONS_VAZIO } from '../../../../../.storybook/fixtures';
import { WNotificationsBellOrganism } from './w-notifications-bell-organism';

const meta = {
    title: 'Organisms/Notifications Bell',
    component: WNotificationsBellOrganism,
    tags: ['autodocs'],
    parameters: {
        layout: 'centered',
        docs: {
            description: {
                component:
                    'Sino de avisos do topbar (F3 §07): dev definido, QA definido e retrabalho, com link para o card. Marcar como lida vai ao servidor (rotas propostas pelo kit) e nunca é otimista.',
            },
        },
    },
    argTypes: {
        notifications: { control: 'object' },
        defaultOpen: { control: 'boolean' },
        className: { control: false },
    },
    // O menu abre alinhado à direita do botão (align="end"): o botão fica na ponta direita de 20rem.
    decorators: [
        (Story) => (
            <div className="flex w-80 justify-end">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WNotificationsBellOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Vazio: Story = { args: { notifications: NOTIFICATIONS_VAZIO, defaultOpen: true } };

export const ComNaoLidos: Story = { args: { notifications: NOTIFICATIONS, defaultOpen: true } };

export const TodosLidos: Story = {
    args: {
        notifications: {
            unread_count: 0,
            items: NOTIFICATIONS.items.map((item) => ({ ...item, read_at: item.read_at ?? '2026-10-14 17:00' })),
        },
    },
};

export const MuitosNaoLidos: Story = { args: { notifications: { ...NOTIFICATIONS, unread_count: 12 } } };
