import type { Meta, StoryObj } from '@storybook/react-vite';

import { WStatusPillAtom } from './w-status-pill-atom';

const meta = {
    title: 'Atoms/Status Pill',
    component: WStatusPillAtom,
    tags: ['autodocs'],
    argTypes: {
        status: { control: 'select', options: [true, false, 'active', 'inactive', 'desconhecido', null] },
        kind: { control: 'inline-radio', options: ['user', 'client'] },
        size: { control: 'inline-radio', options: ['default', 'sm'] },
        label: { control: 'text' },
    },
    args: { status: true, kind: 'user', size: 'default' },
} satisfies Meta<typeof WStatusPillAtom>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Ativo: Story = {};
export const Inativo: Story = { args: { status: false } };
export const Pequeno: Story = { args: { size: 'sm' } };
export const StatusForaDoDicionario: Story = { args: { status: 'desconhecido', label: 'Em análise' } };
