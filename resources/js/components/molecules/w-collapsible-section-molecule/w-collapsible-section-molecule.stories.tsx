import type { Meta, StoryObj } from '@storybook/react-vite';

import { Button } from '@/components/shadcn/button';

import { WCollapsibleSectionMolecule } from './w-collapsible-section-molecule';

const meta = {
    title: 'Molecules/Collapsible Section',
    component: WCollapsibleSectionMolecule,
    tags: ['autodocs'],
    argTypes: {
        title: { control: 'text' },
        summary: { control: 'text' },
        count: { control: 'number' },
        defaultOpen: { control: 'boolean' },
        open: { control: false },
        actions: { control: false },
        children: { control: false },
    },
    args: {
        title: 'Critérios de aceite',
        count: 4,
        summary: '1 aprovado · 1 reprovado · 2 a testar',
        children: <p className="text-sm text-muted-foreground">O conteúdo da seção fica aqui.</p>,
    },
    decorators: [
        (Story) => (
            <div className="w-[36rem] max-w-full">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WCollapsibleSectionMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Como nasce na ficha: recolhida, com o resumo dizendo o que há dentro (R4). */
export const Recolhida: Story = {};
export const Aberta: Story = { args: { defaultOpen: true } };
/** Ações à direita do cabeçalho, fora do botão que abre e fecha. */
export const ComAcoes: Story = {
    args: {
        title: 'Requisição',
        count: undefined,
        summary: 'Sendo contribuinte com IPTU em aberto, quero emitir a 2ª via…',
        actions: (
            <Button variant="ghost" size="sm">
                Editar
            </Button>
        ),
    },
};
export const SemResumo: Story = { args: { title: 'Anexos', count: 0, summary: undefined } };
