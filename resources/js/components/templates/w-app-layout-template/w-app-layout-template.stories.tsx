import type { Meta, StoryObj } from '@storybook/react-vite';
import { Plus } from 'lucide-react';

import { Button } from '@/components/shadcn/button';
import { Card } from '@/components/shadcn/card';

import { WAppLayoutTemplate } from './w-app-layout-template';

const meta = {
    title: 'Templates/App Layout',
    component: WAppLayoutTemplate,
    tags: ['autodocs'],
    argTypes: {
        title: { control: 'text' },
        subtitle: { control: 'text' },
        eyebrow: { control: 'text' },
        width: { control: 'inline-radio', options: ['narrow', 'default', 'wide'] },
        actions: { control: false },
        children: { control: false },
    },
    args: {
        eyebrow: 'Cadastros',
        title: 'Usuários',
        subtitle: 'Quem acessa o sistema e com qual papel',
        width: 'default',
        children: <Card className="h-64 p-4 text-sm text-muted-foreground">Conteúdo da página</Card>,
    },
    parameters: { layout: 'fullscreen', inertia: { url: '/admin/users' } },
} satisfies Meta<typeof WAppLayoutTemplate>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComAcao: Story = {
    args: {
        actions: (
            <Button>
                <Plus aria-hidden /> Novo usuário
            </Button>
        ),
    },
};
export const ComAvisoDoServidor: Story = {
    parameters: { inertia: { url: '/admin/users', props: { flash: { success: 'Usuário cadastrado.' } } } },
};
export const Estreito: Story = { args: { width: 'narrow', eyebrow: 'Conta', title: 'Meu perfil', subtitle: 'Seus dados de acesso' } };
