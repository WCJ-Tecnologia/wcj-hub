import { FileText, ImageOff, Loader2, Paperclip } from 'lucide-react';
import { type ChangeEvent, useRef, useState } from 'react';

import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { REMOVE_ATTACHMENT_TEXT, coverOf, dateTimeBr, formatBytes } from '@/lib/ficha';
import { cn } from '@/lib/utils';
import type { TaskAttachment } from '@/types/quadro';

export interface WTaskAttachmentsSectionOrganismProps {
    attachments: TaskAttachment[];
    /** `tasks.cover_attachment_id` (P59). */
    coverId: number | null;
    /** `abilities.attach`: anexar e remover (qualquer alocado, P24). */
    canAttach: boolean;
    /** `abilities.update`: *Usar como capa* / *Tirar a capa* (P59). */
    canEdit: boolean;
    busy?: boolean;
    /** Erros do servidor: `file`. */
    errors?: Record<string, string>;
    onUpload: (files: File[]) => void;
    onRemove: (id: number) => void;
    /** `null` = tirar a capa. */
    onSetCover: (id: number | null) => void;
}

/** "3 anexos · capa: tela-guia-ok.png", "1 anexo" ou "Nenhum anexo": a linha do cabeçalho recolhido. */
export function attachmentsSummary(attachments: TaskAttachment[], coverId: number | null): string {
    if (attachments.length === 0) return 'Nenhum anexo';
    const count = `${attachments.length} ${attachments.length === 1 ? 'anexo' : 'anexos'}`;
    const cover = coverOf(attachments, coverId);
    return cover ? `${count} · capa: ${cover.name}` : count;
}

/** Os erros do envio: `file` e, no envio de vários, `file.0`, `files`, `files.1`… (o Laravel indexa por arquivo). */
function uploadErrors(errors: Record<string, string> | undefined): string[] {
    if (!errors) return [];
    return Object.entries(errors)
        .filter(([key]) => key === 'file' || key === 'files' || key.startsWith('file.') || key.startsWith('files.'))
        .map(([, message]) => message);
}

/** Qual botão disparou a gravação em curso: o spinner vai nele. */
type Acting = 'upload' | `remove-${number}` | `cover-${number}`;

/**
 * Seção *Anexos* da ficha do card (F3 §02, §03, §04; P24, P59): a lista na ordem em que os
 * arquivos entraram, com download pelo nome original, *Anexar arquivo*, *Remover* (com
 * confirmação) e *Usar como capa* / *Tirar a capa* nos anexos de imagem. Só o conteúdo: a
 * página põe o cabeçalho recolhível e o resumo (`attachmentsSummary`).
 */
export function WTaskAttachmentsSectionOrganism({
    attachments,
    coverId,
    canAttach,
    canEdit,
    busy = false,
    errors,
    onUpload,
    onRemove,
    onSetCover,
}: WTaskAttachmentsSectionOrganismProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [acting, setActing] = useState<Acting | null>(null);
    const [removing, setRemoving] = useState<TaskAttachment | null>(null);

    const spinning = (key: Acting) => busy && acting === key;
    const fileErrors = uploadErrors(errors);

    const pickFiles = (event: ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(event.target.files ?? []);
        // Limpa o campo: escolher o mesmo arquivo de novo dispara o change outra vez.
        event.target.value = '';
        if (files.length === 0 || busy) return;
        setActing('upload');
        onUpload(files);
    };

    const setCover = (attachment: TaskAttachment, isCover: boolean) => {
        setActing(`cover-${attachment.id}`);
        onSetCover(isCover ? null : attachment.id);
    };

    const confirmRemove = () => {
        if (!removing) return;
        setActing(`remove-${removing.id}`);
        onRemove(removing.id);
        setRemoving(null);
    };

    const uploadButton = canAttach && (
        <>
            <input
                ref={inputRef}
                type="file"
                multiple
                hidden
                tabIndex={-1}
                aria-label="Anexar arquivo"
                onChange={pickFiles}
                disabled={busy}
            />
            <Button variant="outline" size="sm" disabled={busy} onClick={() => inputRef.current?.click()}>
                {spinning('upload') ? <Loader2 className="animate-spin" aria-hidden /> : <Paperclip aria-hidden />}
                Anexar arquivo
            </Button>
        </>
    );

    return (
        <div data-slot="w-task-attachments-section-organism" aria-busy={busy || undefined} className="space-y-3">
            {attachments.length === 0 ? (
                <div className="flex flex-wrap items-center gap-3">
                    <p className="text-sm text-muted-foreground">Nenhum anexo.</p>
                    {uploadButton}
                </div>
            ) : (
                <>
                    <ul aria-label="Anexos" className="divide-y divide-border rounded-md border border-border">
                        {attachments.map((attachment) => {
                            const isCover = attachment.id === coverId;
                            return (
                                <li key={attachment.id} className="flex flex-col gap-3 p-3 sm:flex-row sm:items-center">
                                    <div className="flex min-w-0 flex-1 items-center gap-3">
                                        <Thumbnail attachment={attachment} />
                                        <div className="min-w-0 flex-1 space-y-1">
                                            <div className="flex flex-wrap items-center gap-1.5">
                                                <a
                                                    href={attachment.url}
                                                    download={attachment.name}
                                                    className="min-w-0 truncate text-sm font-medium text-primary-text underline-offset-4 hover:underline"
                                                >
                                                    {attachment.name}
                                                </a>
                                                {isCover && <Badge variant="info">Capa</Badge>}
                                                {attachment.criterion_id !== null && <Badge variant="outline">Evidência do QA</Badge>}
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                <span className="font-mono tabular-nums">{formatBytes(attachment.size)}</span> · por{' '}
                                                {attachment.uploaded_by.name} em{' '}
                                                <span className="font-mono tabular-nums">{dateTimeBr(attachment.uploaded_at)}</span>
                                            </p>
                                        </div>
                                    </div>

                                    {((canEdit && attachment.is_image) || canAttach) && (
                                        <div className="flex flex-wrap gap-2 sm:justify-end">
                                            {canEdit && attachment.is_image && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={busy}
                                                    onClick={() => setCover(attachment, isCover)}
                                                >
                                                    {spinning(`cover-${attachment.id}`) && <Loader2 className="animate-spin" aria-hidden />}
                                                    {isCover ? 'Tirar a capa' : 'Usar como capa'}
                                                    <span className="sr-only">: {attachment.name}</span>
                                                </Button>
                                            )}
                                            {canAttach && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    disabled={busy}
                                                    onClick={() => setRemoving(attachment)}
                                                >
                                                    {spinning(`remove-${attachment.id}`) && (
                                                        <Loader2 className="animate-spin" aria-hidden />
                                                    )}
                                                    Remover
                                                    <span className="sr-only"> {attachment.name}</span>
                                                </Button>
                                            )}
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                    {uploadButton && <div>{uploadButton}</div>}
                </>
            )}

            {fileErrors.map((message) => (
                <p key={message} role="alert" className="text-xs font-medium text-destructive">
                    {message}
                </p>
            ))}

            <WConfirmDialogMolecule
                open={removing !== null}
                onOpenChange={(open) => !open && setRemoving(null)}
                title={removing ? `Remover ${removing.name}?` : ''}
                description={
                    removing?.id === coverId
                        ? `${REMOVE_ATTACHMENT_TEXT} Ele é a capa do card: o card fica sem capa.`
                        : REMOVE_ATTACHMENT_TEXT
                }
                confirmLabel="Remover"
                destructive
                loading={busy}
                onConfirm={confirmRemove}
            />
        </div>
    );
}

/** Miniatura de 48 px da imagem; ícone de arquivo no resto (e se a imagem não carregar). */
function Thumbnail({ attachment }: { attachment: TaskAttachment }) {
    const [broken, setBroken] = useState(false);
    const box = 'size-12 shrink-0 rounded-md border border-border';

    if (attachment.is_image && !broken) {
        return <img src={attachment.url} alt="" loading="lazy" onError={() => setBroken(true)} className={cn(box, 'object-cover')} />;
    }
    const Icon = attachment.is_image ? ImageOff : FileText;
    return (
        <span className={cn(box, 'grid place-items-center bg-muted text-muted-foreground')}>
            <Icon className="size-5" aria-hidden />
        </span>
    );
}
