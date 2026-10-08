import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';

import { WConfirmDialogMolecule } from './w-confirm-dialog-molecule';

const meta = {
    title: 'Molecules/Confirm Dialog',
    component: WConfirmDialogMolecule,
    tags: ['autodocs'],
    argTypes: {
        open: { control: 'boolean' },
        title: { control: 'text' },
        description: { control: 'text' },
        confirmLabel: { control: 'text' },
        cancelLabel: { control: 'text' },
        destructive: { control: 'boolean' },
        loading: { control: 'boolean' },
        children: { control: false },
    },
    args: {
        open: true,
        title: 'Excluir usuário',
        description: 'Excluir Ana Souza? Só é possível excluir quem nunca entrou no sistema.',
        confirmLabel: 'Excluir',
        cancelLabel: 'Cancelar',
        destructive: true,
        loading: false,
        onOpenChange: fn(),
        onConfirm: fn(),
    },
    parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof WConfirmDialogMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Destrutivo: Story = {};
export const Neutro: Story = {
    args: {
        title: 'Enviar senha temporária',
        description: 'Vamos mandar uma senha temporária para ana@exemplo.wcj.test.',
        confirmLabel: 'Enviar agora',
        destructive: false,
    },
};
export const Carregando: Story = { args: { loading: true } };
export const ComConteudoExtra: Story = {
    args: {
        children: (
            <ul className="list-disc space-y-1 pl-5 text-sm">
                <li>3 tarefas perdem o responsável</li>
                <li>O histórico continua na auditoria</li>
            </ul>
        ),
    },
};
