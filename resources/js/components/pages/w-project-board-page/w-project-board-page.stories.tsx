import type { Meta, StoryObj } from '@storybook/react-vite';

import { asUser, DEVELOPER } from '../../../../../.storybook/fixtures';
import {
    ABILITIES_DEV,
    ABILITIES_GESTOR,
    BOARD_FILTERS_VAZIO,
    BOARD_OPTIONS,
    BOARD_PROJECT,
    BOARD_PROJECT_SEM_SPRINT,
    CARDS,
    CARDS_VAZIO,
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
    },
    parameters: { layout: 'fullscreen', inertia: { url: '/projetos/1/quadro' } },
} satisfies Meta<typeof WProjectBoardPage>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor com sprint aberta: arrastar para Paused pede o motivo; de In test para Doing, o retrabalho; para Done, o aviso do roteiro. */
export const Padrao: Story = {};
/** Sem sprint aberta: arrastar do Backlog para o To do abre o diálogo *Nova sprint* (R5, F5 §02). */
export const SemSprintAberta: Story = { args: { project: BOARD_PROJECT_SEM_SPRINT } };
/** Dev (Bruno Lima): tirar do Backlog mostra a recusa em toast (P45); *Só os meus* filtra os cards dele. */
export const ComoDev: Story = {
    args: { abilities: ABILITIES_DEV },
    parameters: { inertia: { url: '/projetos/1/quadro', props: asUser(DEVELOPER) } },
};
export const Vazio: Story = { args: { cards: CARDS_VAZIO } };
