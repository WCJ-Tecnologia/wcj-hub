import type { Meta, StoryObj } from '@storybook/react-vite';
import { Plus, Users } from 'lucide-react';

import { Button } from '@/components/shadcn/button';

import { WEmptyStateMolecule } from './w-empty-state-molecule';

const meta = {
    title: 'Molecules/Empty State',
    component: WEmptyStateMolecule,
    tags: ['autodocs'],
    argTypes: { title: { control: 'text' }, description: { control: 'text' }, icon: { control: false }, action: { control: false } },
    args: {
        title: 'Nenhum usuário encontrado',
        description: 'Cadastre as pessoas que vão acessar o sistema. Cada uma recebe uma senha temporária por e-mail.',
    },
    decorators: [
        (Story) => (
            <div className="w-[36rem]">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WEmptyStateMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComIconeEAcao: Story = {
    args: {
        icon: <Users className="size-5" aria-hidden />,
        action: (
            <Button>
                <Plus aria-hidden /> Novo usuário
            </Button>
        ),
    },
};
