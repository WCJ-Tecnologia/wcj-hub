import { Loader2 } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Textarea } from '@/components/shadcn/textarea';
import type { BoardCard, BoardStage } from '@/types/quadro';

export const PAUSE_REASON_MAX = 500;

export interface PauseReasonPayload {
    pause_reason: string;
}

export interface WPauseReasonDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** O card que está indo para a pausa. */
    card: BoardCard;
    /** A coluna de pausa (papel `paused`): o nome dela vai no texto. */
    stage: BoardStage;
    /** Desabilita, mostra o spinner no botão e impede fechar. */
    loading?: boolean;
    /** Erros do servidor por campo (`pause_reason`), do `useForm`/`onError` da tela. */
    errors?: Record<string, string>;
    onConfirm: (payload: PauseReasonPayload) => void;
}

/**
 * Diálogo *Motivo da pausa* (F3 §02, P15): abre ao levar o card para a coluna de pausa
 * quando o projeto exige o motivo. Cancelar deixa o card onde estava (a tela cuida).
 */
export function WPauseReasonDialogOrganism({
    open,
    onOpenChange,
    card,
    stage,
    loading = false,
    errors,
    onConfirm,
}: WPauseReasonDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            <DialogContent data-slot="w-pause-reason-dialog-organism">
                {/* O formulário mora dentro do conteúdo: o Radix o desmonta ao fechar, e o campo volta vazio na próxima abertura. */}
                <PauseReasonForm
                    card={card}
                    stage={stage}
                    loading={loading}
                    errors={errors}
                    onCancel={() => onOpenChange(false)}
                    onConfirm={onConfirm}
                />
            </DialogContent>
        </Dialog>
    );
}

interface FormProps {
    card: BoardCard;
    stage: BoardStage;
    loading: boolean;
    errors?: Record<string, string>;
    onCancel: () => void;
    onConfirm: (payload: PauseReasonPayload) => void;
}

function PauseReasonForm({ card, stage, loading, errors, onCancel, onConfirm }: FormProps) {
    const formId = useId();
    const [reason, setReason] = useState('');
    const [tried, setTried] = useState(false);

    const localError = tried && reason.trim() === '' ? 'Informe o motivo da pausa.' : undefined;
    const error = errors?.pause_reason ?? localError;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (reason.trim() === '' || loading) return;
        onConfirm({ pause_reason: reason.trim() });
    };

    return (
        <>
            <DialogHeader>
                <DialogTitle>Pausar {card.key}</DialogTitle>
                <DialogDescription>O card vai para {stage.name}. Diga o motivo; ele fica no histórico.</DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id={formId} onSubmit={submit} noValidate>
                    <WFormFieldMolecule
                        label="Motivo da pausa"
                        htmlFor={`${formId}-pause_reason`}
                        required
                        error={error}
                        hint={`Até ${PAUSE_REASON_MAX} caracteres.`}
                    >
                        <Textarea
                            value={reason}
                            maxLength={PAUSE_REASON_MAX}
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
                    Pausar
                </Button>
            </DialogFooter>
        </>
    );
}
