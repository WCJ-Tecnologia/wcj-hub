import type { Meta, StoryObj } from '@storybook/react-vite';
import { Plus } from 'lucide-react';

import { Button } from '@/components/shadcn/button';

import { WPageHeaderMolecule } from './w-page-header-molecule';

const meta = {
    title: 'Molecules/Page Header',
    component: WPageHeaderMolecule,
    tags: ['autodocs'],
    argTypes: { eyebrow: { control: 'text' }, title: { control: 'text' }, subtitle: { control: 'text' }, actions: { control: false } },
    args: { eyebrow: 'Cadastros', title: 'Usuários', subtitle: 'Quem acessa o sistema e com qual papel' },
    parameters: { layout: 'padded' },
} satisfies Meta<typeof WPageHeaderMolecule>;

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
export const SoTitulo: Story = { args: { eyebrow: undefined, subtitle: undefined, title: 'Dashboard' } };
