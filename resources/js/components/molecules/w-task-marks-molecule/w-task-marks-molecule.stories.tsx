import { CARD_COMPLETO, CARDS, card } from '../../../../../.storybook/fixtures-quadro';
import type { Meta, StoryObj } from '@storybook/react-vite';

import { WTaskMarksMolecule } from './w-task-marks-molecule';

const BASE = card({ id: 30, key: 'SGM-30', title: 'Card de exemplo', stage_id: 3 });
const byKey = (key: string) => CARDS.find((item) => item.key === key) ?? BASE;
const SUPORTE = byKey('SGM-24');
const PAUSADO = byKey('SGM-15');

const meta = {
    title: 'Molecules/Task Marks',
    component: WTaskMarksMolecule,
    tags: ['autodocs'],
    argTypes: {
        card: { control: 'object' },
        inPausedStage: { control: 'boolean' },
    },
    args: { card: BASE, inPausedStage: false },
    decorators: [
        (Story) => (
            <div className="w-72">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WTaskMarksMolecule>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Pausa: Story = { args: { card: PAUSADO, inPausedStage: true } };
export const PausaSemMotivo: Story = { args: { card: { ...PAUSADO, pause_reason: null }, inPausedStage: true } };
export const Retrabalho: Story = { args: { card: { ...BASE, rework_count: 2 } } };
export const UmaVolta: Story = { args: { card: { ...BASE, rework_count: 1 } } };
export const Suporte: Story = { args: { card: SUPORTE } };
export const Reestimativa: Story = { args: { card: { ...BASE, estimated_hours: 16, proposed_hours: 24 } } };
export const Atraso: Story = { args: { card: { ...BASE, late: true, due_on: '2026-10-09' } } };
export const Todas: Story = {
    args: {
        card: { ...CARD_COMPLETO, type: SUPORTE.type, support_requester: 'Marcos Vieira', pause_reason: 'Aguardando o cliente' },
        inPausedStage: true,
    },
};
/** Sem marca: não renderiza nada. */
export const Nenhuma: Story = {};
