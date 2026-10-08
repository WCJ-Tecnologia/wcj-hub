import type { Meta, StoryObj } from '@storybook/react-vite';
import { Plus, Trash2 } from 'lucide-react';

import { Button } from './button';

const meta = {
    title: 'Shadcn/Button',
    component: Button,
    tags: ['autodocs'],
    argTypes: {
        variant: { control: 'select', options: ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'] },
        size: { control: 'select', options: ['default', 'sm', 'lg', 'icon'] },
        disabled: { control: 'boolean' },
        asChild: { control: false },
    },
    args: { children: 'Salvar', variant: 'default', size: 'default', disabled: false },
} satisfies Meta<typeof Button>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const Destrutivo: Story = { args: { variant: 'destructive', children: 'Excluir' } };
export const Contorno: Story = { args: { variant: 'outline', children: 'Cancelar' } };
export const Link: Story = { args: { variant: 'link', children: 'Ver detalhes' } };
export const ComIcone: Story = {
    args: {
        children: (
            <>
                <Plus aria-hidden /> Novo usuário
            </>
        ),
    },
};
export const SoIcone: Story = { args: { size: 'icon', variant: 'ghost', 'aria-label': 'Excluir', children: <Trash2 aria-hidden /> } };
