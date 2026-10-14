import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import type { AreaRef, PrioritySlug, TaskTypeRef } from '@/types/quadro';

import { DETAIL_OPTIONS, TASK_COMPLETO, TASK_MINIMO, TASK_SUPORTE } from '../../../../../.storybook/fixtures-ficha';
import {
    attributesSummary,
    WTaskAttributesSectionOrganism,
    type WTaskAttributesSectionOrganismProps,
} from './w-task-attributes-section-organism';

/** Com estado local: cada troca muda o valor na hora, como depois da resposta do servidor. */
function AttributesSectionDemo(props: WTaskAttributesSectionOrganismProps) {
    const [type, setType] = useState<TaskTypeRef>(props.type);
    const [area, setArea] = useState<AreaRef | null>(props.area);
    const [priority, setPriority] = useState<PrioritySlug>(props.priority);

    return (
        <div className="grid gap-3">
            <p className="text-xs text-muted-foreground">Resumo: {attributesSummary(type, area, priority)}</p>
            <WTaskAttributesSectionOrganism
                {...props}
                type={type}
                area={area}
                priority={priority}
                onChange={(changes) => {
                    action('onChange')(changes);
                    if (props.errors) return;
                    if (changes.type_id !== undefined) setType(props.types.find((item) => item.id === changes.type_id) ?? type);
                    if (changes.area_id !== undefined) setArea(props.areas.find((item) => item.id === changes.area_id) ?? null);
                    if (changes.priority) setPriority(changes.priority);
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Attributes Section',
    component: WTaskAttributesSectionOrganism,
    render: (args) => <AttributesSectionDemo {...args} />,
    tags: ['autodocs'],
    parameters: { layout: 'padded' },
    argTypes: {
        priority: { control: 'select', options: ['low', 'normal', 'high', 'urgent'] },
        canEdit: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
        type: { control: 'object' },
        area: { control: 'object' },
        support: { control: 'object' },
        types: { control: false },
        areas: { control: false },
        onChange: { control: false },
    },
    args: {
        type: TASK_COMPLETO.type,
        area: TASK_COMPLETO.area,
        priority: TASK_COMPLETO.priority,
        support: TASK_COMPLETO.support,
        types: DETAIL_OPTIONS.types,
        areas: DETAIL_OPTIONS.areas,
        canEdit: true,
        busy: false,
        onChange: () => undefined,
    },
    decorators: [
        (Story) => (
            <div className="w-[40rem] max-w-full">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WTaskAttributesSectionOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Melhoria, área Portal do cidadão, prioridade Alta, visto pelo gestor. */
export const Padrao: Story = {};
/** Card de suporte (P52): o bloco "Pedido do cliente", só leitura. */
export const Suporte: Story = {
    args: { type: TASK_SUPORTE.type, area: TASK_SUPORTE.area, priority: TASK_SUPORTE.priority, support: TASK_SUPORTE.support },
};
/** Projeto sem área cadastrada (P18): o campo diz "Nenhuma área no projeto", desabilitado. */
export const SemAreaNoProjeto: Story = { args: { area: TASK_MINIMO.area, priority: TASK_MINIMO.priority, areas: [] } };
/** Tipo desativado na lista geral (G7): continua no card, marcado "(desativado)". */
export const TipoDesativado: Story = { args: { type: { id: 99, slug: null, label: 'Treinamento' } } };
export const SoLeitura: Story = { args: { canEdit: false } };
export const Gravando: Story = { args: { busy: true } };
export const ComErro: Story = {
    args: { errors: { type_id: 'Para trocar para Suporte, informe quem pediu, por onde e quando.' } },
};
