import type { Meta, StoryObj } from '@storybook/react-vite';
import { userEvent, within } from 'storybook/test';

import { asUser, DEVELOPER } from '../../../../../.storybook/fixtures';
import {
    ABILITIES_DEV,
    ABILITIES_GESTOR,
    BOARD_FILTERS_VAZIO,
    BOARD_OPTIONS,
    BOARD_PROJECT,
    BOARD_PROJECT_SEM_SPRINT,
    CARDS,
    CARDS_MONTAR_SPRINT,
    CARDS_VAZIO,
    SPRINT_PLANNING,
    SPRINT_PLANNING_COM_ABERTA,
    STAGES,
} from '../../../../../.storybook/fixtures-quadro';
import { WProjectBoardPage } from './w-project-board-page';

const meta = {
    title: 'Pages/Project Board',
    component: WProjectBoardPage,
    tags: ['autodocs'],
    argTypes: {
        project: { control: 'object' },
        cards: { control: 'object' },
        abilities: { control: 'object' },
        filters: { control: 'object' },
        stages: { control: false },
        options: { control: false },
        today: { control: 'text' },
    },
    args: {
        project: BOARD_PROJECT,
        stages: STAGES,
        cards: CARDS,
        abilities: ABILITIES_GESTOR,
        options: BOARD_OPTIONS,
        filters: BOARD_FILTERS_VAZIO,
        today: '2026-10-14',
        planning: SPRINT_PLANNING_COM_ABERTA,
    },
    parameters: { layout: 'fullscreen', inertia: { url: '/projetos/1/quadro' } },
} satisfies Meta<typeof WProjectBoardPage>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor com sprint aberta: arrastar para Paused pede o motivo; de In test para Doing, o retrabalho; para Done, o aviso do roteiro. */
export const Padrao: Story = {};
/** Sem sprint aberta: arrastar do Backlog para o To do abre o diálogo *Nova sprint* (R5, F5 §02). */
export const SemSprintAberta: Story = { args: { project: BOARD_PROJECT_SEM_SPRINT, planning: SPRINT_PLANNING } };
/** Dev (Bruno Lima): tirar do Backlog mostra a recusa em toast (P45); *Só os meus* filtra os cards dele. */
export const ComoDev: Story = {
    args: { abilities: ABILITIES_DEV, planning: null },
    parameters: { inertia: { url: '/projetos/1/quadro', props: asUser(DEVELOPER) } },
};
export const Vazio: Story = { args: { cards: CARDS_VAZIO } };

/** Liga o *Montar sprint* pelo botão do cabeçalho e marca SGM-23, SGM-30 e SGM-21. */
async function selectThree(canvasElement: HTMLElement, button: RegExp) {
    const canvas = within(canvasElement);
    await userEvent.click(await canvas.findByRole('button', { name: button }));
    for (const key of ['SGM-23', 'SGM-30', 'SGM-21']) {
        await userEvent.click(await canvas.findByRole('checkbox', { name: new RegExp(`^Selecionar ${key} `) }));
    }
}

/**
 * *Montar sprint* sem sprint aberta (D-GER-35): o botão do cabeçalho liga o modo de seleção no Backlog;
 * 3 marcadas, a barra soma as horas e *Iniciar sprint* abre o diálogo com a sprint proposta.
 */
export const MontarSprintSemSprintAberta: Story = {
    args: { project: BOARD_PROJECT_SEM_SPRINT, cards: CARDS_MONTAR_SPRINT, planning: SPRINT_PLANNING },
    play: async ({ canvasElement }) => selectThree(canvasElement, /^Montar sprint$/),
};
/** Com sprint aberta o botão é *Pôr na Sprint 3*, e o diálogo só confirma. */
export const PorNaSprintAberta: Story = {
    args: { cards: CARDS_MONTAR_SPRINT },
    play: async ({ canvasElement }) => selectThree(canvasElement, /^Pôr na Sprint 3$/),
};
/** O planejamento *Iniciar sprint*, aberto pela barra depois de marcar: dev, QA, horas e prioridade por card, com a capacidade ao lado. */
export const IniciarSprint: Story = {
    args: { project: BOARD_PROJECT_SEM_SPRINT, cards: CARDS_MONTAR_SPRINT, planning: SPRINT_PLANNING },
    play: async ({ canvasElement }) => {
        await selectThree(canvasElement, /^Montar sprint$/);
        await userEvent.click(await within(canvasElement).findByRole('button', { name: 'Iniciar sprint' }));
    },
};
export const MontarSprintCelular: Story = {
    ...MontarSprintSemSprintAberta,
    globals: { viewport: { value: 'mobile2', isRotated: false } },
};
/** O planejamento *Pôr na Sprint 3*: o período é o da sprint aberta e a carga que já está nela entra na conta. */
export const PorNaSprintAbertaPlanejamento: Story = {
    args: { cards: CARDS_MONTAR_SPRINT },
    play: async ({ canvasElement }) => {
        await selectThree(canvasElement, /^Pôr na Sprint 3$/);
        await userEvent.click(await within(canvasElement).findByRole('button', { name: 'Pôr na Sprint 3' }));
    },
};
