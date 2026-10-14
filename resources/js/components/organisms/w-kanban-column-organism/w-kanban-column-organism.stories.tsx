import { DndContext } from '@dnd-kit/core';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';

import { sortCards } from '@/lib/quadro';

import { ABILITIES_DEV, ABILITIES_GESTOR, BOARD_OPTIONS, CARDS, STAGES } from '../../../../../.storybook/fixtures-quadro';
import { WKanbanColumnOrganism } from './w-kanban-column-organism';

const [BACKLOG, , , , , , DONE, SUPPORT] = STAGES;
const inStage = (stageId: number) =>
    sortCards(
        CARDS.filter((card) => card.stage_id === stageId),
        'priority',
    );

const meta = {
    title: 'Organisms/Kanban Column',
    component: WKanbanColumnOrganism,
    tags: ['autodocs'],
    argTypes: {
        stage: { control: 'object' },
        cards: { control: 'object' },
        abilities: { control: 'object' },
        highlighted: { control: 'boolean' },
        creating: { control: 'boolean' },
        createError: { control: 'text' },
        stages: { control: false },
        areas: { control: false },
        members: { control: false },
        onCreate: { control: false },
        onCreateSupport: { control: false },
        onMove: { control: false },
        onChangePriority: { control: false },
        onChangeArea: { control: false },
        onToggleParticipant: { control: false },
        onOpen: { control: false },
    },
    args: {
        stage: BACKLOG,
        cards: inStage(BACKLOG.id),
        abilities: ABILITIES_GESTOR,
        stages: STAGES,
        areas: BOARD_OPTIONS.areas,
        members: BOARD_OPTIONS.members,
        onCreate: fn(),
        onCreateSupport: fn(),
        onMove: fn(),
        onChangePriority: fn(),
        onChangeArea: fn(),
        onToggleParticipant: fn(),
    },
    decorators: [
        (Story) => (
            // A coluna precisa do DndContext do quadro (área de soltar e cards arrastáveis).
            <DndContext>
                <div className="h-[36rem]">
                    <Story />
                </div>
            </DndContext>
        ),
    ],
} satisfies Meta<typeof WKanbanColumnOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Backlog com o criar rápido no topo (P17, R4). */
export const Backlog: Story = {};
export const BacklogComoDev: Story = { args: { abilities: ABILITIES_DEV } };
export const Criando: Story = { args: { creating: true } };
/** Coluna vazia: só a contagem 0 e a área tracejada para soltar (F3 §03). */
export const Vazia: Story = { args: { stage: STAGES[3], cards: [] } };
/** Support com o botão *Novo card de suporte* (P52), só para gestor e Administrador. */
export const Support: Story = { args: { stage: SUPPORT, cards: inStage(SUPPORT.id) } };
/** Done com a dica de quais cards aparecem (P13). */
export const Done: Story = {
    args: {
        stage: DONE,
        cards: sortCards(
            [
                ...inStage(DONE.id),
                { ...CARDS.find((card) => card.key === 'SGM-18')!, id: 9, key: 'SGM-9', stage_id: DONE.id, title: 'Cadastro de bairros' },
            ],
            'priority',
        ),
    },
};
export const DestacadaAoArrastar: Story = { args: { stage: STAGES[2], cards: inStage(STAGES[2].id), highlighted: true } };
