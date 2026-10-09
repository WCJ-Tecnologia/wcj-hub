import type { Meta, StoryObj } from '@storybook/react-vite';
import { UserCheck, Users } from 'lucide-react';

import { WStatCardMolecule } from './w-stat-card-molecule';

const meta = {
    title: 'Molecules/Stat Card',
    component: WStatCardMolecule,
    tags: ['autodocs'],
    argTypes: {
        label: { control: 'text' },
        value: { control: 'text' },
        hint: { control: 'text' },
        tone: { control: 'select', options: ['default', 'primary', 'pending', 'approved', 'rejected'] },
        progress: { control: { type: 'range', min: 0, max: 120 } },
        icon: { control: false },
    },
    args: { label: 'Usuários ativos', value: 12, tone: 'approved', icon: UserCheck },
    decorators: [
        (Story) => (
            <div className="w-64">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WStatCardMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComDica: Story = { args: { label: 'Usuários cadastrados', value: 14, tone: 'primary', icon: Users, hint: '2 inativos' } };
export const ComProgresso: Story = {
    args: { label: 'Capacidade da sprint', value: '86%', tone: 'pending', icon: undefined, progress: 86 },
};
