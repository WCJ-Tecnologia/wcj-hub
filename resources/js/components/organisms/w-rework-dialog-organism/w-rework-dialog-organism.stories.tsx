import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { fn } from 'storybook/test';

import { Button } from '@/components/shadcn/button';

import { BOARD_OPTIONS, CARDS, STAGES } from '../../../../../.storybook/fixtures-quadro';
import { type ReworkPayload, WReworkDialogOrganism, type WReworkDialogOrganismProps } from './w-rework-dialog-organism';

/** SGM-14, em In test, com dev: volta para Doing. */
const CARD_EM_TESTE = CARDS.find((item) => item.key === 'SGM-14')!;

interface DemoProps {
    defaultOpen: boolean;
    loading: boolean;
    errors?: Record<string, string>;
    initial?: WReworkDialogOrganismProps['initial'];
    onConfirm: (payload: ReworkPayload) => void;
}

/** Controla o `open`; "Abrir" reabre depois de fechar. */
function ReworkDialogDemo({ defaultOpen, loading, errors, initial, onConfirm }: DemoProps) {
    const [open, setOpen] = useState(defaultOpen);
    return (
        <>
            <Button variant="outline" onClick={() => setOpen(true)}>
                Abrir
            </Button>
            <WReworkDialogOrganism
                open={open}
                onOpenChange={setOpen}
                card={CARD_EM_TESTE}
                to={STAGES[2]}
                origins={BOARD_OPTIONS.rework_origins}
                reasons={BOARD_OPTIONS.rework_reasons}
                initial={initial}
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
    title: 'Organisms/Rework Dialog',
    component: ReworkDialogDemo,
    tags: ['autodocs'],
    argTypes: {
        defaultOpen: { control: 'boolean' },
        loading: { control: 'boolean' },
        errors: { control: 'object' },
        initial: { control: 'object' },
    },
    args: { defaultOpen: true, loading: false, onConfirm: fn() },
} satisfies Meta<typeof ReworkDialogDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Padrao: Story = {};
export const FoiEngano: Story = { args: { initial: { mistake: true } } };
export const MotivoOutro: Story = { args: { initial: { rework_origin_id: 3, rework_reason_id: 5 } } };
export const ComErroDoServidor: Story = {
    args: {
        initial: { rework_origin_id: 3 },
        errors: { rework_reason_id: 'O motivo escolhido foi desativado. Escolha outro.' },
    },
};
export const Carregando: Story = { args: { loading: true, initial: { rework_origin_id: 3, rework_reason_id: 3 } } };
