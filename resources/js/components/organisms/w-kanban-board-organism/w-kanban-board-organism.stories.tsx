import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';
import { fn } from 'storybook/test';

import { Toaster } from '@/components/shadcn/sonner';
import type { BoardCard } from '@/types/quadro';

import {
    ABILITIES_DEV,
    ABILITIES_ENCERRADO,
    ABILITIES_GESTOR,
    BOARD_OPTIONS,
    BOARD_PROJECT,
    CARDS,
    CARDS_VAZIO,
    STAGES,
} from '../../../../../.storybook/fixtures-quadro';
import { WKanbanBoardOrganism, type WKanbanBoardOrganismProps } from './w-kanban-board-organism';

/**
 * O quadro com estado local: cada `onMove` aparece no painel Actions e o card muda de coluna (e de
 * posição, na ordem manual) na hora, sem diálogos. Os diálogos e as recusas estão na story da página.
 */
function KanbanBoardDemo(props: WKanbanBoardOrganismProps) {
    const [cards, setCards] = useState<BoardCard[]>(props.cards);

    return (
        <>
            <WKanbanBoardOrganism
                {...props}
                cards={cards}
                onMove={(intent) => {
                    props.onMove(intent);
                    setCards((list) => {
                        const moved = { ...intent.card, stage_id: intent.to.id };
                        const others = list.filter((item) => item.id !== intent.card.id);
                        if (intent.index === null) return [...others, moved];
                        const column = others.filter((item) => item.stage_id === intent.to.id).sort((a, b) => a.position - b.position);
                        column.splice(intent.index, 0, moved);
                        const rest = others.filter((item) => item.stage_id !== intent.to.id);
                        return [...rest, ...column.map((item, index) => ({ ...item, position: index + 1 }))];
                    });
                }}
                onChangePriority={(card, priority) => {
                    action('onChangePriority')(card.key, priority);
                    setCards((list) => list.map((item) => (item.id === card.id ? { ...item, priority } : item)));
                }}
            />
            <Toaster />
        </>
    );
}

const meta = {
    title: 'Organisms/Kanban Board',
    component: WKanbanBoardOrganism,
    tags: ['autodocs'],
    argTypes: {
        cards: { control: 'object' },
        order: { control: 'inline-radio', options: ['priority', 'manual'] },
        project: { control: 'object' },
        abilities: { control: 'object' },
        filtered: { control: 'boolean' },
        creating: { control: 'boolean' },
        createError: { control: 'text' },
        stages: { control: false },
        options: { control: false },
        onMove: { control: false },
        onCreate: { control: false },
        onCreateSupport: { control: false },
        onChangePriority: { control: false },
        onChangeArea: { control: false },
        onToggleParticipant: { control: false },
        onOpen: { control: false },
    },
    args: {
        stages: STAGES,
        cards: CARDS,
        order: 'priority',
        project: BOARD_PROJECT,
        abilities: ABILITIES_GESTOR,
        options: BOARD_OPTIONS,
        onMove: fn(),
        onCreate: fn(),
        onCreateSupport: fn(),
        onChangeArea: fn(),
        onToggleParticipant: fn(),
    },
    parameters: { layout: 'padded' },
    render: (args) => <KanbanBoardDemo key={`${args.order}-${args.cards.length}`} {...args} />,
} satisfies Meta<typeof WKanbanBoardOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor: arraste entre colunas (e para uma coluna que ficou vazia) e veja o `onMove` no Actions. */
export const Padrao: Story = {};
/** Dev: cria no Backlog, não tem *Novo card de suporte*; o quadro não recusa nada, quem recusa é a página (`classifyMove`). */
export const ComoDev: Story = { args: { abilities: ABILITIES_DEV } };
/** Ordem manual: o arraste vertical reordena, e o `onMove` leva o `index` (P13). */
export const OrdemManual: Story = { args: { order: 'manual' } };
export const Vazio: Story = { args: { cards: CARDS_VAZIO } };
export const FiltroSemResultado: Story = { args: { cards: [], filtered: true } };
export const ProjetoEncerrado: Story = { args: { project: { ...BOARD_PROJECT, finished: true }, abilities: ABILITIES_ENCERRADO } };
/** 375 px: uma coluna por vez, com rolagem horizontal e encaixe. */
export const Celular: Story = {
    decorators: [
        (Story) => (
            <div className="w-[375px] overflow-hidden border border-border">
                <Story />
            </div>
        ),
    ],
};
