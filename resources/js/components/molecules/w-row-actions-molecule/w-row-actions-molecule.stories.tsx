import type { Meta, StoryObj } from '@storybook/react-vite';
import { KeyRound, Pencil, Trash2 } from 'lucide-react';
import { fn } from 'storybook/test';

import { WRowActionsMolecule } from './w-row-actions-molecule';

const meta = {
    title: 'Molecules/Row Actions',
    component: WRowActionsMolecule,
    tags: ['autodocs'],
    argTypes: { labeled: { control: 'boolean' }, actions: { control: 'object' } },
    args: {
        labeled: false,
        actions: [
            { label: 'Enviar senha', ariaLabel: 'Enviar nova senha para Ana Souza', icon: KeyRound, onClick: fn() },
            { label: 'Editar', ariaLabel: 'Editar Ana Souza', icon: Pencil, onClick: fn() },
            { label: 'Excluir', ariaLabel: 'Excluir Ana Souza', icon: Trash2, onClick: fn(), destructive: true },
        ],
    },
} satisfies Meta<typeof WRowActionsMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Desktop: Story = {};
export const CelularComTexto: Story = { args: { labeled: true } };
