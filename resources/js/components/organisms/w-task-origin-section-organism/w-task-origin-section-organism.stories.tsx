import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import type { TaskRef } from '@/types/quadro';

import { DETAIL_OPTIONS, TASK_BUG_PRODUCAO, TASK_COMPLETO, TASK_MINIMO } from '../../../../../.storybook/fixtures-ficha';
import { originSummary, WTaskOriginSectionOrganism, type WTaskOriginSectionOrganismProps } from './w-task-origin-section-organism';

/** Com estado local: escolher ou limpar a tarefa de origem muda na hora, como depois da resposta do servidor. */
function OriginSectionDemo(props: WTaskOriginSectionOrganismProps) {
    const [origin, setOrigin] = useState<TaskRef | null>(props.originTask);

    return (
        <div className="grid gap-3">
            <p className="text-xs text-muted-foreground">Resumo: {originSummary(origin, props.productionBugs)}</p>
            <WTaskOriginSectionOrganism
                {...props}
                originTask={origin}
                onChangeOrigin={(card) => {
                    action('onChangeOrigin')(card);
                    if (!props.errors) setOrigin(card);
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Origin Section',
    component: WTaskOriginSectionOrganism,
    render: (args) => <OriginSectionDemo {...args} />,
    tags: ['autodocs'],
    parameters: { layout: 'padded' },
    argTypes: {
        canEdit: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
        originTask: { control: 'object' },
        productionBugs: { control: 'object' },
        cards: { control: false },
        onChangeOrigin: { control: false },
    },
    args: {
        originTask: TASK_COMPLETO.origin_task,
        productionBugs: TASK_COMPLETO.production_bugs,
        cards: DETAIL_OPTIONS.cards,
        canEdit: true,
        busy: false,
        onChangeOrigin: () => undefined,
    },
    decorators: [
        (Story) => (
            <div className="w-[40rem] max-w-full">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WTaskOriginSectionOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** O SGM-17: sem tarefa de origem e com 1 bug de produção ligado (o SGM-28). */
export const Padrao: Story = {};
/** O SGM-28, bug de produção do SGM-17 (P25). */
export const BugDeProducao: Story = {
    args: { originTask: TASK_BUG_PRODUCAO.origin_task, productionBugs: TASK_BUG_PRODUCAO.production_bugs },
};
/** Sem origem e sem bugs ligados: só a busca. */
export const Vazio: Story = { args: { originTask: TASK_MINIMO.origin_task, productionBugs: TASK_MINIMO.production_bugs } };
export const SoLeitura: Story = { args: { ...BugDeProducao.args, canEdit: false } };
export const Gravando: Story = { args: { ...BugDeProducao.args, busy: true } };
export const ComErro: Story = {
    args: { ...Vazio.args, errors: { origin_task_id: 'A tarefa de origem precisa ser um card do mesmo projeto.' } },
};
