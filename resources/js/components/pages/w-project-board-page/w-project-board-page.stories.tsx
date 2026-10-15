import type { Meta, StoryObj } from '@storybook/react-vite';
import { screen } from 'storybook/test';

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

type PlayContext = Parameters<NonNullable<Story['play']>>[0];

/**
 * Liga o *Montar sprint* pelo botão do cabeçalho e marca SGM-23, SGM-30 e SGM-21. Usa o `canvas` e o
 * `userEvent` do contexto do play (amarrados a esta renderização da story) e espera cada passo: nada roda
 * depois de o play terminar.
 */
async function selectThree({ canvas, userEvent }: PlayContext, button: RegExp) {
    await userEvent.click(await canvas.findByRole('button', { name: button }));
    for (const key of ['SGM-23', 'SGM-30', 'SGM-21']) {
        await userEvent.click(await canvas.findByRole('checkbox', { name: new RegExp(`^Selecionar ${key} `) }));
    }
}

/** Marca os três e abre o planejamento pelo botão da barra. */
async function openPlan(context: PlayContext, button: RegExp, confirm: string) {
    await selectThree(context, button);
    await context.userEvent.click(await context.canvas.findByRole('button', { name: confirm }));
    // O diálogo abre em portal, fora do canvas: espera por ele na página inteira.
    await screen.findByRole('dialog', { name: confirm });
}

/**
 * *Montar sprint* sem sprint aberta (D-GER-35): o botão do cabeçalho liga o modo de seleção no Backlog;
 * 3 marcadas, a barra soma as horas e *Iniciar sprint* abre o planejamento.
 */
export const MontarSprintSemSprintAberta: Story = {
    args: { project: BOARD_PROJECT_SEM_SPRINT, cards: CARDS_MONTAR_SPRINT, planning: SPRINT_PLANNING },
    play: async (context) => {
        await selectThree(context, /^Montar sprint$/);
    },
};
/** Com sprint aberta o botão é *Pôr na Sprint 3*. */
export const PorNaSprintAberta: Story = {
    args: { cards: CARDS_MONTAR_SPRINT },
    play: async (context) => {
        await selectThree(context, /^Pôr na Sprint 3$/);
    },
};
/** O planejamento *Iniciar sprint*, aberto pela barra depois de marcar: dev, QA, horas e prioridade por card, com a capacidade ao lado. */
export const IniciarSprint: Story = {
    args: { project: BOARD_PROJECT_SEM_SPRINT, cards: CARDS_MONTAR_SPRINT, planning: SPRINT_PLANNING },
    play: async (context) => {
        await openPlan(context, /^Montar sprint$/, 'Iniciar sprint');
    },
};
export const MontarSprintCelular: Story = {
    args: { project: BOARD_PROJECT_SEM_SPRINT, cards: CARDS_MONTAR_SPRINT, planning: SPRINT_PLANNING },
    globals: { viewport: { value: 'mobile2', isRotated: false } },
    play: async (context) => {
        await selectThree(context, /^Montar sprint$/);
    },
};
/** O planejamento *Pôr na Sprint 3*: o período é o da sprint aberta e a carga que já está nela entra na conta. */
export const PorNaSprintAbertaPlanejamento: Story = {
    args: { cards: CARDS_MONTAR_SPRINT },
    play: async (context) => {
        await openPlan(context, /^Pôr na Sprint 3$/, 'Pôr na Sprint 3');
    },
};
