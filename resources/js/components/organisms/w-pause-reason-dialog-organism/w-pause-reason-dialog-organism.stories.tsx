import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn } from 'storybook/test';

import { Button } from '@/components/shadcn/button';

import { CARD_COMPLETO, STAGES } from '../../../../../.storybook/fixtures-quadro';
import { type PauseReasonPayload, WPauseReasonDialogOrganism } from './w-pause-reason-dialog-organism';

interface DemoProps {
    defaultOpen: boolean;
    loading: boolean;
    errors?: Record<string, string>;
    onConfirm: (payload: PauseReasonPayload) => void;
}

/** Controla o `open`; "Abrir" reabre depois de fechar. */
function PauseReasonDialogDemo({ defaultOpen, loading, errors, onConfirm }: DemoProps) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <>
            <Button variant="outline" onClick={() => setOpen(true)}>
                Abrir
            </Button>
            <WPauseReasonDialogOrganism
                open={open}
                onOpenChange={setOpen}
                card={CARD_COMPLETO}
                stage={STAGES[3]}
                loading={loading}
                errors={errors}
                onConfirm={(payload) => {
                    onConfirm(payload);
                    setOpen(false);
                }}
            />
        </>
    );
}

const meta = {
    title: 'Organisms/Pause Reason Dialog',
    component: PauseReasonDialogDemo,
    tags: ['autodocs'],
    argTypes: {
        defaultOpen: { control: 'boolean' },
        loading: { control: 'boolean' },
        errors: { control: 'object' },
    },
    args: { defaultOpen: true, loading: false, onConfirm: fn() },
} satisfies Meta<typeof PauseReasonDialogDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComErroDoServidor: Story = { args: { errors: { pause_reason: 'O motivo da pausa não pode passar de 500 caracteres.' } } };
export const Carregando: Story = { args: { loading: true } };
