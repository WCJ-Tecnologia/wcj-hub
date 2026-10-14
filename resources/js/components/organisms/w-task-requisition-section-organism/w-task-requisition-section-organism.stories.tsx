import type { Meta, StoryObj } from '@storybook/react-vite';
import { useRef, useState } from 'react';
import { action } from 'storybook/actions';

import type { TaskDetail } from '@/types/quadro';

import { TASK_COMPLETO, TASK_MINIMO } from '../../../../../.storybook/fixtures-ficha';
import {
    requisitionSummary,
    WTaskRequisitionSectionOrganism,
    type WTaskRequisitionSectionOrganismProps,
} from './w-task-requisition-section-organism';

/**
 * Com estado local: *Salvar* grava por 600 ms (`busy`) e devolve a requisição nova, como a página
 * depois da resposta do servidor. O resumo do cabeçalho recolhido aparece acima, para conferir.
 */
function RequisitionSectionDemo(props: WTaskRequisitionSectionOrganismProps) {
    const [requisition, setRequisition] = useState<TaskDetail['requisition']>(props.requisition);
    const [saving, setSaving] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);

    return (
        <div className="grid gap-3">
            <p className="text-xs text-muted-foreground">Resumo: {requisitionSummary(requisition)}</p>
            <WTaskRequisitionSectionOrganism
                {...props}
                requisition={requisition}
                busy={props.busy || saving}
                onSave={(payload) => {
                    action('onSave')(payload);
                    if (props.errors) return;
                    setSaving(true);
                    clearTimeout(timer.current);
                    timer.current = setTimeout(() => {
                        setRequisition((current) => ({ ...current, ...payload }));
                        setSaving(false);
                    }, 600);
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Requisition Section',
    component: WTaskRequisitionSectionOrganism,
    render: (args) => <RequisitionSectionDemo {...args} />,
    tags: ['autodocs'],
    parameters: { layout: 'padded' },
    argTypes: {
        canEdit: { control: 'boolean' },
        busy: { control: 'boolean' },
        defaultEditing: { control: 'boolean' },
        errors: { control: 'object' },
        requisition: { control: 'object' },
        onSave: { control: false },
    },
    args: { requisition: TASK_COMPLETO.requisition, canEdit: true, busy: false, onSave: () => undefined },
    decorators: [
        (Story) => (
            <div className="w-[40rem] max-w-full">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WTaskRequisitionSectionOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Os quatro blocos preenchidos, visto pelo gestor: *Editar* abre o formulário. */
export const Completo: Story = {};
/** Requisição vazia (P17): "Nenhuma requisição." e *Escrever*. */
export const Vazio: Story = { args: { requisition: TASK_MINIMO.requisition } };
/** Só "Sendo" e "Quero": os blocos vazios mostram "—". */
export const Parcial: Story = {
    args: {
        requisition: {
            story_as: 'fiscal da prefeitura',
            story_want: 'ver as guias emitidas no dia',
            story_so_that: null,
            description: null,
        },
    },
};
export const SoLeitura: Story = { args: { canEdit: false } };
export const Gravando: Story = { args: { busy: true, defaultEditing: true } };
export const ComErro: Story = {
    args: { defaultEditing: true, errors: { story_as: 'O campo Sendo não pode passar de 200 caracteres.' } },
};
