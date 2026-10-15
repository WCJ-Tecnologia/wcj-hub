import type { Meta, StoryObj } from '@storybook/react-vite';

import { asUser, DEVELOPER } from '../../../../../.storybook/fixtures';
import {
    DETAIL_OPTIONS,
    TASK_ARQUIVADO,
    TASK_BUG_PRODUCAO,
    TASK_COMPLETO,
    TASK_COMPLETO_DEV,
    TASK_MINIMO,
    TASK_PAI,
    TASK_SUPORTE,
} from '../../../../../.storybook/fixtures-ficha';
import { BOARD_PROJECT, BOARD_PROJECT_SEM_SPRINT } from '../../../../../.storybook/fixtures-quadro';
import { WTaskDetailPage } from './w-task-detail-page';

const meta = {
    title: 'Pages/Task Detail',
    component: WTaskDetailPage,
    tags: ['autodocs'],
    argTypes: {
        task: { control: 'object' },
        project: { control: 'object' },
        options: { control: false },
        today: { control: 'text' },
    },
    args: { project: BOARD_PROJECT, task: TASK_COMPLETO, options: DETAIL_OPTIONS, today: '2026-10-14' },
    parameters: { layout: 'fullscreen', inertia: { url: '/projetos/1/cards/SGM-17' } },
} satisfies Meta<typeof WTaskDetailPage>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor na ficha cheia: cada seção recolhida mostra o resumo; *Mover para…* no menu ⋯ abre os diálogos do quadro. */
export const Completo: Story = {};
/** O card do criar rápido: tudo vazio, com os textos do F3 §03, e *Excluir* no menu (nunca usado, P26). */
export const Minimo: Story = { args: { task: TASK_MINIMO, project: BOARD_PROJECT_SEM_SPRINT } };
/** Dev (Bruno Lima): propõe horas, não arquiva, tirar do Backlog recusa (P45). */
export const ComoDev: Story = {
    args: { task: TASK_COMPLETO_DEV },
    parameters: { inertia: { url: '/projetos/1/cards/SGM-17', props: asUser(DEVELOPER) } },
};
/** Card pai com 7 filhos: resumo, barra e lista; o picker de pai fica desabilitado (um nível só, P57). */
export const CardPai: Story = { args: { task: TASK_PAI } };
/** Arquivado: a situação diz quando e por quê; *Desarquivar* no menu (P26). */
export const Arquivado: Story = { args: { task: TASK_ARQUIVADO } };
/** Card de suporte em pausa: o pedido do cliente só leitura; marcas de pausa e suporte (P15, P52). */
export const Suporte: Story = { args: { task: TASK_SUPORTE } };
/** Bug de produção: aponta para a tarefa de origem (P25). */
export const BugDeProducao: Story = { args: { task: TASK_BUG_PRODUCAO } };
