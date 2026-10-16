import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';

import {
    BOARD_PROJECT,
    BOARD_PROJECT_SEM_SPRINT,
    CARDS_MONTAR_SPRINT,
    MONTAR_SPRINT_MARCADAS,
    SPRINT_PLANNING,
    SPRINT_PLANNING_COM_ABERTA,
} from '../../../../../.storybook/fixtures-quadro';
import { WStartSprintDialogOrganism } from './w-start-sprint-dialog-organism';

const MARCADAS = CARDS_MONTAR_SPRINT.filter((card) => MONTAR_SPRINT_MARCADAS.includes(card.id));

const meta = {
    title: 'Organisms/Start Sprint Dialog',
    component: WStartSprintDialogOrganism,
    tags: ['autodocs'],
    args: {
        open: true,
        onOpenChange: fn(),
        project: BOARD_PROJECT_SEM_SPRINT,
        cards: MARCADAS,
        planning: SPRINT_PLANNING,
        today: '2026-10-15',
        onRemove: fn(),
        onConfirm: fn(),
    },
} satisfies Meta<typeof WStartSprintDialogOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/**
 * Sem sprint aberta, 3 cards: a Sprint 4 proposta em cima; SGM-30 (44 h) estoura o Bruno (4 h/dia × 10 dias
 * úteis = 40 h), em destaque e sem travar; SGM-21 está sem dev e sem horas. Troque o dev, as horas ou a data
 * de fim e veja a capacidade mudar.
 */
export const SemSprintAberta: Story = {};
/** Com a Sprint 3 aberta: o período é o dela, e o que já falta da Ana e do Diego nela entra na conta. */
export const ComSprintAberta: Story = { args: { project: BOARD_PROJECT, planning: SPRINT_PLANNING_COM_ABERTA } };
/** O servidor recusou: um card saiu do Backlog, o QA não está alocado e o nome já existia. */
export const LoteRecusado: Story = {
    args: {
        errors: {
            tasks: 'Nada foi gravado: SGM-30 já está na Sprint 3.',
            'tasks.0.qa_id': 'O QA não está alocado neste projeto.',
            name: 'Já existe a Sprint 4 neste projeto',
        },
    },
};
export const Gravando: Story = { args: { loading: true } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
