import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';

import { WQuickCreateMolecule } from './w-quick-create-molecule';

const meta = {
    title: 'Molecules/Quick Create',
    component: WQuickCreateMolecule,
    tags: ['autodocs'],
    argTypes: {
        busy: { control: 'boolean' },
        error: { control: 'text' },
        onCreate: { control: false },
    },
    args: { onCreate: fn(), busy: false },
    decorators: [
        (Story) => (
            <div className="w-68 rounded-lg bg-muted/60 p-2">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WQuickCreateMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Escreva um título e tecle Enter: `onCreate` aparece no painel Actions e o campo fica pronto para o próximo. */
export const Padrao: Story = {};
export const Criando: Story = { args: { busy: true } };
export const ComErro: Story = { args: { error: 'Projeto encerrado não recebe card novo' } };
