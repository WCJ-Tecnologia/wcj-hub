import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';
import { fn } from 'storybook/test';

import { Toaster } from '@/components/shadcn/sonner';

import {
    ABILITIES_FICHA_DEV,
    ABILITIES_FICHA_LEITURA,
    DETAIL_OPTIONS,
    TASK_ARQUIVADO,
    TASK_COMPLETO,
    TASK_MINIMO,
    TASK_SUPORTE,
} from '../../../../../.storybook/fixtures-ficha';
import { WTaskDetailHeaderOrganism, type WTaskDetailHeaderOrganismProps } from './w-task-detail-header-organism';

/** Com estado local: renomear troca o título na hora; o resto vai para o Actions. */
function HeaderDemo(props: WTaskDetailHeaderOrganismProps) {
    const [title, setTitle] = useState(props.detail.title);
    return (
        <>
            <WTaskDetailHeaderOrganism
                {...props}
                detail={{ ...props.detail, title }}
                onRenameTitle={(next) => {
                    action('onRenameTitle')(next);
                    setTitle(next);
                }}
            />
            <Toaster />
        </>
    );
}

const meta = {
    title: 'Organisms/Task Detail Header',
    component: WTaskDetailHeaderOrganism,
    render: (args) => <HeaderDemo {...args} />,
    tags: ['autodocs'],
    argTypes: {
        detail: { control: 'object' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
        stages: { control: false },
    },
    args: {
        detail: TASK_COMPLETO,
        stages: DETAIL_OPTIONS.stages,
        onRenameTitle: fn(),
        onMove: fn(),
        onArchive: fn(),
        onUnarchive: fn(),
        onDelete: fn(),
    },
    parameters: { layout: 'padded' },
    decorators: [
        (Story) => (
            <div className="w-[64rem] max-w-full">
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof WTaskDetailHeaderOrganism>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor: tudo no menu; o card é filho, tem capa, está atrasado e com reestimativa proposta. */
export const Completo: Story = {};
/** Nunca usado: *Excluir* aparece no menu (P26). */
export const NuncaUsado: Story = { args: { detail: TASK_MINIMO } };
/** Dev: sem *Arquivar* nem *Excluir*; *Mover para…* segue. */
export const ComoDev: Story = { args: { detail: { ...TASK_COMPLETO, abilities: ABILITIES_FICHA_DEV } } };
/** Arquivado: a situação diz quando e por quê; *Desarquivar* no lugar de *Arquivar*. */
export const Arquivado: Story = { args: { detail: TASK_ARQUIVADO } };
/** Suporte em pausa: marcas de pausa (com motivo) e de suporte (quem pediu). */
export const SuporteEmPausa: Story = { args: { detail: TASK_SUPORTE } };
export const SoLeitura: Story = { args: { detail: { ...TASK_COMPLETO, abilities: ABILITIES_FICHA_LEITURA } } };
export const Gravando: Story = { args: { busy: true } };
export const ComErro: Story = { args: { errors: { title: 'O título precisa ter até 200 caracteres.' } } };
