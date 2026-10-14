import { BOARD_OPTIONS, CARD_COMPLETO, CARDS, STAGES, card } from '../../../../../.storybook/fixtures-quadro';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import { Toaster } from '@/components/shadcn/sonner';
import type { BoardCard, BoardStage } from '@/types/quadro';

import { WTaskCardOrganism, type WTaskCardOrganismProps } from './w-task-card-organism';

const byKey = (key: string): BoardCard => {
    const found = CARDS.find((item) => item.key === key);
    if (!found) throw new Error(`Card ${key} não está nas fixtures`);
    return found;
};
const stageOf = (item: BoardCard): BoardStage => STAGES.find((stage) => stage.id === item.stage_id) ?? STAGES[0];

/**
 * O card com estado local: Prioridade, Área, participantes e Mover para… mudam o card na hora,
 * como depois da resposta do servidor. As intenções aparecem no painel Actions.
 */
function TaskCardDemo(props: WTaskCardOrganismProps) {
    const [current, setCurrent] = useState<BoardCard>(props.card);
    const [stage, setStage] = useState<BoardStage>(props.stage);

    return (
        <WTaskCardOrganism
            {...props}
            card={current}
            stage={stage}
            onMove={(item, to) => {
                action('onMove')(item.key, to.name);
                setStage(to);
                setCurrent({ ...item, stage_id: to.id });
            }}
            onChangePriority={(item, priority) => {
                action('onChangePriority')(item.key, priority);
                setCurrent({ ...item, priority });
            }}
            onChangeArea={(item, area) => {
                action('onChangeArea')(item.key, area?.name ?? null);
                setCurrent({ ...item, area });
            }}
            onToggleParticipant={(item, person, checked) => {
                action('onToggleParticipant')(item.key, person.name, checked);
                setCurrent({
                    ...item,
                    participants: checked ? [...item.participants, person] : item.participants.filter((p) => p.id !== person.id),
                });
            }}
        />
    );
}

const meta = {
    title: 'Organisms/Task Card',
    component: WTaskCardOrganism,
    tags: ['autodocs'],
    argTypes: {
        card: { control: 'object' },
        stage: { control: false },
        stages: { control: false },
        areas: { control: false },
        members: { control: false },
        dragging: { control: 'boolean' },
        compact: { control: 'boolean' },
        onMove: { control: false },
        onChangePriority: { control: false },
        onChangeArea: { control: false },
        onToggleParticipant: { control: false },
        onOpen: { control: false },
    },
    args: {
        card: card({ id: 31, key: 'SGM-31', title: 'Revisar o texto do e-mail de boas-vindas', stage_id: 1 }),
        stage: STAGES[0],
        stages: STAGES,
        areas: BOARD_OPTIONS.areas,
        members: BOARD_OPTIONS.members,
        dragging: false,
        compact: false,
    },
    render: (args) => <TaskCardDemo key={args.card.key} {...args} />,
    decorators: [
        (Story) => (
            <div className="w-72">
                <Story />
                <Toaster />
            </div>
        ),
    ],
} satisfies Meta<typeof WTaskCardOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Card criado pelo rápido: só o título (P17, R4). */
export const Minimo: Story = {};

/** Todas as zonas do F3 §03. */
export const Completo: Story = { args: { card: CARD_COMPLETO, stage: stageOf(CARD_COMPLETO) } };

export const Filho: Story = { args: { card: byKey('SGM-20'), stage: stageOf(byKey('SGM-20')) } };

export const Pai: Story = { args: { card: byKey('SGM-10'), stage: stageOf(byKey('SGM-10')) } };

export const Suporte: Story = { args: { card: byKey('SGM-24'), stage: stageOf(byKey('SGM-24')) } };

export const EmPausa: Story = { args: { card: byKey('SGM-15'), stage: stageOf(byKey('SGM-15')) } };

export const Atrasado: Story = {
    args: { card: { ...byKey('SGM-16'), late: true, due_on: '2026-10-09' }, stage: stageOf(byKey('SGM-16')) },
};

/** Sem áreas no projeto: o submenu Área mostra "Nenhuma área no projeto". */
export const SemAreas: Story = { args: { card: byKey('SGM-19'), stage: stageOf(byKey('SGM-19')), areas: [] } };

/** O visual enquanto o quadro arrasta o card. */
export const Arrastando: Story = { args: { card: byKey('SGM-18'), stage: stageOf(byKey('SGM-18')), dragging: true } };

/** Abre a ficha por callback (ex.: num Sheet), em vez de navegar. */
export const AbreFichaPorCallback: Story = {
    args: { card: byKey('SGM-18'), stage: stageOf(byKey('SGM-18')), onOpen: action('onOpen') },
};

export const Compacto: Story = { args: { card: CARD_COMPLETO, stage: stageOf(CARD_COMPLETO), compact: true } };

/** Largura de celular (375 px menos o respiro de 16 px de cada lado). */
export const Celular: Story = {
    args: { card: CARD_COMPLETO, stage: stageOf(CARD_COMPLETO) },
    decorators: [
        (Story) => (
            <div className="w-[343px]">
                <Story />
            </div>
        ),
    ],
};
