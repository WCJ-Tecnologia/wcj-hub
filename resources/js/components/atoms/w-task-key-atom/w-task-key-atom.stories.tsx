import type { Meta, StoryObj } from '@storybook/react-vite';

import { WTaskKeyAtom } from './w-task-key-atom';

const meta = {
    title: 'Atoms/Task Key',
    component: WTaskKeyAtom,
    tags: ['autodocs'],
    argTypes: {
        taskKey: { control: 'text' },
        href: { control: 'text' },
        title: { control: 'text' },
        size: { control: 'inline-radio', options: ['sm', 'default'] },
    },
    args: { taskKey: 'SGM-17' },
} satisfies Meta<typeof WTaskKeyAtom>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Texto: Story = {};
export const ComLink: Story = { args: { href: '/projetos/1/cards/SGM-17', title: 'Emitir a guia de IPTU' } };
export const Pequena: Story = { args: { taskKey: 'SGM-10', href: '/projetos/1/cards/SGM-10', size: 'sm' } };
