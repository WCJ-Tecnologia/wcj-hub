import { Loader2 } from 'lucide-react';
import { type FormEvent, useId, useState } from 'react';

import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { dueChangeText, previewDueOn, proposalText, remainingHours } from '@/lib/ficha';
import { hours } from '@/lib/quadro';
import { dateBr } from '@/lib/utils';
import type { TaskAbilities, TaskDetail } from '@/types/quadro';

export interface WTaskHoursSectionOrganismProps {
    detail: Pick<
        TaskDetail,
        | 'key'
        | 'estimated_hours'
        | 'proposed_hours'
        | 'proposed_by'
        | 'logged_hours'
        | 'due_on'
        | 'development_started_on'
        | 'developer_daily_hours'
    >;
    abilities: Pick<TaskAbilities, 'set_hours' | 'propose_hours' | 'decide_hours'>;
    busy?: boolean;
    /** Erros do servidor: `estimated_hours`, `hours`. */
    errors?: Record<string, string>;
    /** Gestor e Administrador: definir ou reestimar; `null` limpa (P27). */
    onSetHours: (hours: number | null) => void;
    /** Desenvolvedor: propor um valor (P27). */
    onPropose: (hours: number) => void;
    onApprove: () => void;
    onReject: () => void;
}

/**
 * "16 h previstas · 11,5 h lançadas · faltam 4,5 h · proposta de 24 h" ou "Sem horas previstas"
 * (com "· 3 h lançadas" quando já há lançamento): a linha do cabeçalho recolhido.
 */
export function hoursSummary(detail: WTaskHoursSectionOrganismProps['detail']): string {
    const parts: string[] = [];
    if (detail.estimated_hours === null) parts.push('Sem horas previstas');
    else parts.push(`${hours(detail.estimated_hours)} previstas`);
    if (detail.estimated_hours !== null || detail.logged_hours > 0) parts.push(`${hours(detail.logged_hours)} lançadas`);
    const remaining = remainingHours(detail.estimated_hours, detail.logged_hours);
    if (remaining !== null) parts.push(`faltam ${hours(remaining)}`);
    if (detail.proposed_hours !== null) parts.push(`proposta de ${hours(detail.proposed_hours)}`);
    return parts.join(' · ');
}

const INVALID_TEXT = 'Informe horas maiores que zero';

/** Qual campo inline está aberto: definir/reestimar (gestor) ou propor (dev). */
type Mode = 'set' | 'propose';

/** Qual botão disparou a gravação em curso: o spinner vai nele. */
type Acting = Mode | 'approve' | 'reject';

/** O que espera o *Confirmar*: reestimar com data nova, ou limpar as horas. */
type Pending = { kind: 'reestimate'; value: number; text: string } | { kind: 'clear' };

/** "16" → 16; "" → null; "1,5" também vale (o navegador pode mandar a vírgula). */
function parseHours(raw: string): number | null {
    const text = raw.trim().replace(',', '.');
    return text === '' ? null : Number(text);
}

/**
 * Seção *Horas* da ficha do card (F5 §02 "Horas e data prevista"; F3 §02, §04; P27, P28, P30):
 * horas previstas, data prevista, horas lançadas e o que falta; *Definir* / *Reestimar* para o
 * gestor e o Administrador, com a confirmação "A data prevista vai de DD/MM para DD/MM" quando a
 * data muda (P28); *Propor outro valor* para o dev, e *Aprovar* / *Recusar* a proposta pendente.
 * A prévia da data é só para a frase: a conta que vale é a do servidor.
 * Só o conteúdo: a página põe o cabeçalho recolhível e o resumo (`hoursSummary`).
 */
export function WTaskHoursSectionOrganism({
    detail,
    abilities,
    busy = false,
    errors,
    onSetHours,
    onPropose,
    onApprove,
    onReject,
}: WTaskHoursSectionOrganismProps) {
    const formId = useId();
    const [mode, setMode] = useState<Mode | null>(
        errors?.estimated_hours && abilities.set_hours ? 'set' : errors?.hours && abilities.propose_hours ? 'propose' : null,
    );
    const [value, setValue] = useState(detail.estimated_hours !== null ? String(detail.estimated_hours) : '');
    const [tried, setTried] = useState(false);
    const [acting, setActing] = useState<Acting | null>(null);
    const [pending, setPending] = useState<Pending | null>(null);

    // O campo fecha ao gravar; se o servidor recusar, ele reabre com o valor digitado e o erro.
    const setError = errors?.estimated_hours;
    const proposeError = errors?.hours;
    // Ajuste no render (não em efeito): erro novo do servidor reabre o campo dele.
    const [seen, setSeen] = useState({ set: setError, propose: proposeError });
    if (seen.set !== setError || seen.propose !== proposeError) {
        setSeen({ set: setError, propose: proposeError });
        if (setError && setError !== seen.set && abilities.set_hours) setMode('set');
        else if (proposeError && proposeError !== seen.propose && abilities.propose_hours) setMode('propose');
    }

    const spinning = (key: Acting) => busy && acting === key;
    const hasProposal = detail.proposed_hours !== null;
    const remaining = remainingHours(detail.estimated_hours, detail.logged_hours);

    const open = (next: Mode) => {
        setValue(next === 'set' && detail.estimated_hours !== null ? String(detail.estimated_hours) : '');
        setTried(false);
        setMode(next);
    };

    const parsed = parseHours(value);
    const invalid = parsed !== null && (!Number.isFinite(parsed) || parsed <= 0);
    // Vazio só vale para limpar horas que existem; no resto é o mesmo erro do valor inválido.
    const emptyNotAllowed = parsed === null && (mode === 'propose' || detail.estimated_hours === null);
    const localError = tried && (invalid || emptyNotAllowed) ? INVALID_TEXT : undefined;

    const run = (key: Acting, call: () => void) => {
        setActing(key);
        call();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        if (busy || invalid || emptyNotAllowed || mode === null) return;

        if (mode === 'propose') {
            run('propose', () => onPropose(parsed as number));
            setMode(null);
            return;
        }

        if (parsed === null) {
            setPending({ kind: 'clear' });
            return;
        }
        if (parsed === detail.estimated_hours) {
            setMode(null);
            return;
        }
        const text = dueChangeText(detail.due_on, previewDueOn(detail, parsed));
        if (text) {
            setPending({ kind: 'reestimate', value: parsed, text });
            return;
        }
        run('set', () => onSetHours(parsed));
        setMode(null);
    };

    const confirmPending = () => {
        if (!pending) return;
        run('set', () => onSetHours(pending.kind === 'clear' ? null : pending.value));
        setPending(null);
        setMode(null);
    };

    const fieldId = `${formId}-hours`;
    const isSet = mode === 'set';

    return (
        <div data-slot="w-task-hours-section-organism" aria-busy={busy || undefined} className="space-y-4">
            <dl className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <Fact label="Horas previstas" value={detail.estimated_hours !== null ? hours(detail.estimated_hours) : '—'} />
                <Fact label="Data prevista" value={dateBr(detail.due_on)} />
                <Fact label="Horas lançadas" value={hours(detail.logged_hours)} />
                <Fact label="Faltam" value={remaining !== null ? hours(remaining) : '—'} />
            </dl>
            <p className="text-xs text-muted-foreground">Data prevista: calculada na 1ª entrada em Doing; vazia sem dev ou sem horas.</p>

            {hasProposal && (
                <div role="status" className="space-y-3 rounded-md border border-status-pending/40 bg-status-pending-bg/40 p-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="warning">Reestimativa proposta</Badge>
                        <p className="text-sm">
                            <span className="font-mono tabular-nums">{proposalText(hours(detail.proposed_hours as number))}</span>
                            {detail.proposed_by && <span className="text-muted-foreground"> · por {detail.proposed_by.name}</span>}
                        </p>
                    </div>
                    {abilities.decide_hours && (
                        <div className="flex flex-wrap gap-2">
                            <Button size="sm" disabled={busy} onClick={() => run('approve', onApprove)}>
                                {spinning('approve') && <Loader2 className="animate-spin" aria-hidden />}
                                Aprovar
                            </Button>
                            <Button size="sm" variant="outline" disabled={busy} onClick={() => run('reject', onReject)}>
                                {spinning('reject') && <Loader2 className="animate-spin" aria-hidden />}
                                Recusar
                            </Button>
                        </div>
                    )}
                </div>
            )}

            {mode !== null ? (
                <form id={formId} onSubmit={submit} noValidate className="flex flex-wrap items-start gap-2">
                    <WFormFieldMolecule
                        className="w-48"
                        label={isSet ? 'Horas previstas' : 'Proposta de horas'}
                        htmlFor={fieldId}
                        error={(isSet ? setError : proposeError) ?? localError}
                        hint={isSet ? 'Total da tarefa, dev + QA' : undefined}
                    >
                        <Input
                            type="number"
                            inputMode="decimal"
                            step="0.5"
                            min="0.5"
                            value={value}
                            disabled={busy}
                            autoFocus
                            className="font-mono tabular-nums"
                            onChange={(event) => setValue(event.target.value)}
                        />
                    </WFormFieldMolecule>
                    {/* Alinha os botões com o campo, abaixo do rótulo. */}
                    <div className="flex gap-2 pt-[1.625rem]">
                        <Button type="submit" size="sm" disabled={busy}>
                            {isSet ? 'Salvar' : 'Propor'}
                        </Button>
                        <Button type="button" size="sm" variant="outline" disabled={busy} onClick={() => setMode(null)}>
                            Cancelar
                        </Button>
                    </div>
                </form>
            ) : (
                (abilities.set_hours || (abilities.propose_hours && !hasProposal)) && (
                    <div className="flex flex-wrap gap-2">
                        {abilities.set_hours && (
                            <Button size="sm" variant="outline" disabled={busy} onClick={() => open('set')}>
                                {spinning('set') && <Loader2 className="animate-spin" aria-hidden />}
                                {detail.estimated_hours === null ? 'Definir' : 'Reestimar'}
                            </Button>
                        )}
                        {abilities.propose_hours && !hasProposal && (
                            <Button size="sm" variant="outline" disabled={busy} onClick={() => open('propose')}>
                                {spinning('propose') && <Loader2 className="animate-spin" aria-hidden />}
                                Propor outro valor
                            </Button>
                        )}
                    </div>
                )
            )}

            <WConfirmDialogMolecule
                open={pending !== null}
                onOpenChange={(next) => !next && setPending(null)}
                title={pending?.kind === 'clear' ? `Limpar as horas previstas de ${detail.key}?` : `Reestimar ${detail.key}?`}
                description={pending?.kind === 'reestimate' ? pending.text : undefined}
                confirmLabel={pending?.kind === 'clear' ? 'Limpar' : 'Reestimar'}
                loading={busy}
                onConfirm={confirmPending}
            />
        </div>
    );
}

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-0.5">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="font-mono text-sm tabular-nums">{value}</dd>
        </div>
    );
}
