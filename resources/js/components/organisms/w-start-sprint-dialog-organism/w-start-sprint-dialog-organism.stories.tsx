import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';

import {
    BOARD_PROJECT,
    BOARD_PROJECT_SEM_SPRINT,
    CARDS_MONTAR_SPRINT,
    MONTAR_SPRINT_MARCADAS,
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
        today: '2026-10-15',
        onConfirm: fn(),
    },
} satisfies Meta<typeof WStartSprintDialogOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Sem sprint aberta: *Iniciar sprint* com a Sprint 4 proposta (hoje, fim em 10 dias úteis) e as marcadas. */
export const SemSprintAberta: Story = {};
/** Com sprint aberta: só a confirmação, *Pôr na Sprint 3*. */
export const ComSprintAberta: Story = { args: { project: BOARD_PROJECT } };
/** O servidor recusou o lote: uma das marcadas saiu do Backlog no meio, e o nome já existia. */
export const LoteRecusado: Story = {
    args: {
        errors: {
            task_ids: 'Nada foi gravado: SGM-30 já está na Sprint 3.',
            name: 'Já existe a Sprint 4 neste projeto',
        },
    },
};
export const Gravando: Story = { args: { loading: true } };
export const Celular: Story = { globals: { viewport: { value: 'mobile2', isRotated: false } } };
