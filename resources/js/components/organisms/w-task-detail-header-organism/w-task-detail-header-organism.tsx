import { Link } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    ArrowRightLeft,
    CornerDownRight,
    History,
    Link2,
    Loader2,
    MoreHorizontal,
    Pencil,
    Trash2,
} from 'lucide-react';
import { type FormEvent, type MouseEvent, useId, useState } from 'react';
import { toast } from 'sonner';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WTaskMarksMolecule } from '@/components/molecules/w-task-marks-molecule/w-task-marks-molecule';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/shadcn/dropdown-menu';
import { Input } from '@/components/shadcn/input';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/shadcn/tooltip';
import { coverOf, crossTestedText, detailToBoardCard, HISTORY_SOON_TEXT, situationText } from '@/lib/ficha';
import { cn, dateBr } from '@/lib/utils';
import type { BoardStage, TaskDetail } from '@/types/quadro';

/** Limite do título (F3 §02, P17), o mesmo do criar rápido. */
export const TITLE_MAX = 200;

export interface WTaskDetailHeaderOrganismProps {
    detail: TaskDetail;
    /** Etapas ativas do projeto, na ordem: o *Mover para…* (P13). */
    stages: BoardStage[];
    /** Uma gravação está rodando: Salvar com spinner, menu travado. */
    busy?: boolean;
    /** Erros do servidor: `title`. */
    errors?: Record<string, string>;
    onRenameTitle: (title: string) => void;
    /** *Mover para…*: a página passa pelo `useBoardMove` (diálogos e recusas), como no quadro. */
    onMove: (to: BoardStage) => void;
    onArchive: () => void;
    onUnarchive: () => void;
    onDelete: () => void;
    className?: string;
}

/**
 * O topo da ficha (F3 §02): chave, título (o único campo que o criar rápido preencheu, editável
 * aqui), card pai, marcas, capa, e os fatos só leitura (etapa, sprint, pacote, data prevista, teste
 * cruzado, situação). À direita, *Histórico* (F7, B7) e o menu ⋯ com *Mover para…*, *Copiar link*,
 * *Arquivar* / *Desarquivar* e *Excluir* (P26). Cada ação só aparece com a ability dela.
 */
export function WTaskDetailHeaderOrganism({
    detail,
    stages,
    busy = false,
    errors,
    onRenameTitle,
    onMove,
    onArchive,
    onUnarchive,
    onDelete,
    className,
}: WTaskDetailHeaderOrganismProps) {
    const { abilities } = detail;
    const card = detailToBoardCard(detail);
    const cover = coverOf(detail.attachments, detail.cover_attachment_id);
    const targets = stages.filter((stage) => stage.id !== detail.stage.id);
    const hasMenu = abilities.move || abilities.archive || abilities.delete;

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(new URL(detail.url, window.location.origin).href);
            toast.success('Link copiado');
        } catch {
            toast.error('Não foi possível copiar o link');
        }
    };

    return (
        <div data-slot="w-task-detail-header-organism" className={cn('grid gap-4', className)}>
            <div className="flex items-start gap-3">
                <div className="grid min-w-0 flex-1 gap-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <WTaskKeyAtom taskKey={detail.key} />
                        <span className="font-mono text-xs tracking-wide text-muted-foreground uppercase">{detail.type.label}</span>
                        {detail.area && (
                            <Badge variant="info">
                                <span className="sr-only">Área: </span>
                                {detail.area.name}
                            </Badge>
                        )}
                        {detail.parent && (
                            <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                <CornerDownRight className="size-3.5" aria-hidden />
                                <span className="sr-only">Card pai:</span>
                                <WTaskKeyAtom taskKey={detail.parent.key} href={detail.parent.url} title={detail.parent.title} size="sm" />
                            </span>
                        )}
                    </div>

                    <TitleEditor title={detail.title} canEdit={abilities.update} busy={busy} error={errors?.title} onSave={onRenameTitle} />

                    <WTaskMarksMolecule card={card} inPausedStage={detail.stage.role === 'paused'} />
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    {detail.history_url ? (
                        <Button variant="outline" asChild>
                            <Link href={detail.history_url}>
                                <History aria-hidden /> Histórico
                            </Link>
                        </Button>
                    ) : (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    aria-disabled="true"
                                    className="cursor-not-allowed opacity-60"
                                    onClick={(event: MouseEvent) => event.preventDefault()}
                                >
                                    <History aria-hidden /> Histórico
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>{HISTORY_SOON_TEXT}</TooltipContent>
                        </Tooltip>
                    )}

                    {/* modal={false}: o menu não esconde a página do leitor de tela (regra aria-hidden-focus do axe). */}
                    <DropdownMenu modal={false}>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="icon" aria-label={`Ações de ${detail.key}`} disabled={busy}>
                                <MoreHorizontal aria-hidden />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-52">
                            <DropdownMenuItem onSelect={copyLink}>
                                <Link2 aria-hidden /> Copiar link
                            </DropdownMenuItem>
                            {hasMenu && <DropdownMenuSeparator />}
                            {abilities.move && (
                                <DropdownMenuSub>
                                    <DropdownMenuSubTrigger disabled={targets.length === 0}>
                                        <ArrowRightLeft aria-hidden /> Mover para…
                                    </DropdownMenuSubTrigger>
                                    <DropdownMenuSubContent className="w-44">
                                        {targets.map((target) => (
                                            <DropdownMenuItem key={target.id} onSelect={() => onMove(target)}>
                                                {target.name}
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuSubContent>
                                </DropdownMenuSub>
                            )}
                            {abilities.archive && !detail.archived && (
                                <DropdownMenuItem onSelect={onArchive}>
                                    <Archive aria-hidden /> Arquivar
                                </DropdownMenuItem>
                            )}
                            {abilities.archive && detail.archived && (
                                <DropdownMenuItem onSelect={onUnarchive}>
                                    <ArchiveRestore aria-hidden /> Desarquivar
                                </DropdownMenuItem>
                            )}
                            {abilities.delete && (
                                <DropdownMenuItem variant="destructive" onSelect={onDelete}>
                                    <Trash2 aria-hidden /> Excluir
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            {cover && (
                <img src={cover.url} alt={`Capa: ${cover.name}`} className="max-h-56 w-full rounded-md border border-border object-cover" />
            )}

            <dl className="grid grid-cols-2 gap-x-6 gap-y-2 rounded-md border border-border bg-card px-4 py-3 text-sm sm:grid-cols-3 lg:grid-cols-6">
                <Fact label="Etapa">
                    <Badge variant="outline">{detail.stage.name}</Badge>
                </Fact>
                <Fact label="Sprint">{detail.sprint?.name ?? 'Fora de sprint'}</Fact>
                <Fact label="Pacote">{detail.package?.name ?? '—'}</Fact>
                <Fact label="Data prevista">
                    <span className={cn('font-mono tabular-nums', detail.late && 'font-medium text-status-rejected')}>
                        {dateBr(detail.due_on)}
                    </span>
                </Fact>
                <Fact label="Teste cruzado">{crossTestedText(detail.cross_tested)}</Fact>
                <Fact label="Situação">
                    <span className={cn(detail.archived && 'text-status-pending')}>{situationText(detail.archived)}</span>
                </Fact>
            </dl>
        </div>
    );
}

function Fact({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="min-w-0">
            <dt className="font-mono text-2xs tracking-wider text-muted-foreground uppercase">{label}</dt>
            <dd className="truncate">{children}</dd>
        </div>
    );
}

interface TitleEditorProps {
    title: string;
    canEdit: boolean;
    busy: boolean;
    error?: string;
    onSave: (title: string) => void;
}

/** O título como h2 com o lápis; editando, vira um campo com Salvar e Cancelar (Enter salva, Esc cancela). */
function TitleEditor({ title, canEdit, busy, error, onSave }: TitleEditorProps) {
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(title);
    const [tried, setTried] = useState(false);
    const id = useId();

    const start = () => {
        setDraft(title);
        setTried(false);
        setEditing(true);
    };
    const cancel = () => setEditing(false);
    const localError = tried && draft.trim() === '' ? 'Informe o título.' : undefined;
    const message = error ?? localError;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setTried(true);
        const clean = draft.trim();
        if (clean === '' || busy) return;
        if (clean === title) {
            setEditing(false);
            return;
        }
        onSave(clean);
    };

    if (!editing) {
        return (
            <div className="flex items-start gap-2">
                <h2 className="min-w-0 font-heading text-xl font-bold tracking-tight break-words sm:text-2xl">{title}</h2>
                {canEdit && (
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8 shrink-0 text-muted-foreground"
                        aria-label="Editar título"
                        onClick={start}
                    >
                        <Pencil aria-hidden />
                    </Button>
                )}
            </div>
        );
    }

    return (
        <form onSubmit={submit} noValidate className="grid gap-1.5">
            <label htmlFor={id} className="sr-only">
                Título
            </label>
            <Input
                id={id}
                value={draft}
                maxLength={TITLE_MAX}
                autoFocus
                disabled={busy}
                aria-invalid={message ? true : undefined}
                aria-describedby={message ? `${id}-error` : undefined}
                className="h-11 font-heading text-lg font-bold"
                onChange={(event) => setDraft(event.target.value)}
                onKeyDown={(event) => event.key === 'Escape' && cancel()}
            />
            {message && (
                <p id={`${id}-error`} role="alert" className="text-xs font-medium text-destructive">
                    {message}
                </p>
            )}
            <div className="flex gap-2">
                <Button type="submit" size="sm" disabled={busy}>
                    {busy && <Loader2 className="size-4 animate-spin" aria-hidden />}
                    Salvar
                </Button>
                <Button type="button" variant="outline" size="sm" onClick={cancel} disabled={busy}>
                    Cancelar
                </Button>
            </div>
        </form>
    );
}
