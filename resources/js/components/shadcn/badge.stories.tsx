import type { Meta, StoryObj } from '@storybook/react-vite';

import { Badge } from './badge';

const meta = {
    title: 'Shadcn/Badge',
    component: Badge,
    tags: ['autodocs'],
    argTypes: {
        variant: { control: 'select', options: ['default', 'secondary', 'outline', 'success', 'warning', 'info', 'danger'] },
        asChild: { control: false },
    },
    args: { children: 'Administrador', variant: 'info' },
} satisfies Meta<typeof Badge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Info: Story = {};
export const Sucesso: Story = { args: { variant: 'success', children: 'Aprovado' } };
export const Aviso: Story = { args: { variant: 'warning', children: 'Trocar senha no próximo acesso' } };
export const Perigo: Story = { args: { variant: 'danger', children: 'Recusado' } };
export const Contorno: Story = { args: { variant: 'outline', children: 'Criado' } };

export const TodasAsVariantes: Story = {
    render: () => (
        <div className="flex flex-wrap gap-2">
            {(['default', 'secondary', 'outline', 'success', 'warning', 'info', 'danger'] as const).map((variant) => (
                <Badge key={variant} variant={variant}>
                    {variant}
                </Badge>
            ))}
        </div>
    ),
};
