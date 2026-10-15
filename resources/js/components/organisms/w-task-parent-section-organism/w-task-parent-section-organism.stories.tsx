import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import type { ParentRef } from '@/types/quadro';

import { DETAIL_OPTIONS, TASK_COMPLETO, TASK_MINIMO, TASK_PAI } from '../../../../../.storybook/fixtures-ficha';
import { parentSummary, WTaskParentSectionOrganism, type WTaskParentSectionOrganismProps } from './w-task-parent-section-organism';

/** Com estado local: escolher ou limpar o pai muda na hora, como depois da resposta do servidor. */
function ParentSectionDemo(props: WTaskParentSectionOrganismProps) {
    const [parent, setParent] = useState<ParentRef | null>(props.parent);

    return (
        <div className="grid gap-3">
            <p className="text-xs text-muted-foreground">Resumo: {parentSummary(parent, props.children)}</p>
            <WTaskParentSectionOrganism
                {...props}
                parent={parent}
                onChangeParent={(card) => {
                    action('onChangeParent')(card);
                    if (!props.errors) setParent(card);
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Parent Section',
    component: WTaskParentSectionOrganism,
    render: (args) => <ParentSectionDemo {...args} />,
    tags: ['autodocs'],
    parameters: { layout: 'padded' },
    argTypes: {
        taskKey: { control: 'text' },
        canEdit: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
        parent: { control: 'object' },
        children: { control: false },
        cards: { control: false },
        onChangeParent: { control: false },
    },
    args: {
        taskKey: TASK_COMPLETO.key,
        parent: TASK_COMPLETO.parent,
        children: TASK_COMPLETO.children,
        cards: DETAIL_OPTIONS.cards,
        canEdit: true,
        busy: false,
        onChangeParent: () => undefined,
    },
    decorators: [
        (Story) => (
            <div className="w-[40rem] max-w-full">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WTaskParentSectionOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** O SGM-17, filho do SGM-10: chave e título do pai com link, *Trocar* e *Limpar*. */
export const Filho: Story = {};
/** O SGM-10, pai de 7 cards: resumo, barra e a lista dos filhos; a busca do pai fica desabilitada (P57). */
export const Pai: Story = { args: { taskKey: TASK_PAI.key, parent: TASK_PAI.parent, children: TASK_PAI.children } };
/** Sem pai nem filhos: a busca vazia. Digite "sgm-1": o SGM-10 aparece desabilitado com o motivo. */
export const SemPai: Story = { args: { taskKey: TASK_MINIMO.key, parent: TASK_MINIMO.parent, children: TASK_MINIMO.children } };
export const SoLeitura: Story = { args: { canEdit: false } };
export const SoLeituraPai: Story = { args: { ...Pai.args, canEdit: false } };
export const Gravando: Story = { args: { busy: true } };
export const ComErro: Story = {
    args: { ...SemPai.args, errors: { parent_task_id: 'SGM-10 já é pai de outros cards; um nível só.' } },
};
