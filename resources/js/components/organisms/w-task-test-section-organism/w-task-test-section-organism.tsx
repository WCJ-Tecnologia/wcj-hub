import { CheckCircle2, Circle, Loader2, Paperclip, Plus, Trash2, Undo2, UserCheck } from 'lucide-react';
import { type ChangeEvent, useRef, useState } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { Button } from '@/components/shadcn/button';
import {
    CRITERION_RESULT_LABEL,
    CROSS_TEST_PENDING_TEXT,
    crossTestedText,
    evidencesOf,
    markedText,
    NO_CRITERIA_TEXT,
    REMOVE_EVIDENCE_TEXT,
    scriptItems,
    scriptProgressText,
    scriptSummaryText,
    undoCrossTestText,
} from '@/lib/ficha';
import { cn } from '@/lib/utils';
import type { AcceptanceCriterion, CriterionResult, TaskAttachment, TaskDetail } from '@/types/quadro';

/** De onde vem a devolução: o bloco do teste cruzado ou o roteiro (F4 §04). */
export type ReturnOrigin = 'cross_test' | 'qa';

export interface WTaskTestSectionOrganismProps {
    criteria: AcceptanceCriterion[];
    crossTested: TaskDetail['cross_tested'];
    /** Os anexos do card: as evidências são os que têm `criterion_id` (F4 §02). */
    attachments: TaskAttachment[];
    /** `abilities.cross_test`: *Marquei: testado por mim* (não aparece para o dev da tarefa, P34). */
    canCrossTest: boolean;
    /** `abilities.undo_cross_test`: *Desfazer a marca* (quem marcou, gestor, Administrador). */
    canUndoCrossTest: boolean;
    /** `abilities.run_qa`: marcar o resultado de cada item (qualquer alocado). */
    canRunQa: boolean;
    /** `abilities.attach`: anexar e excluir evidência (a regra de anexo do F3). */
    canAttach: boolean;
    /** `abilities.update`: o *Novo critério* do roteiro vazio. */
    canEdit: boolean;
    /** Mostra *Devolver para desenvolvimento*: alocado e a etapa é de onde voltar é retrabalho (P39). */
    canReturn: boolean;
    busy?: boolean;
    /** Erros do servidor: `result`, `criterion_id` e os do envio (`files`, `files.0`…), no item que agiu. */
    errors?: Record<string, string>;
    onMarkCrossTest: () => void;
    onUndoCrossTest: () => void;
    onSetResult: (criterionId: number, result: CriterionResult) => void;
    onUploadEvidence: (criterionId: number, files: File[]) => void;
    onRemoveEvidence: (attachmentId: number) => void;
    /** Abre o diálogo de retrabalho do F3 com destino `To do` e a origem já escolhida (P39). */
    onReturn: (origin: ReturnOrigin) => void;
    /** O roteiro vazio: abre a criação de critério da ficha (F4 §03). */
    onNewCriterion: () => void;
}

/** A linha do cabeçalho recolhido: "Teste cruzado feito · 4 de 7 aprovados · 1 reprovado". */
export function testSummary(crossTested: TaskDetail['cross_tested'], criteria: AcceptanceCriterion[]): string {
    return `${crossTested ? 'Teste cruzado feito' : 'Teste cruzado pendente'} · ${scriptProgressText(criteria)}`;
}

const RESULTS: CriterionResult[] = ['pending', 'approved', 'failed'];

/** A cor do botão escolhido, pelos tokens de situação (o mesmo tom da pílula do critério). */
const RESULT_PRESSED: Record<CriterionResult, string> = {
    pending: 'border-status-pending bg-status-pending-bg text-status-pending hover:bg-status-pending-bg',
    approved: 'border-status-approved bg-status-approved-bg text-status-approved hover:bg-status-approved-bg',
    failed: 'border-status-rejected bg-status-rejected-bg text-status-rejected hover:bg-status-rejected-bg',
};

/** Qual botão disparou a gravação em curso: o spinner e o erro vão nele. */
type Acting = 'mark' | 'undo' | `result-${number}` | `upload-${number}` | `remove-${number}`;

function uploadErrors(errors: Record<string, string> | undefined): string[] {
    if (!errors) return [];
    return Object.entries(errors)
        .filter(([key]) => key === 'criterion_id' || key === 'files' || key.startsWith('files.'))
        .map(([, message]) => message);
}

/**
 * Seção *Teste* da ficha do card (F4 §01 a §04; P34 a P39; B4): o teste cruzado (situação, *Marquei:
 * testado por mim*, *Desfazer a marca*) e o roteiro de QA, um item por critério de aceite na ordem
 * CA_1, CA_2… com os subitens logo abaixo do pai, cada um com o resultado (A testar, Aprovado,
 * Reprovado), as evidências e a última marcação. Os dois blocos têm o atalho *Devolver para
 * desenvolvimento*. Nada grava aqui: devolve a intenção por callback; a recusa é do servidor.
 */
export function WTaskTestSectionOrganism({
    criteria,
    crossTested,
    attachments,
    canCrossTest,
    canUndoCrossTest,
    canRunQa,
    canAttach,
    canEdit,
    canReturn,
    busy = false,
    errors,
    onMarkCrossTest,
    onUndoCrossTest,
    onSetResult,
    onUploadEvidence,
    onRemoveEvidence,
    onReturn,
    onNewCriterion,
}: WTaskTestSectionOrganismProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [acting, setActing] = useState<Acting | null>(null);
    const [uploadFor, setUploadFor] = useState<number | null>(null);
    const [undoing, setUndoing] = useState(false);
    const [removing, setRemoving] = useState<TaskAttachment | null>(null);
    const [openEvidence, setOpenEvidence] = useState<Set<number>>(new Set());

    // Fecha o diálogo de desfazer quando a página troca `crossTested` (ajuste durante a renderização, sem efeito).
    const [seen, setSeen] = useState(crossTested);
    if (seen !== crossTested) {
        setSeen(crossTested);
        if (!crossTested) setUndoing(false);
    }

    const spinning = (key: Acting) => busy && acting === key;
    const act = (key: Acting, run: () => void) => {
        if (busy) return;
        setActing(key);
        run();
    };

    const pickFiles = (event: ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(event.target.files ?? []);
        // Limpa o campo: escolher o mesmo arquivo de novo dispara o change outra vez.
        event.target.value = '';
        if (files.length === 0 || uploadFor === null) return;
        const criterionId = uploadFor;
        setOpenEvidence((current) => new Set(current).add(criterionId));
        act(`upload-${criterionId}`, () => onUploadEvidence(criterionId, files));
    };

    const items = scriptItems(criteria);
    const errorOf = (criterionId: number): string[] => {
        if (acting === `result-${criterionId}` && errors?.result) return [errors.result];
        if (acting === `upload-${criterionId}`) return uploadErrors(errors);
        return [];
    };

    const returnButton = (origin: ReturnOrigin) =>
        canReturn && (
            <Button variant="outline" size="sm" disabled={busy} onClick={() => onReturn(origin)}>
                <Undo2 aria-hidden />
                Devolver para desenvolvimento
            </Button>
        );

    const renderItem = (item: AcceptanceCriterion, isChild: boolean) => {
        const evidences = evidencesOf(attachments, item.id);
        const expanded = openEvidence.has(item.id);
        const itemErrors = errorOf(item.id);
        const failed = item.result === 'failed';
        return (
            <li
                key={item.id}
                data-result={item.result}
                className={cn(
                    'grid grid-cols-[3.5rem_minmax(0,1fr)] gap-x-2 rounded-md border px-3 py-2.5',
                    isChild && 'ml-6',
                    failed ? 'border-status-rejected/40 bg-status-rejected-bg/60' : 'border-border',
                )}
            >
                <span className="pt-0.5 font-mono text-xs font-semibold text-muted-foreground tabular-nums">{item.number}</span>
                <div className="min-w-0 space-y-2">
                    <div className="flex flex-wrap items-start gap-x-3 gap-y-2">
                        <p className="min-w-40 flex-1 text-sm whitespace-pre-wrap">{item.description}</p>
                        {canRunQa ? (
                            <div role="group" aria-label={`Resultado de ${item.number}`} className="flex shrink-0 items-center gap-1">
                                {RESULTS.map((result) => {
                                    const pressed = item.result === result;
                                    return (
                                        <Button
                                            key={result}
                                            variant="outline"
                                            size="sm"
                                            aria-pressed={pressed}
                                            disabled={busy}
                                            className={cn('h-7 px-2 text-xs', pressed && RESULT_PRESSED[result])}
                                            onClick={() => !pressed && act(`result-${item.id}`, () => onSetResult(item.id, result))}
                                        >
                                            {CRITERION_RESULT_LABEL[result]}
                                        </Button>
                                    );
                                })}
                                {spinning(`result-${item.id}`) && <Loader2 className="size-4 animate-spin" aria-label="Salvando" />}
                            </div>
                        ) : (
                            <WStatusPillAtom kind="criterion" status={item.result} size="sm" />
                        )}
                    </div>

                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                        <span>
                            Última marcação: <span className="text-foreground">{markedText(item.marked)}</span>
                        </span>
                        <Button
                            variant="link"
                            size="sm"
                            className="h-auto p-0 text-xs"
                            aria-expanded={expanded}
                            disabled={evidences.length === 0}
                            onClick={() =>
                                setOpenEvidence((current) => {
                                    const next = new Set(current);
                                    if (next.has(item.id)) next.delete(item.id);
                                    else next.add(item.id);
                                    return next;
                                })
                            }
                        >
                            {evidences.length === 0
                                ? 'Sem evidência'
                                : `${evidences.length} ${evidences.length === 1 ? 'evidência' : 'evidências'}`}
                        </Button>
                        {canAttach && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-7 px-2 text-xs"
                                disabled={busy}
                                aria-label={`Anexar evidência em ${item.number}`}
                                onClick={() => {
                                    setUploadFor(item.id);
                                    inputRef.current?.click();
                                }}
                            >
                                {spinning(`upload-${item.id}`) ? (
                                    <Loader2 className="animate-spin" aria-hidden />
                                ) : (
                                    <Paperclip aria-hidden />
                                )}
                                Anexar evidência
                            </Button>
                        )}
                    </div>

                    {expanded && evidences.length > 0 && (
                        <ul aria-label={`Evidências de ${item.number}`} className="space-y-1">
                            {evidences.map((evidence) => (
                                <li key={evidence.id} className="flex items-center gap-2 text-xs">
                                    <a
                                        href={evidence.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="truncate text-primary-text underline-offset-2 hover:underline"
                                    >
                                        {evidence.name}
                                    </a>
                                    {canAttach && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="h-6 px-1.5 text-xs text-destructive hover:text-destructive"
                                            disabled={busy}
                                            aria-label={`Excluir ${evidence.name}`}
                                            onClick={() => setRemoving(evidence)}
                                        >
                                            {spinning(`remove-${evidence.id}`) ? (
                                                <Loader2 className="animate-spin" aria-hidden />
                                            ) : (
                                                <Trash2 aria-hidden />
                                            )}
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}

                    {itemErrors.length > 0 && (
                        <p role="alert" className="text-xs font-medium text-destructive">
                            {itemErrors.join(' ')}
                        </p>
                    )}
                </div>
            </li>
        );
    };

    return (
        <div data-slot="w-task-test-section-organism" aria-busy={busy || undefined} className="space-y-5">
            <section aria-labelledby="teste-cruzado-titulo" className="space-y-2">
                <h3 id="teste-cruzado-titulo" className="font-mono text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Teste cruzado
                </h3>
                <p className="flex items-center gap-2 text-sm">
                    {crossTested ? (
                        <CheckCircle2 className="size-4 text-status-approved" aria-hidden />
                    ) : (
                        <Circle className="size-4 text-muted-foreground" aria-hidden />
                    )}
                    {crossTested ? crossTestedText(crossTested) : CROSS_TEST_PENDING_TEXT}
                </p>
                <div className="flex flex-wrap gap-2">
                    {canCrossTest && !crossTested && (
                        <Button size="sm" disabled={busy} onClick={() => act('mark', onMarkCrossTest)}>
                            {spinning('mark') ? <Loader2 className="animate-spin" aria-hidden /> : <UserCheck aria-hidden />}
                            Marquei: testado por mim
                        </Button>
                    )}
                    {canUndoCrossTest && crossTested && (
                        <Button variant="outline" size="sm" disabled={busy} onClick={() => setUndoing(true)}>
                            Desfazer a marca
                        </Button>
                    )}
                    {returnButton('cross_test')}
                </div>
            </section>

            <section aria-labelledby="roteiro-titulo" className="space-y-2">
                <h3 id="roteiro-titulo" className="font-mono text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Roteiro de QA
                </h3>
                {items.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {NO_CRITERIA_TEXT}{' '}
                        {canEdit && (
                            <Button variant="link" className="h-auto p-0" onClick={onNewCriterion}>
                                <Plus aria-hidden />
                                Novo critério
                            </Button>
                        )}
                    </p>
                ) : (
                    <>
                        <p className="text-sm font-medium">{scriptSummaryText(criteria)}</p>
                        <ul aria-label="Roteiro de QA" className="space-y-2">
                            {criteria.map((criterion) => [
                                renderItem(criterion, false),
                                ...criterion.children.map((child) => renderItem(child, true)),
                            ])}
                        </ul>
                        <div className="flex flex-wrap gap-2">{returnButton('qa')}</div>
                    </>
                )}
            </section>

            <input
                ref={inputRef}
                type="file"
                multiple
                hidden
                tabIndex={-1}
                aria-label="Arquivo da evidência"
                onChange={pickFiles}
                disabled={busy}
            />

            <WConfirmDialogMolecule
                open={undoing && crossTested !== null}
                onOpenChange={(next) => !next && !busy && setUndoing(false)}
                title={crossTested ? undoCrossTestText(crossTested.by.name) : ''}
                description="A marca fica no histórico do card."
                confirmLabel="Desfazer"
                loading={spinning('undo')}
                onConfirm={() => act('undo', onUndoCrossTest)}
            />
            <WConfirmDialogMolecule
                open={removing !== null}
                onOpenChange={(next) => !next && setRemoving(null)}
                title={removing ? REMOVE_EVIDENCE_TEXT(removing.name) : ''}
                confirmLabel="Excluir"
                destructive
                onConfirm={() => {
                    if (!removing) return;
                    const id = removing.id;
                    setRemoving(null);
                    act(`remove-${id}`, () => onRemoveEvidence(id));
                }}
            />
        </div>
    );
}
