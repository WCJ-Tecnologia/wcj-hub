import { ATTACHMENTS } from '../../../../../.storybook/fixtures-ficha';
import { BRUNO } from '../../../../../.storybook/fixtures-quadro';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { action } from 'storybook/actions';

import { formatBytes } from '@/lib/ficha';
import type { TaskAttachment } from '@/types/quadro';

import {
    WTaskAttachmentsSectionOrganism,
    type WTaskAttachmentsSectionOrganismProps,
    attachmentsSummary,
} from './w-task-attachments-section-organism';

/**
 * A seção com estado local: anexar, remover e trocar a capa mudam a lista na hora, como depois
 * da resposta do servidor. As intenções aparecem no painel Actions; o resumo do cabeçalho
 * recolhido aparece em cima, como a página o mostraria.
 */
function AttachmentsSectionDemo(props: WTaskAttachmentsSectionOrganismProps) {
    const [attachments, setAttachments] = useState<TaskAttachment[]>(props.attachments);
    const [coverId, setCoverId] = useState<number | null>(props.coverId);

    return (
        <div className="space-y-3">
            <p className="font-mono text-xs text-muted-foreground">Resumo: {attachmentsSummary(attachments, coverId)}</p>
            <WTaskAttachmentsSectionOrganism
                {...props}
                attachments={attachments}
                coverId={coverId}
                onUpload={(files) => {
                    action('onUpload')(files.map((file) => `${file.name} (${formatBytes(file.size)})`));
                    const nextId = Math.max(0, ...attachments.map((item) => item.id)) + 1;
                    setAttachments([
                        ...attachments,
                        ...files.map((file, index) => ({
                            id: nextId + index,
                            name: file.name,
                            size: file.size,
                            is_image: file.type.startsWith('image/'),
                            url: URL.createObjectURL(file),
                            uploaded_by: BRUNO,
                            uploaded_at: '2026-10-14 11:20',
                            criterion_id: null,
                        })),
                    ]);
                }}
                onRemove={(id) => {
                    action('onRemove')(id);
                    setAttachments(attachments.filter((item) => item.id !== id));
                    // Remover o anexo tira a capa (P59).
                    if (id === coverId) setCoverId(null);
                }}
                onSetCover={(id) => {
                    action('onSetCover')(id);
                    setCoverId(id);
                }}
            />
        </div>
    );
}

const meta = {
    title: 'Organisms/Task Attachments Section',
    component: AttachmentsSectionDemo,
    tags: ['autodocs'],
    parameters: { layout: 'padded' },
    decorators: [
        (Story) => (
            <div className="w-[40rem] max-w-full">
                <Story />
            </div>
        ),
    ],
    argTypes: {
        attachments: { control: 'object' },
        coverId: { control: 'number' },
        canAttach: { control: 'boolean' },
        canEdit: { control: 'boolean' },
        busy: { control: 'boolean' },
        errors: { control: 'object' },
        onUpload: { control: false },
        onRemove: { control: false },
        onSetCover: { control: false },
    },
    args: {
        attachments: ATTACHMENTS,
        coverId: 2,
        canAttach: true,
        canEdit: true,
        busy: false,
        onUpload: () => {},
        onRemove: () => {},
        onSetCover: () => {},
    },
} satisfies Meta<typeof AttachmentsSectionDemo>;

export default meta;
type Story = StoryObj<typeof meta>;

/** Gestor: os três anexos do SGM-17, a capa marcada e a evidência do QA do CA_2.2. */
export const Completo: Story = {};

/** Card sem anexo: "Nenhum anexo." e *Anexar arquivo* (F3 §03). */
export const Vazio: Story = { args: { attachments: [], coverId: null } };

/** Sem `attach` nem `update`: só a lista, com o download. */
export const SoLeitura: Story = { args: { canAttach: false, canEdit: false } };

/** Gravação em curso: os botões desabilitados e `aria-busy` no bloco. */
export const Gravando: Story = { args: { busy: true } };

/** O servidor recusou o arquivo. */
export const ComErro: Story = { args: { errors: { file: 'O arquivo não pode passar de 20 MB.' } } };

/** Imagens sem capa escolhida: *Usar como capa* em cada uma. */
export const SemCapa: Story = { args: { coverId: null } };
