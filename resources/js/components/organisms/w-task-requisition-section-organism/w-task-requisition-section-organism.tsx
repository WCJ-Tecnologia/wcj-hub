import { Loader2, Pencil } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { Textarea } from '@/components/shadcn/textarea';
import { requisitionIsEmpty } from '@/lib/ficha';
import { cn } from '@/lib/utils';
import type { TaskDetail, TaskPatchPayload } from '@/types/quadro';

/** `tasks.story_as`: texto até 200 (F3 §02). */
export const STORY_AS_MAX = 200;

type RequisitionField = 'story_as' | 'story_want' | 'story_so_that' | 'description';
type RequisitionPayload = Pick<TaskPatchPayload, RequisitionField>;

/** Os quatro blocos do documento de requisição, na ordem do F3 §02 (P17). */
const BLOCKS: { field: RequisitionField; label: string }[] = [
    { field: 'story_as', label: 'Sendo' },
    { field: 'story_want', label: 'Quero' },
    { field: 'story_so_that', label: 'Para' },
    { field: 'description', label: 'Informações adicionais' },
];

const SUMMARY_MAX = 120;

/** Uma linha só, sem quebras nem espaços repetidos. */
const oneLine = (text: string | null) => (text ?? '').replace(/\s+/g, ' ').trim();

/**
 * Resumo do cabeçalho recolhido: "Sendo contribuinte…, quero emitir a 2ª via…, para pagar…", em até
 * 120 letras; só as informações adicionais, quando as três frases estão vazias; "Sem requisição" sem nada.
 */
export function requisitionSummary(requisition: TaskDetail['requisition']): string {
    if (requisitionIsEmpty(requisition)) return 'Sem requisição';
    const story = [
        ['Sendo', oneLine(requisition.story_as)],
        ['quero', oneLine(requisition.story_want)],
        ['para', oneLine(requisition.story_so_that)],
    ]
        .filter(([, text]) => text !== '')
        .map(([word, text]) => `${word} ${text}`)
        .join(', ');
    const text = story === '' ? oneLine(requisition.description) : story.charAt(0).toLocaleUpperCase('pt-BR') + story.slice(1);
    return text.length > SUMMARY_MAX ? `${text.slice(0, SUMMARY_MAX - 1).trimEnd()}…` : text;
}

export interface WTaskRequisitionSectionOrganismProps {
    requisition: TaskDetail['requisition'];
    canEdit: boolean;
    busy?: boolean;
    /** Erros do servidor: `story_as`, `story_want`, `story_so_that`, `description`. */
    errors?: Record<string, string>;
    onSave: (payload: Pick<TaskPatchPayload, 'story_as' | 'story_want' | 'story_so_that' | 'description'>) => void;
    /** Começa no formulário (stories e a volta de um erro do servidor com a página recarregada). */
    defaultEditing?: boolean;
    className?: string;
}

/**
 * Documento de requisição da ficha (F3 §02, P17): Sendo / Quero / Para / Informações adicionais.
 * Lê em `dl`; com permissão, *Editar* troca para o formulário, e *Salvar* devolve os quatro campos
 * por `onSave` (texto vazio vira null). O formulário fecha quando a gravação termina sem erro nos
 * quatro campos; *Cancelar* volta ao que veio.
 */
export function WTaskRequisitionSectionOrganism({
    requisition,
    canEdit,
    busy = false,
    errors,
    onSave,
    defaultEditing = false,
    className,
}: WTaskRequisitionSectionOrganismProps) {
    const [editing, setEditing] = useState(defaultEditing);
    // Fecha o formulário quando a gravação disparada por ele termina sem erro (busy: true → false).
    const [phase, setPhase] = useState<'idle' | 'sent' | 'saving'>('idle');
    const fieldErrors = BLOCKS.some(({ field }) => errors?.[field]);

    if (phase === 'sent' && busy) setPhase('saving');
    if (phase === 'saving' && !busy) {
        setPhase('idle');
        if (!fieldErrors) setEditing(false);
    }

    const showForm = editing && canEdit;

    return (
        <div data-slot="w-task-requisition-section-organism" aria-busy={busy || undefined} className={cn('grid gap-3', className)}>
            {showForm ? (
                <RequisitionForm
                    requisition={requisition}
                    busy={busy}
                    errors={errors}
                    onCancel={() => {
                        setPhase('idle');
                        setEditing(false);
                    }}
                    onSave={(payload) => {
                        setPhase('sent');
                        onSave(payload);
                    }}
                />
            ) : requisitionIsEmpty(requisition) ? (
                <p className="text-sm text-muted-foreground">
                    Nenhuma requisição.
                    {canEdit && (
                        <>
                            {' '}
                            <Button type="button" variant="link" className="h-auto p-0" disabled={busy} onClick={() => setEditing(true)}>
                                Escrever
                            </Button>
                        </>
                    )}
                </p>
            ) : (
                <>
                    {canEdit && (
                        <div className="flex justify-end">
                            <Button type="button" variant="outline" size="sm" disabled={busy} onClick={() => setEditing(true)}>
                                <Pencil aria-hidden /> Editar
                            </Button>
                        </div>
                    )}
                    <dl className="grid gap-3">
                        {BLOCKS.map(({ field, label }) => {
                            const text = requisition[field]?.trim();
                            return (
                                <div key={field} className="grid gap-1">
                                    <dt className="font-mono text-xs tracking-wider text-muted-foreground uppercase">{label}</dt>
                                    <dd className={cn('text-sm whitespace-pre-wrap', !text && 'text-muted-foreground')}>{text || '—'}</dd>
                                </div>
                            );
                        })}
                    </dl>
                </>
            )}
        </div>
    );
}

interface FormProps {
    requisition: TaskDetail['requisition'];
    busy: boolean;
    errors?: Record<string, string>;
    onCancel: () => void;
    onSave: (payload: RequisitionPayload) => void;
}

/** Montado só durante a edição: cada *Editar* começa do valor atual. */
function RequisitionForm({ requisition, busy, errors, onCancel, onSave }: FormProps) {
    const formId = useId();
    const [values, setValues] = useState<Record<RequisitionField, string>>({
        story_as: requisition.story_as ?? '',
        story_want: requisition.story_want ?? '',
        story_so_that: requisition.story_so_that ?? '',
        description: requisition.description ?? '',
    });

    const set = (field: RequisitionField) => (event: { target: { value: string } }) =>
        setValues((current) => ({ ...current, [field]: event.target.value }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (busy) return;
        const clean = (text: string) => (text.trim() === '' ? null : text.trim());
        onSave({
            story_as: clean(values.story_as),
            story_want: clean(values.story_want),
            story_so_that: clean(values.story_so_that),
            description: clean(values.description),
        });
    };

    const fieldId = (field: RequisitionField) => `${formId}-${field}`;

    return (
        <form onSubmit={submit} noValidate className="grid gap-3">
            <WFormFieldMolecule
                label="Sendo"
                htmlFor={fieldId('story_as')}
                error={errors?.story_as}
                hint="Quem é a pessoa: 'contribuinte com IPTU em aberto'"
            >
                <Input value={values.story_as} maxLength={STORY_AS_MAX} disabled={busy} autoFocus onChange={set('story_as')} />
            </WFormFieldMolecule>
            <WFormFieldMolecule label="Quero" htmlFor={fieldId('story_want')} error={errors?.story_want}>
                <Textarea value={values.story_want} disabled={busy} onChange={set('story_want')} />
            </WFormFieldMolecule>
            <WFormFieldMolecule label="Para" htmlFor={fieldId('story_so_that')} error={errors?.story_so_that}>
                <Textarea value={values.story_so_that} disabled={busy} onChange={set('story_so_that')} />
            </WFormFieldMolecule>
            <WFormFieldMolecule
                label="Informações adicionais"
                htmlFor={fieldId('description')}
                error={errors?.description}
                hint="Imagens vão como anexo"
            >
                <Textarea value={values.description} disabled={busy} className="min-h-28" onChange={set('description')} />
            </WFormFieldMolecule>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" disabled={busy} onClick={onCancel}>
                    Cancelar
                </Button>
                <Button type="submit" disabled={busy}>
                    {busy && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    Salvar
                </Button>
            </div>
        </form>
    );
}
