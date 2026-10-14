import { Loader2 } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Checkbox } from '@/components/shadcn/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Label } from '@/components/shadcn/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { Textarea } from '@/components/shadcn/textarea';
import type { BoardCard, BoardOptions, BoardStage } from '@/types/quadro';

export const REWORK_TEXT_MAX = 500;

/** "Foi engano" grava o movimento sem origem nem motivo e não conta retrabalho (P39). */
export type ReworkPayload =
    { mistake: true } | { mistake: false; rework_origin_id: number; rework_reason_id: number; rework_reason_text?: string };

export interface WReworkDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** O card que está voltando. */
    card: BoardCard;
    /** A coluna de destino (papel `sprint` ou `development`). */
    to: BoardStage;
    /** Origem do retrabalho (`validation_types`, P39). */
    origins: BoardOptions['rework_origins'];
    /** Motivos ativos da lista geral (P40); o de `slug === 'other'` pede texto. */
    reasons: BoardOptions['rework_reasons'];
    /** Valores iniciais (stories; a tela normalmente não precisa): tudo vazio e "Foi engano" desmarcado por padrão. */
    initial?: { mistake?: boolean; rework_origin_id?: number; rework_reason_id?: number };
    /** Desabilita, mostra o spinner no botão e impede fechar. */
    loading?: boolean;
    /** Erros do servidor por campo (`rework_origin_id`, `rework_reason_id`, `rework_reason_text`). */
    errors?: Record<string, string>;
    onConfirm: (payload: ReworkPayload) => void;
}

/**
 * Diálogo *Devolver* (F3 §02, P39, P40): abre ao levar o card de Code-review, In test ou
 * Done de volta para To do ou Doing. Pede origem e motivo, com texto quando o motivo é
 * "Outro", ou a marca "foi engano". Cancelar deixa o card onde estava (a tela cuida).
 */
export function WReworkDialogOrganism({ open, onOpenChange, loading = false, ...props }: WReworkDialogOrganismProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            <DialogContent data-slot="w-rework-dialog-organism">
                {/* Desmontado ao fechar: a próxima abertura começa limpa. */}
                <ReworkForm {...props} loading={loading} onCancel={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

type FormProps = Omit<WReworkDialogOrganismProps, 'open' | 'onOpenChange' | 'loading'> & { loading: boolean; onCancel: () => void };

function ReworkForm({ card, to, origins, reasons, initial, loading, errors, onConfirm, onCancel }: FormProps) {
    const formId = useId();
    const [mistake, setMistake] = useState(initial?.mistake ?? false);
    const [originId, setOriginId] = useState(initial?.rework_origin_id ? String(initial.rework_origin_id) : '');
    const [reasonId, setReasonId] = useState(initial?.rework_reason_id ? String(initial.rework_reason_id) : '');
    const [text, setText] = useState('');
    const [tried, setTried] = useState(false);

    const reason = reasons.find((item) => String(item.id) === reasonId);
    const needsText = reason?.slug === 'other';

    const missing = {
        origin: !mistake && originId === '',
        reason: !mistake && reasonId === '',
        text: !mistake && needsText && text.trim() === '',
    };
    const fieldError = (key: string, local: boolean, message: string) => errors?.[key] ?? (tried && local ? message : undefined);
    const originError = fieldError('rework_origin_id', missing.origin, 'Escolha a origem.');
    const reasonError = fieldError('rework_reason_id', missing.reason, 'Escolha o motivo.');
    const textError = fieldError('rework_reason_text', missing.text, 'Descreva o motivo.');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (loading || missing.origin || missing.reason || missing.text) return;
        if (mistake) {
            onConfirm({ mistake: true });
            return;
        }
        onConfirm({
            mistake: false,
            rework_origin_id: Number(originId),
            rework_reason_id: Number(reasonId),
            ...(needsText ? { rework_reason_text: text.trim() } : {}),
        });
    };

    const ids = { mistake: `${formId}-mistake`, origin: `${formId}-origin`, reason: `${formId}-reason`, text: `${formId}-text` };

    return (
        <>
            <DialogHeader>
                <DialogTitle>
                    Devolver {card.key} para {to.name}
                </DialogTitle>
                <DialogDescription>
                    {mistake
                        ? 'O movimento fica gravado sem origem nem motivo e não conta retrabalho.'
                        : 'Diga de onde veio a volta e por quê. Conta um ciclo de retrabalho.'}
                </DialogDescription>
            </DialogHeader>
            <DialogBody>
                <form id={formId} onSubmit={submit} noValidate className="space-y-4">
                    <div className="flex items-start gap-2">
                        <Checkbox
                            id={ids.mistake}
                            checked={mistake}
                            disabled={loading}
                            onCheckedChange={(checked) => setMistake(checked === true)}
                            className="mt-0.5"
                        />
                        <Label htmlFor={ids.mistake} className="leading-snug font-normal">
                            Arrastei para a coluna errada; não é retrabalho
                        </Label>
                    </div>

                    {!mistake && (
                        <>
                            <WFormFieldMolecule label="Origem" htmlFor={ids.origin} required error={originError}>
                                <Select value={originId} onValueChange={setOriginId} disabled={loading}>
                                    <SelectTrigger
                                        id={ids.origin}
                                        aria-invalid={Boolean(originError)}
                                        aria-describedby={originError ? `${ids.origin}-error` : undefined}
                                    >
                                        <SelectValue placeholder="Escolha a origem" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {origins.map((origin) => (
                                            <SelectItem key={origin.id} value={String(origin.id)}>
                                                {origin.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </WFormFieldMolecule>

                            <WFormFieldMolecule label="Motivo" htmlFor={ids.reason} required error={reasonError}>
                                <Select value={reasonId} onValueChange={setReasonId} disabled={loading}>
                                    <SelectTrigger
                                        id={ids.reason}
                                        aria-invalid={Boolean(reasonError)}
                                        aria-describedby={reasonError ? `${ids.reason}-error` : undefined}
                                    >
                                        <SelectValue placeholder="Escolha o motivo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {reasons.map((item) => (
                                            <SelectItem key={item.id} value={String(item.id)}>
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </WFormFieldMolecule>

                            {needsText && (
                                <WFormFieldMolecule
                                    label="Texto do motivo"
                                    htmlFor={ids.text}
                                    required
                                    error={textError}
                                    hint={`Até ${REWORK_TEXT_MAX} caracteres.`}
                                >
                                    <Textarea
                                        value={text}
                                        maxLength={REWORK_TEXT_MAX}
                                        disabled={loading}
                                        onChange={(e) => setText(e.target.value)}
                                    />
                                </WFormFieldMolecule>
                            )}
                        </>
                    )}

                    {!mistake && card.developer && (
                        <p className="text-xs text-muted-foreground">O dev do card será avisado no sistema e por e-mail.</p>
                    )}

                    {errors?.mistake && (
                        <p className="text-xs font-medium text-destructive" role="alert">
                            {errors.mistake}
                        </p>
                    )}
                </form>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={onCancel} disabled={loading}>
                    Cancelar
                </Button>
                <Button type="submit" form={formId} disabled={loading}>
                    {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    Devolver
                </Button>
            </DialogFooter>
        </>
    );
}
