import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn } from 'storybook/test';

import { Button } from '@/components/shadcn/button';
import type { BoardOptions } from '@/types/quadro';

import { BOARD_OPTIONS } from '../../../../../.storybook/fixtures-quadro';
import { type SupportCardPayload, WSupportCardDialogOrganism } from './w-support-card-dialog-organism';

interface DemoProps {
    defaultOpen: boolean;
    loading: boolean;
    errors?: Record<string, string>;
    contacts: BoardOptions['contacts'];
    onConfirm: (payload: SupportCardPayload) => void;
}

/** Controla o `open`; "Abrir" reabre depois de fechar. O "agora" é fixo, para a story não mudar. */
function SupportCardDialogDemo({ defaultOpen, loading, errors, contacts, onConfirm }: DemoProps) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <>
            <Button variant="outline" onClick={() => setOpen(true)}>
                Abrir
            </Button>
            <WSupportCardDialogOrganism
                open={open}
                onOpenChange={setOpen}
                contacts={contacts}
                channels={BOARD_OPTIONS.channels}
                now={{ date: '2026-10-14', time: '09:30' }}
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
    title: 'Organisms/Support Card Dialog',
    component: SupportCardDialogDemo,
    tags: ['autodocs'],
    argTypes: {
        defaultOpen: { control: 'boolean' },
        loading: { control: 'boolean' },
        errors: { control: 'object' },
        contacts: { control: 'object' },
    },
    args: { defaultOpen: true, loading: false, contacts: BOARD_OPTIONS.contacts, onConfirm: fn() },
} satisfies Meta<typeof SupportCardDialogDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const SemContato: Story = { args: { contacts: [] } };
export const ComErroDoServidor: Story = {
    args: {
        errors: {
            title: 'Informe o título.',
            requester_contact_id: 'O contato escolhido foi desativado. Escolha outro.',
            requested_at: 'A data não pode ser no futuro.',
        },
    },
};
export const Carregando: Story = { args: { loading: true } };
