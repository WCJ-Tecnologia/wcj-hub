import type { Meta, StoryObj } from '@storybook/react-vite';

import { WAuthLayoutTemplate } from './w-auth-layout-template';

const meta = {
    title: 'Templates/Auth Layout',
    component: WAuthLayoutTemplate,
    tags: ['autodocs'],
    argTypes: { title: { control: 'text' }, children: { control: false } },
    args: {
        title: 'Entrar',
        children: (
            <div>
                <h1 className="font-heading text-2xl font-bold tracking-tight">Entrar</h1>
                <p className="mt-1.5 text-sm text-muted-foreground">O formulário da tela entra aqui.</p>
            </div>
        ),
    },
    parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof WAuthLayoutTemplate>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
