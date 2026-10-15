import { Loader2, Pencil, Trash2 } from 'lucide-react';
import { type FormEvent, type KeyboardEvent, useId, useRef, useState } from 'react';

import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Avatar, AvatarFallback } from '@/components/shadcn/avatar';
import { Button } from '@/components/shadcn/button';
import { Textarea } from '@/components/shadcn/textarea';
import { DELETE_COMMENT_TEXT, dateTimeBr } from '@/lib/ficha';
import { initials } from '@/lib/utils';
import type { TaskComment } from '@/types/quadro';

export interface WTaskCommentsSectionOrganismProps {
    comments: TaskComment[];
    /** `abilities.comment`: mostra o campo de novo comentário. Editar/excluir vem de `comment.can` (só o autor). */
    canComment: boolean;
    busy?: boolean;
    /** Erros do servidor: `body`. */
    errors?: Record<string, string>;
    onAdd: (body: string) => void;
    onUpdate: (id: number, body: string) => void;
    onDelete: (id: number) => void;
}

/** Linha de resumo da seção recolhida: "3 comentários · último em 13/10/2026 15:02" ou "Nenhum comentário". */
export function commentsSummary(comments: TaskComment[]): string {
    if (comments.length === 0) return 'Nenhum comentário';
    const last = comments[comments.length - 1];
    const count = comments.length === 1 ? '1 comentário' : `${comments.length} comentários`;
    return `${count} · último em ${dateTimeBr(last.created_at)}`;
}

/** As primeiras ~80 letras do comentário, numa linha, para a descrição da confirmação. */
export function commentExcerpt(body: string, max = 80): string {
    const line = body.replace(/\s+/g, ' ').trim();
    return line.length > max ? `${line.slice(0, max).trimEnd()}…` : line;
}

const BODY_REQUIRED = 'Escreva o comentário.';

/** Ctrl+Enter ou Cmd+Enter envia; Esc cancela (quando há o que cancelar). */
const keyHandler = (submit: () => void, cancel?: () => void) => (event: KeyboardEvent<HTMLTextAreaElement>) => {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        submit();
    } else if (event.key === 'Escape' && cancel) {
        event.preventDefault();
        cancel();
    }
};

/**
 * Seção *Comentários* da ficha (F3 §02, §03, §04; P24, decisão 10.10): do mais antigo para o mais novo,
 * com autor e data; o autor edita e exclui o próprio (o servidor decide pelo `comment.can`); o campo
 * *Novo comentário* no fim para quem pode comentar. Nada grava aqui: devolve a intenção por callback.
 */
export function WTaskCommentsSectionOrganism({
    comments,
    canComment,
    busy = false,
    errors,
    onAdd,
    onUpdate,
    onDelete,
}: WTaskCommentsSectionOrganismProps) {
    const baseId = useId();
    const composerRef = useRef<HTMLTextAreaElement>(null);
    const [body, setBody] = useState('');
    const [bodyTried, setBodyTried] = useState(false);
    const [addPending, setAddPending] = useState<number | null>(null);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [draft, setDraft] = useState('');
    const [draftTried, setDraftTried] = useState(false);
    const [editPending, setEditPending] = useState<{ id: number; text: string } | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<TaskComment | null>(null);
    const [lastAction, setLastAction] = useState<string | null>(null);

    // Limpa o campo e fecha o editor só quando a página troca `comments` com o resultado da gravação
    // (ajuste de estado na renderização, sem `useEffect`): com erro do servidor, o texto fica.
    const [seen, setSeen] = useState(comments);
    if (seen !== comments) {
        setSeen(comments);
        if (addPending !== null && comments.length > addPending) {
            setBody('');
            setBodyTried(false);
            setAddPending(null);
        }
        if (editPending) {
            const found = comments.find((comment) => comment.id === editPending.id);
            if (!found || found.body === editPending.text) {
                setEditingId(null);
                setDraft('');
                setDraftTried(false);
                setEditPending(null);
            }
        }
        if (deleteTarget && !comments.some((comment) => comment.id === deleteTarget.id)) setDeleteTarget(null);
    }

    const spinning = (key: string) => busy && lastAction === key;

    const submitNew = () => {
        setBodyTried(true);
        const text = body.trim();
        if (text === '' || busy) return;
        setLastAction('add');
        setAddPending(comments.length);
        onAdd(text);
    };

    const startEdit = (comment: TaskComment) => {
        setEditingId(comment.id);
        setDraft(comment.body);
        setDraftTried(false);
        setEditPending(null);
    };
    const cancelEdit = () => {
        setEditingId(null);
        setDraft('');
        setDraftTried(false);
        setEditPending(null);
    };
    const submitEdit = () => {
        if (editingId === null) return;
        setDraftTried(true);
        const text = draft.trim();
        if (text === '' || busy) return;
        setLastAction(`edit-${editingId}`);
        setEditPending({ id: editingId, text });
        onUpdate(editingId, text);
    };

    // O `errors.body` do servidor é de quem gravou por último: o editor, se é ele que está aberto e mandou; senão, o campo novo.
    const serverErrorOnEdit = editingId !== null && lastAction === `edit-${editingId}`;
    const editError = (serverErrorOnEdit ? errors?.body : undefined) ?? (draftTried && draft.trim() === '' ? BODY_REQUIRED : undefined);
    const newError = (!serverErrorOnEdit ? errors?.body : undefined) ?? (bodyTried && body.trim() === '' ? BODY_REQUIRED : undefined);

    return (
        <div data-slot="w-task-comments-section-organism" aria-busy={busy || undefined} className="space-y-4">
            {comments.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Nenhum comentário.{' '}
                    {canComment && (
                        <Button variant="link" className="h-auto p-0" onClick={() => composerRef.current?.focus()}>
                            Comentar
                        </Button>
                    )}
                </p>
            ) : (
                <ul aria-label="Comentários" className="space-y-4">
                    {comments.map((comment) => {
                        const editing = editingId === comment.id;
                        const fieldId = `${baseId}-edit-${comment.id}`;
                        return (
                            <li key={comment.id} className="flex gap-3">
                                <Avatar aria-hidden className="size-8">
                                    <AvatarFallback>{initials(comment.author.name)}</AvatarFallback>
                                </Avatar>
                                <div className="min-w-0 flex-1 space-y-1">
                                    <p className="flex flex-wrap items-baseline gap-x-2 text-sm">
                                        <span className="font-medium">{comment.author.name}</span>
                                        <span className="text-xs text-muted-foreground">{dateTimeBr(comment.created_at)}</span>
                                        {comment.edited_at && (
                                            <span
                                                className="text-xs text-muted-foreground"
                                                title={`Editado em ${dateTimeBr(comment.edited_at)}`}
                                            >
                                                (editado)
                                            </span>
                                        )}
                                    </p>
                                    {editing ? (
                                        <form
                                            noValidate
                                            className="space-y-2"
                                            onSubmit={(event: FormEvent) => {
                                                event.preventDefault();
                                                submitEdit();
                                            }}
                                        >
                                            <WFormFieldMolecule
                                                label="Editar comentário"
                                                htmlFor={fieldId}
                                                required
                                                error={editError}
                                                hint="Ctrl+Enter salva; Esc cancela."
                                            >
                                                <Textarea
                                                    value={draft}
                                                    autoFocus
                                                    onChange={(event) => setDraft(event.target.value)}
                                                    onKeyDown={keyHandler(submitEdit, cancelEdit)}
                                                />
                                            </WFormFieldMolecule>
                                            <div className="flex justify-end gap-2">
                                                <Button type="button" variant="outline" size="sm" onClick={cancelEdit}>
                                                    Cancelar
                                                </Button>
                                                <Button type="submit" size="sm" disabled={busy}>
                                                    {spinning(`edit-${comment.id}`) && (
                                                        <Loader2 className="size-4 animate-spin" aria-hidden />
                                                    )}
                                                    Salvar
                                                </Button>
                                            </div>
                                        </form>
                                    ) : (
                                        <p className="text-sm break-words whitespace-pre-wrap">{comment.body}</p>
                                    )}
                                    {!editing && (comment.can.update || comment.can.delete) && (
                                        <div className="flex gap-1">
                                            {comment.can.update && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    aria-label={`Editar o comentário de ${comment.author.name} de ${dateTimeBr(comment.created_at)}`}
                                                    onClick={() => startEdit(comment)}
                                                >
                                                    <Pencil aria-hidden />
                                                    Editar
                                                </Button>
                                            )}
                                            {comment.can.delete && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    aria-label={`Excluir o comentário de ${comment.author.name} de ${dateTimeBr(comment.created_at)}`}
                                                    disabled={busy}
                                                    onClick={() => setDeleteTarget(comment)}
                                                >
                                                    <Trash2 aria-hidden />
                                                    Excluir
                                                </Button>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            {canComment && (
                <form
                    noValidate
                    className="space-y-2"
                    onSubmit={(event: FormEvent) => {
                        event.preventDefault();
                        submitNew();
                    }}
                >
                    <WFormFieldMolecule label="Novo comentário" htmlFor={`${baseId}-new`} error={newError} hint="Ctrl+Enter envia.">
                        <Textarea
                            ref={composerRef}
                            value={body}
                            onChange={(event) => setBody(event.target.value)}
                            onKeyDown={keyHandler(submitNew)}
                        />
                    </WFormFieldMolecule>
                    <div className="flex justify-end">
                        <Button type="submit" size="sm" disabled={busy}>
                            {spinning('add') && <Loader2 className="size-4 animate-spin" aria-hidden />}
                            Comentar
                        </Button>
                    </div>
                </form>
            )}

            <WConfirmDialogMolecule
                open={deleteTarget !== null}
                onOpenChange={(next) => !next && setDeleteTarget(null)}
                title={DELETE_COMMENT_TEXT}
                description={deleteTarget ? `“${commentExcerpt(deleteTarget.body)}”` : undefined}
                confirmLabel="Excluir"
                destructive
                loading={busy && deleteTarget !== null && lastAction === `delete-${deleteTarget.id}`}
                onConfirm={() => {
                    if (!deleteTarget || busy) return;
                    setLastAction(`delete-${deleteTarget.id}`);
                    onDelete(deleteTarget.id);
                }}
            />
        </div>
    );
}
