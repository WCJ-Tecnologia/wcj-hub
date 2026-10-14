import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn } from 'storybook/test';

import { Button } from '@/components/shadcn/button';

import { CARD_COMPLETO } from '../../../../../.storybook/fixtures-quadro';
import { type ArchivePayload, WArchiveDialogOrganism } from './w-archive-dialog-organism';

interface DemoProps {
    defaultOpen: boolean;
    loading: boolean;
    errors?: Record<string, string>;
    onConfirm: (payload: ArchivePayload) => void;
}

/** Controla o `open`; "Abrir" reabre depois de fechar. */
function ArchiveDialogDemo({ defaultOpen, loading, errors, onConfirm }: DemoProps) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <>
            <Button variant="outline" onClick={() => setOpen(true)}>
                Abrir
            </Button>
            <WArchiveDialogOrganism
                open={open}
                onOpenChange={setOpen}
                card={CARD_COMPLETO}
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
    title: 'Organisms/Archive Dialog',
    component: ArchiveDialogDemo,
    tags: ['autodocs'],
    argTypes: {
        defaultOpen: { control: 'boolean' },
        loading: { control: 'boolean' },
        errors: { control: 'object' },
    },
    args: { defaultOpen: true, loading: false, onConfirm: fn() },
} satisfies Meta<typeof ArchiveDialogDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComErroDoServidor: Story = { args: { errors: { archive_reason: 'O motivo não pode passar de 500 caracteres.' } } };
export const Carregando: Story = { args: { loading: true } };
