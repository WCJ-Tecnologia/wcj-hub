import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn } from 'storybook/test';

import { Button } from '@/components/shadcn/button';

import { BOARD_PROJECT_SEM_SPRINT, CARDS } from '../../../../../.storybook/fixtures-quadro';
import { type NewSprintPayload, WNewSprintDialogOrganism } from './w-new-sprint-dialog-organism';

/** SGM-21, no Backlog: é o card que entra na sprint nova. */
const CARD_DO_BACKLOG = CARDS.find((item) => item.key === 'SGM-21')!;

interface DemoProps {
    defaultOpen: boolean;
    loading: boolean;
    errors?: Record<string, string>;
    /** Hoje, `AAAA-MM-DD`: o início proposto. */
    today: string;
    onConfirm: (payload: NewSprintPayload) => void;
}

/** Controla o `open`; "Abrir" reabre depois de fechar. */
function NewSprintDialogDemo({ defaultOpen, loading, errors, today, onConfirm }: DemoProps) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <>
            <Button variant="outline" onClick={() => setOpen(true)}>
                Abrir
            </Button>
            <WNewSprintDialogOrganism
                open={open}
                onOpenChange={setOpen}
                card={CARD_DO_BACKLOG}
                project={BOARD_PROJECT_SEM_SPRINT}
                today={today}
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
    title: 'Organisms/New Sprint Dialog',
    component: NewSprintDialogDemo,
    tags: ['autodocs'],
    argTypes: {
        defaultOpen: { control: 'boolean' },
        loading: { control: 'boolean' },
        errors: { control: 'object' },
        today: { control: 'text' },
    },
    // Terça 06/10/2026 com 10 dias úteis: fim proposto na segunda 19/10/2026 (exemplo do F5 §01).
    args: { defaultOpen: true, loading: false, today: '2026-10-06', onConfirm: fn() },
} satisfies Meta<typeof NewSprintDialogDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const ComErroDoServidor: Story = { args: { errors: { name: 'Já existe a Sprint 4 neste projeto.' } } };
export const Carregando: Story = { args: { loading: true } };
