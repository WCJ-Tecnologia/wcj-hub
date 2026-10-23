import { ListPlus, Loader2, Pencil, Plus, Trash2 } from 'lucide-react';
import { type FormEvent, type KeyboardEvent, useId, useState } from 'react';

import { WStatusPillAtom } from '@/components/atoms/w-status-pill-atom/w-status-pill-atom';
import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Button } from '@/components/shadcn/button';
import { Textarea } from '@/components/shadcn/textarea';
import { countCriteria, CRITERION_RETEST_TEXT, criterionDeletable, deleteCriterionText } from '@/lib/ficha';
import { cn } from '@/lib/utils';
import type { AcceptanceCriterion, TaskAttachment } from '@/types/quadro';

export interface WTaskCriteriaSectionOrganismProps {
    criteria: AcceptanceCriterion[];
    /** `abilities.update`: adicionar, editar e excluir (qualquer alocado). */
    canEdit: boolean;
    /** Os anexos do card: critério com evidência do QA conta como testado e não se exclui (T1; F4 §10.5, B4). */
    attachments?: Pick<TaskAttachment, 'criterion_id'>[];
    /**
     * Pedido de fora para abrir o campo do critério novo (o *Novo critério* da seção Teste, F4 §03):
     * cada valor novo, maior que zero, abre o campo.
     */
    addRequest?: number;
    busy?: boolean;
    /** Erros do servidor: `description` (do critério em edição ou novo). */
    errors?: Record<string, string>;
    /** Novo CA_n no fim (parent_id null) ou CA_n.m no fim do pai (P17, T9). */
    onAdd: (payload: { description: string; parent_id: number | null }) => void;
    onUpdate: (id: number, description: string) => void;
    onDelete: (id: number) => void;
}

const plural = (count: number, one: string, many: string) => `${count} ${count === 1 ? one : many}`;

/**
 * Linha de resumo da seção recolhida, contando critérios e subitens: "1 aprovado · 1 reprovado · 2 a testar".
 * Contagem zero sai da frase; sem critério, "Nenhum critério".
 */
export function criteriaSummary(criteria: AcceptanceCriterion[]): string {
    const all = criteria.flatMap((criterion) => [criterion, ...criterion.children]);
    if (all.length === 0) return 'Nenhum critério';
    const approved = all.filter((item) => item.result === 'approved').length;
    const failed = all.filter((item) => item.result === 'failed').length;
    const pending = all.filter((item) => item.result === 'pending').length;
    return [
        approved > 0 && plural(approved, 'aprovado', 'aprovados'),
        failed > 0 && plural(failed, 'reprovado', 'reprovados'),
        pending > 0 && `${pending} a testar`,
    ]
        .filter(Boolean)
        .join(' · ');
}

function findCriterion(criteria: AcceptanceCriterion[], id: number): AcceptanceCriterion | null {
    for (const criterion of criteria) {
        if (criterion.id === id) return criterion;
        const child = criterion.children.find((item) => item.id === id);
        if (child) return child;
    }
    return null;
}

/** O editor aberto: um só por vez, para o `errors.description` do servidor ter dono. */
type Editor = { mode: 'edit'; id: number } | { mode: 'add'; parentId: number | null };

/** O que foi mandado e ainda espera a página trocar `criteria`. */
type Pending = { kind: 'edit'; id: number; text: string } | { kind: 'add'; count: number };

const DESCRIPTION_REQUIRED = 'Informe a descrição do critério.';

/**
 * Seção *Critérios de aceite* da ficha (F3 §02 "Critério de aceite", §03, §04; P17, P36, P37, T1, T9):
 * `CA_1, CA_2…` com os subitens `CA_n.m` logo abaixo do pai, o resultado do QA em pílula e, para quem
 * edita, *Editar*, *Subitem* e *Excluir* em linha. Nada grava aqui: devolve a intenção por callback.
 */
export function WTaskCriteriaSectionOrganism({
    criteria,
    canEdit,
    attachments = [],
    addRequest = 0,
    busy = false,
    errors,
    onAdd,
    onUpdate,
    onDelete,
}: WTaskCriteriaSectionOrganismProps) {
    const baseId = useId();
    const [editor, setEditor] = useState<Editor | null>(null);
    const [draft, setDraft] = useState('');
    const [tried, setTried] = useState(false);
    const [pending, setPending] = useState<Pending | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<AcceptanceCriterion | null>(null);
    const [lastAction, setLastAction] = useState<string | null>(null);

    // Fecha o editor e o diálogo quando a página troca `criteria` com o resultado da gravação
    // (ajuste de estado durante a renderização, sem efeito: https://react.dev/learn/you-might-not-need-an-effect).
    const [seen, setSeen] = useState(criteria);
    if (seen !== criteria) {
        setSeen(criteria);
        if (pending) {
            const saved =
                pending.kind === 'add'
                    ? countCriteria(criteria) > pending.count
                    : (findCriterion(criteria, pending.id)?.description ?? pending.text) === pending.text;
            if (saved) {
                setEditor(null);
                setDraft('');
                setTried(false);
                setPending(null);
            }
        }
        if (deleteTarget && !findCriterion(criteria, deleteTarget.id)) setDeleteTarget(null);
    }

    // O *Novo critério* da seção Teste: abre o campo do critério novo (o mesmo ajuste durante a renderização).
    const [seenRequest, setSeenRequest] = useState(addRequest);
    if (seenRequest !== addRequest) {
        setSeenRequest(addRequest);
        if (addRequest > 0 && canEdit) {
            setEditor({ mode: 'add', parentId: null });
            setDraft('');
            setTried(false);
            setPending(null);
        }
    }

    const open = (next: Editor, text = '') => {
        setEditor(next);
        setDraft(text);
        setTried(false);
        setPending(null);
    };
    const cancel = () => {
        setEditor(null);
        setDraft('');
        setTried(false);
        setPending(null);
    };

    const actionKey = (target: Editor) => (target.mode === 'edit' ? `edit-${target.id}` : `add-${target.parentId ?? 'root'}`);
    const spinning = (key: string) => busy && lastAction === key;

    const submit = () => {
        if (!editor) return;
        setTried(true);
        const text = draft.trim();
        if (text === '' || busy) return;
        setLastAction(actionKey(editor));
        if (editor.mode === 'edit') {
            setPending({ kind: 'edit', id: editor.id, text });
            onUpdate(editor.id, text);
        } else {
            setPending({ kind: 'add', count: countCriteria(criteria) });
            onAdd({ description: text, parent_id: editor.parentId });
        }
    };

    const error = errors?.description ?? (tried && draft.trim() === '' ? DESCRIPTION_REQUIRED : undefined);
    const isEditing = (id: number) => editor?.mode === 'edit' && editor.id === id;
    const isAdding = (parentId: number | null) => editor?.mode === 'add' && editor.parentId === parentId;

    const renderEditor = (target: Editor, label: string, criterion?: AcceptanceCriterion) => {
        const fieldId = `${baseId}-${actionKey(target)}`;
        const retest = criterion && criterion.result !== 'pending';
        const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                submit();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                cancel();
            }
        };
        return (
            <form
                className="space-y-2"
                noValidate
                onSubmit={(event: FormEvent) => {
                    event.preventDefault();
                    submit();
                }}
            >
                <WFormFieldMolecule
                    label={label}
                    htmlFor={fieldId}
                    required
                    error={error}
                    hint="Enter salva; Shift+Enter quebra a linha; Esc cancela."
                >
                    <Textarea
                        value={draft}
                        rows={2}
                        autoFocus
                        className="min-h-16"
                        onChange={(event) => setDraft(event.target.value)}
                        onKeyDown={onKeyDown}
                    />
                </WFormFieldMolecule>
                {retest && (
                    <p role="status" className="rounded-md bg-status-pending-bg px-3 py-2 text-xs font-medium text-status-pending">
                        {CRITERION_RETEST_TEXT}
                    </p>
                )}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" size="sm" onClick={cancel}>
                        Cancelar
                    </Button>
                    <Button type="submit" size="sm" disabled={busy}>
                        {spinning(actionKey(target)) && <Loader2 className="size-4 animate-spin" aria-hidden />}
                        {target.mode === 'edit' ? 'Salvar' : 'Adicionar'}
                    </Button>
                </div>
            </form>
        );
    };

    const renderItem = (criterion: AcceptanceCriterion, parent: AcceptanceCriterion | null) => (
        <li key={criterion.id} className={cn(parent ? 'pt-2' : 'py-3')}>
            <div className="flex gap-3">
                <span className="shrink-0 pt-0.5 font-mono text-xs font-semibold text-muted-foreground tabular-nums">
                    {criterion.number}
                </span>
                <div className="min-w-0 flex-1 space-y-2">
                    {isEditing(criterion.id) ? (
                        renderEditor({ mode: 'edit', id: criterion.id }, `Descrição de ${criterion.number}`, criterion)
                    ) : (
                        <p className="text-sm whitespace-pre-wrap">{criterion.description}</p>
                    )}
                    <div className="flex flex-wrap items-center gap-1">
                        <WStatusPillAtom kind="criterion" status={criterion.result} size="sm" />
                        {canEdit && !isEditing(criterion.id) && (
                            <div className="ml-auto flex flex-wrap items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    aria-label={`Editar ${criterion.number}`}
                                    onClick={() => open({ mode: 'edit', id: criterion.id }, criterion.description)}
                                >
                                    <Pencil aria-hidden />
                                    Editar
                                </Button>
                                {!parent && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        aria-label={`Subitem em ${criterion.number}`}
                                        onClick={() => open({ mode: 'add', parentId: criterion.id })}
                                    >
                                        <ListPlus aria-hidden />
                                        Subitem
                                    </Button>
                                )}
                                {criterionDeletable(criterion, attachments) && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        aria-label={`Excluir ${criterion.number}`}
                                        className="text-destructive hover:text-destructive"
                                        disabled={busy}
                                        onClick={() => setDeleteTarget(criterion)}
                                    >
                                        <Trash2 aria-hidden />
                                        Excluir
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
            {!parent && (criterion.children.length > 0 || isAdding(criterion.id)) && (
                <div className="mt-1 ml-3 border-l border-border pl-4">
                    {criterion.children.length > 0 && (
                        <ul aria-label={`Subitens de ${criterion.number}`}>
                            {criterion.children.map((child) => renderItem(child, criterion))}
                        </ul>
                    )}
                    {isAdding(criterion.id) && (
                        <div className="pt-2">
                            {renderEditor({ mode: 'add', parentId: criterion.id }, `Novo subitem de ${criterion.number}`)}
                        </div>
                    )}
                </div>
            )}
        </li>
    );

    return (
        <div data-slot="w-task-criteria-section-organism" aria-busy={busy || undefined} className="space-y-3">
            {criteria.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Nenhum critério.{' '}
                    {canEdit && !isAdding(null) && (
                        <Button variant="link" className="h-auto p-0" onClick={() => open({ mode: 'add', parentId: null })}>
                            Adicionar critério
                        </Button>
                    )}
                </p>
            ) : (
                <ul aria-label="Critérios de aceite" className="divide-y divide-border">
                    {criteria.map((criterion) => renderItem(criterion, null))}
                </ul>
            )}

            {canEdit && isAdding(null) && renderEditor({ mode: 'add', parentId: null }, 'Novo critério')}

            {canEdit && criteria.length > 0 && !isAdding(null) && (
                <Button variant="outline" size="sm" onClick={() => open({ mode: 'add', parentId: null })}>
                    <Plus aria-hidden />
                    Adicionar critério
                </Button>
            )}

            <WConfirmDialogMolecule
                open={deleteTarget !== null}
                onOpenChange={(next) => !next && setDeleteTarget(null)}
                title={deleteTarget ? deleteCriterionText(deleteTarget.number) : ''}
                description={deleteTarget && deleteTarget.children.length > 0 ? 'Os subitens saem junto.' : undefined}
                confirmLabel="Excluir"
                destructive
                loading={spinning(`delete-${deleteTarget?.id}`)}
                onConfirm={() => {
                    if (!deleteTarget || busy) return;
                    setLastAction(`delete-${deleteTarget.id}`);
                    onDelete(deleteTarget.id);
                }}
            />
        </div>
    );
}
