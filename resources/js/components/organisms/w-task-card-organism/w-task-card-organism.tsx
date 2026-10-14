import { Link } from '@inertiajs/react';
import { ArrowRightLeft, Clock, CornerDownRight, ExternalLink, Flag, Link2, MoreHorizontal, Tag } from 'lucide-react';
import type { HTMLAttributes, MouseEvent, Ref } from 'react';
import { toast } from 'sonner';

import { WPriorityMarkAtom } from '@/components/atoms/w-priority-mark-atom/w-priority-mark-atom';
import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WCardCountersMolecule } from '@/components/molecules/w-card-counters-molecule/w-card-counters-molecule';
import { WChildProgressMolecule } from '@/components/molecules/w-child-progress-molecule/w-child-progress-molecule';
import { WPeopleAvatarsMolecule } from '@/components/molecules/w-people-avatars-molecule/w-people-avatars-molecule';
import { WTaskMarksMolecule } from '@/components/molecules/w-task-marks-molecule/w-task-marks-molecule';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { Card } from '@/components/shadcn/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/shadcn/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/shadcn/tooltip';
import { PRIORITIES, hours } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { AreaRef, BoardCard, BoardStage, PersonRef, PrioritySlug } from '@/types/quadro';

/** Urgente no topo do submenu, como na ordem do quadro (F3 §03). */
const PRIORITY_MENU: PrioritySlug[] = [...PRIORITIES].reverse();
const NO_AREA = 'none';

export interface WTaskCardOrganismProps extends Omit<HTMLAttributes<HTMLDivElement>, 'children'> {
    card: BoardCard;
    /** A etapa em que o card está: decide a marca de pausa e sai do *Mover para…*. */
    stage: BoardStage;
    /** As etapas ativas do projeto, na ordem do quadro (opções do *Mover para…*). */
    stages?: BoardStage[];
    /** Áreas do projeto (P18). */
    areas?: AreaRef[];
    /** Pessoas alocadas e ativas (P58): opções do *+* de participante. */
    members?: PersonRef[];
    /** *Mover para…*: a página passa pelo `classifyMove` (diálogos e recusas) como no arrastar. */
    onMove?: (card: BoardCard, to: BoardStage) => void;
    onChangePriority?: (card: BoardCard, priority: PrioritySlug) => void;
    onChangeArea?: (card: BoardCard, area: AreaRef | null) => void;
    onToggleParticipant?: (card: BoardCard, person: PersonRef, checked: boolean) => void;
    /** Abre a ficha sem navegar (ex.: num Sheet); sem ele, o título e *Abrir ficha* vão para `card.url`. */
    onOpen?: (card: BoardCard) => void;
    /** Visual de "sendo arrastado" (sombra maior, leve rotação). O arrastar é do quadro. */
    dragging?: boolean;
    /** Uma linha: chave, título e menu, sem capa, marcas nem rodapé. */
    compact?: boolean;
    /** React 19: `ref` é prop. O quadro liga o `setNodeRef` do dnd-kit aqui. */
    ref?: Ref<HTMLDivElement>;
}

/**
 * O card do quadro (F3 §03): faixa de prioridade na borda de cima; cabeçalho com área, tipo e menu ⋯;
 * chave, título e card pai; marcas; capa e horas; rodapé com pessoas, contadores e progresso dos
 * filhos. Recebe o card pronto e devolve intenção por callback: quem grava é a página.
 */
export function WTaskCardOrganism({
    card,
    stage,
    stages = [],
    areas = [],
    members = [],
    onMove,
    onChangePriority,
    onChangeArea,
    onToggleParticipant,
    onOpen,
    dragging = false,
    compact = false,
    className,
    ref,
    ...rest
}: WTaskCardOrganismProps) {
    const open = (event: MouseEvent) => {
        if (!onOpen) return;
        event.preventDefault();
        onOpen(card);
    };

    const title = (
        <Link
            href={card.url}
            onClick={open}
            draggable={false}
            className="rounded-sm outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50"
        >
            {card.title}
        </Link>
    );

    const menu = (
        <TaskCardMenu
            card={card}
            stage={stage}
            stages={stages}
            areas={areas}
            onMove={onMove}
            onChangePriority={onChangePriority}
            onChangeArea={onChangeArea}
            onOpen={onOpen}
        />
    );

    // Sem ninguém no card, o rodapé não nasce só para o *+*: o card do criar rápido fica baixo, e o
    // primeiro participante entra pela ficha (ou aqui, assim que o card tiver dev, QA ou contador).
    const hasPeople = Boolean(card.developer || card.qa || card.participants.length > 0);
    const hasCounters = Boolean(card.due_on || card.attachments_count > 0 || card.comments_count > 0);

    return (
        <Card
            ref={ref}
            data-slot="w-task-card-organism"
            data-key={card.key}
            data-dragging={dragging || undefined}
            className={cn(
                // Profundidade: sombra de verdade (a coluna é o "poço", bg-muted) e uma subida de 1 px no hover; no escuro a sombra some, então o card é mais claro que a coluna.
                'relative w-full gap-2 overflow-hidden rounded-md border-border/80 px-3 pt-3 pb-2.5 text-sm shadow-md transition-[box-shadow,transform] hover:-translate-y-px hover:shadow-lg dark:border-border',
                compact && 'pt-2.5 pb-1.5',
                // A barra dos filhos mora na borda de baixo: um respiro a mais para a contagem não encostar nela.
                card.children && !compact && 'pb-4',
                dragging && 'rotate-2 cursor-grabbing shadow-xl ring-1 ring-primary/40',
                className,
            )}
            {...rest}
        >
            <WPriorityMarkAtom priority={card.priority} />

            {compact ? (
                <div className="flex items-center gap-2">
                    <WTaskKeyAtom taskKey={card.key} size="sm" />
                    <h3 className="min-w-0 flex-1 truncate font-medium">{title}</h3>
                    {menu}
                </div>
            ) : (
                <>
                    {/* Cabeçalho: área (a "etiqueta"), tipo e o menu ⋯. */}
                    <div className="flex items-start gap-2">
                        <div className="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-1 pt-1">
                            {card.area && (
                                <Badge variant="info" className="max-w-full">
                                    <span className="sr-only">Área: </span>
                                    <span className="truncate">{card.area.name}</span>
                                </Badge>
                            )}
                            <span className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                                <span className="sr-only">Tipo: </span>
                                {card.type.label}
                            </span>
                        </div>
                        {menu}
                    </div>

                    {/* Chave, título (o link principal) e o card pai. */}
                    <div className="grid gap-0.5 pr-2">
                        <WTaskKeyAtom taskKey={card.key} size="sm" />
                        <h3 className="line-clamp-2 leading-snug font-medium break-words">{title}</h3>
                        {card.parent && (
                            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                <CornerDownRight className="size-3.5 shrink-0" aria-hidden />
                                <span className="sr-only">Card pai:</span>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <span className="inline-flex">
                                            <WTaskKeyAtom taskKey={card.parent.key} href={card.parent.url} size="sm" />
                                        </span>
                                    </TooltipTrigger>
                                    <TooltipContent className="max-w-64">{card.parent.title}</TooltipContent>
                                </Tooltip>
                            </p>
                        )}
                    </div>

                    {/* Marcas logo abaixo do título: o que pede atenção fica perto do nome do card. */}
                    <WTaskMarksMolecule card={card} inPausedStage={stage.role === 'paused'} className="pr-2" />

                    {/* Corpo: capa e horas previstas. */}
                    {card.cover_url && (
                        <img
                            src={card.cover_url}
                            alt=""
                            loading="lazy"
                            draggable={false}
                            className="h-28 w-full rounded-md border border-border object-cover"
                        />
                    )}
                    {card.estimated_hours !== null && (
                        <p className="flex items-center gap-1 text-xs text-muted-foreground" title="Horas previstas">
                            <Clock className="size-3.5" aria-hidden />
                            <span className="sr-only">Horas previstas:</span>
                            <span className="font-mono tabular-nums">{hours(card.estimated_hours)}</span>
                        </p>
                    )}

                    {/* Rodapé: pessoas, contadores e progresso dos filhos. */}
                    {(hasPeople || hasCounters) && (
                        <div className="flex flex-wrap items-center justify-between gap-2 pr-2">
                            <WPeopleAvatarsMolecule
                                developer={card.developer}
                                qa={card.qa}
                                participants={card.participants}
                                members={members}
                                onToggleParticipant={
                                    onToggleParticipant && ((person, checked) => onToggleParticipant(card, person, checked))
                                }
                                size="sm"
                            />
                            <WCardCountersMolecule
                                dueOn={card.due_on}
                                attachmentsCount={card.attachments_count}
                                commentsCount={card.comments_count}
                                late={card.late}
                                className="ml-auto"
                            />
                        </div>
                    )}
                    {/* Progresso dos filhos: contagem discreta no rodapé e a barra fina rente à borda de baixo. */}
                    <WChildProgressMolecule progress={card.children} layout="edge" className="pr-2" />
                </>
            )}
        </Card>
    );
}

interface TaskCardMenuProps {
    card: BoardCard;
    stage: BoardStage;
    stages: BoardStage[];
    areas: AreaRef[];
    onMove?: WTaskCardOrganismProps['onMove'];
    onChangePriority?: WTaskCardOrganismProps['onChangePriority'];
    onChangeArea?: WTaskCardOrganismProps['onChangeArea'];
    onOpen?: WTaskCardOrganismProps['onOpen'];
}

/** O menu ⋯ do card (F3 §03): Abrir ficha, Copiar link, Mover para…, Prioridade e Área. */
function TaskCardMenu({ card, stage, stages, areas, onMove, onChangePriority, onChangeArea, onOpen }: TaskCardMenuProps) {
    const targets = stages.filter((item) => item.id !== stage.id);
    const editable = Boolean(onMove || onChangePriority || onChangeArea);

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(new URL(card.url, window.location.origin).href);
            toast.success('Link copiado');
        } catch {
            toast.error('Não foi possível copiar o link');
        }
    };

    // modal={false}: o menu não esconde o resto do quadro do leitor de tela (aria-hidden com foco dentro,
    // regra aria-hidden-focus do axe) nem trava o ponteiro da página, onde o dnd-kit escuta.
    return (
        <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" aria-label={`Ações de ${card.key}`} className="size-7 shrink-0 text-muted-foreground">
                    <MoreHorizontal aria-hidden />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-52">
                {onOpen ? (
                    <DropdownMenuItem onSelect={() => onOpen(card)}>
                        <ExternalLink aria-hidden /> Abrir ficha
                    </DropdownMenuItem>
                ) : (
                    <DropdownMenuItem asChild>
                        <Link href={card.url}>
                            <ExternalLink aria-hidden /> Abrir ficha
                        </Link>
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem onSelect={copyLink}>
                    <Link2 aria-hidden /> Copiar link
                </DropdownMenuItem>

                {editable && <DropdownMenuSeparator />}

                {onMove && (
                    <DropdownMenuSub>
                        <DropdownMenuSubTrigger disabled={targets.length === 0}>
                            <ArrowRightLeft aria-hidden /> Mover para…
                        </DropdownMenuSubTrigger>
                        <DropdownMenuSubContent className="w-44">
                            {targets.map((target) => (
                                <DropdownMenuItem key={target.id} onSelect={() => onMove(card, target)}>
                                    {target.name}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuSubContent>
                    </DropdownMenuSub>
                )}

                {onChangePriority && (
                    <DropdownMenuSub>
                        <DropdownMenuSubTrigger>
                            <Flag aria-hidden /> Prioridade
                        </DropdownMenuSubTrigger>
                        <DropdownMenuSubContent className="w-40">
                            <DropdownMenuRadioGroup
                                value={card.priority}
                                onValueChange={(value) => value !== card.priority && onChangePriority(card, value as PrioritySlug)}
                            >
                                {PRIORITY_MENU.map((priority) => (
                                    <DropdownMenuRadioItem key={priority} value={priority}>
                                        <WPriorityMarkAtom priority={priority} variant="chip" />
                                    </DropdownMenuRadioItem>
                                ))}
                            </DropdownMenuRadioGroup>
                        </DropdownMenuSubContent>
                    </DropdownMenuSub>
                )}

                {onChangeArea && (
                    <DropdownMenuSub>
                        <DropdownMenuSubTrigger>
                            <Tag aria-hidden /> Área
                        </DropdownMenuSubTrigger>
                        <DropdownMenuSubContent className="w-48">
                            {areas.length === 0 ? (
                                <DropdownMenuItem disabled className="data-[disabled]:pointer-events-none data-[disabled]:opacity-60">
                                    Nenhuma área no projeto
                                </DropdownMenuItem>
                            ) : (
                                <DropdownMenuRadioGroup
                                    value={card.area ? String(card.area.id) : NO_AREA}
                                    onValueChange={(value) => {
                                        const area = areas.find((item) => String(item.id) === value) ?? null;
                                        if ((area?.id ?? null) !== (card.area?.id ?? null)) onChangeArea(card, area);
                                    }}
                                >
                                    {areas.map((area) => (
                                        <DropdownMenuRadioItem key={area.id} value={String(area.id)}>
                                            {area.name}
                                        </DropdownMenuRadioItem>
                                    ))}
                                    <DropdownMenuSeparator />
                                    <DropdownMenuRadioItem value={NO_AREA}>Nenhuma</DropdownMenuRadioItem>
                                </DropdownMenuRadioGroup>
                            )}
                        </DropdownMenuSubContent>
                    </DropdownMenuSub>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
