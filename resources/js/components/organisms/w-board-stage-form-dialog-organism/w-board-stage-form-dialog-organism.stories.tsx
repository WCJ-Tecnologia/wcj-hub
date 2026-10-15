import { useForm } from '@inertiajs/react';
import type { Meta, StoryObj } from '@storybook/react-vite';

import { STAGE_ROLES, STAGES } from '../../../../../.storybook/fixtures-configuracao';
import { type StageFormData, WBoardStageFormDialogOrganism } from './w-board-stage-form-dialog-organism';

interface DemoProps {
    /** Índice da etapa em edição em `STAGES`; -1 = nova. */
    editingIndex: number;
}

function StageFormDemo({ editingIndex }: DemoProps) {
    const editing = editingIndex >= 0 ? STAGES[editingIndex] : null;
    const form = useForm<StageFormData>({ name: editing?.name ?? '', stage_role_id: editing?.role_id ?? 3 });
    return (
        <WBoardStageFormDialogOrganism
            open
            onOpenChange={() => undefined}
            form={form}
            editing={editing}
            roles={STAGE_ROLES}
            onSubmit={(event) => {
                event.preventDefault();
                form.post('/projetos/1/etapas');
            }}
        />
    );
}

const meta = {
    title: 'Organisms/Etapa do quadro',
    component: StageFormDemo,
    tags: ['autodocs'],
    argTypes: { editingIndex: { control: { type: 'number', min: -1, max: 7 } } },
    args: { editingIndex: -1 },
    parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof StageFormDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Nova: Story = {};
export const EditarSemHistorico: Story = { args: { editingIndex: 6 } };
export const EditarPapelCongelado: Story = { args: { editingIndex: 4 } };
export const EditarPapelUnico: Story = { args: { editingIndex: 1 } };
