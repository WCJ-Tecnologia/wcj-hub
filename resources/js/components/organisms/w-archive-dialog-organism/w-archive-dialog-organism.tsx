import { Loader2 } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Textarea } from '@/components/shadcn/textarea';
import type { BoardCard } from '@/types/quadro';

export const ARCHIVE_REASON_MAX = 500;

export interface ArchivePayload {
    archive_reason: string;
}

export interface WArchiveDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** O card a arquivar. */
    card: BoardCard;
    /** Desabilita, mostra o spinner no botão e impede fechar. */
    loading?: boolean;
    /** Erros do servidor por campo (`archive_reason`). */
    errors?: Record<string, string>;
    onConfirm: (payload: ArchivePayload) => void;
}

/**
 * Diálogo *Arquivar* (F3 §02, P26): tira do quadro o card com histórico, com motivo.
 * Arquivar é reversível (*Desarquivar* devolve ao Backlog), por isso o botão não é vermelho.
 */
export function WArchiveDialogOrganism({ open, onOpenChange, card, loading = false, errors, onConfirm }: WArchiveDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            <DialogContent data-slot="w-archive-dialog-organism">
                {/* Desmontado ao fechar: a próxima abertura começa limpa. */}
                <ArchiveForm card={card} loading={loading} errors={errors} onCancel={() => onOpenChange(false)} onConfirm={onConfirm} />
            </DialogContent>
        </Dialog>
    );
}

interface FormProps {
    card: BoardCard;
    loading: boolean;
    errors?: Record<string, string>;
    onCancel: () => void;
    onConfirm: (payload: ArchivePayload) => void;
}

function ArchiveForm({ card, loading, errors, onCancel, onConfirm }: FormProps) {
    const formId = useId();
    const [reason, setReason] = useState('');
    const [tried, setTried] = useState(false);

    const error = errors?.archive_reason ?? (tried && reason.trim() === '' ? 'Informe o motivo.' : undefined);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (reason.trim() === '' || loading) return;
        onConfirm({ archive_reason: reason.trim() });
    };

    return (
        <>
            <DialogHeader>
                <DialogTitle>Arquivar {card.key}?</DialogTitle>
                <DialogDescription>Ele sai do quadro e volta pelo filtro Arquivados.</DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id={formId} onSubmit={submit} noValidate>
                    <WFormFieldMolecule
                        label="Motivo"
                        htmlFor={`${formId}-archive_reason`}
                        required
                        error={error}
                        hint={`Até ${ARCHIVE_REASON_MAX} caracteres.`}
                    >
                        <Textarea
                            value={reason}
                            maxLength={ARCHIVE_REASON_MAX}
                            disabled={loading}
                            autoFocus
                            onChange={(e) => setReason(e.target.value)}
                        />
                    </WFormFieldMolecule>
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onCancel} disabled={loading}>
                    Cancelar
                </Button>
                <Button type="submit" form={formId} disabled={loading}>
                    {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    Arquivar
                </Button>
            </DialogFooter>
        </>
    );
}
