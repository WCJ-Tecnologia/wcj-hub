import { Link } from '@inertiajs/react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WChildProgressMolecule } from '@/components/molecules/w-child-progress-molecule/w-child-progress-molecule';
import { WTaskPickerMolecule } from '@/components/molecules/w-task-picker-molecule/w-task-picker-molecule';
import { Badge } from '@/components/shadcn/badge';
import { childProgressOf, childrenSummaryText } from '@/lib/ficha';
import { cn } from '@/lib/utils';
import type { ChildCard, ParentRef, TaskPickRef, TaskRef } from '@/types/quadro';

/** "Filho de SGM-10" / "Pai de 7 cards · 2 a fazer · 2 em andamento · 3 concluídos" / "Sem card pai nem filhos". */
export function parentSummary(parent: ParentRef | null, children: ChildCard[]): string {
    const progress = childProgressOf(children);
    if (progress) return `Pai de ${children.length} ${children.length === 1 ? 'card' : 'cards'} · ${childrenSummaryText(progress)}`;
    if (parent) return `Filho de ${parent.key}`;
    return 'Sem card pai nem filhos';
}

export interface WTaskParentSectionOrganismProps {
    taskKey: string;
    parent: ParentRef | null;
    /** Só no card pai (P57); vazio no resto. */
    children: ChildCard[];
    /** `options.cards` (sem os arquivados e sem este card). */
    cards: TaskPickRef[];
    canEdit: boolean;
    busy?: boolean;
    /** Erros do servidor: `parent_task_id`. */
    errors?: Record<string, string>;
    onChangeParent: (card: TaskRef | null) => void;
    className?: string;
}

/**
 * Card pai e cards filhos da ficha (F3 §02, §04, §05; P57). No filho: a busca do card pai, com a
 * chave e o título do pai com link. No pai: o resumo e a barra de progresso dos filhos e a lista
 * deles (chave, título, coluna e dev). Um nível só: card com filhos não pode ter pai.
 */
export function WTaskParentSectionOrganism({
    taskKey,
    parent,
    children,
    cards,
    canEdit,
    busy = false,
    errors,
    onChangeParent,
    className,
}: WTaskParentSectionOrganismProps) {
    const progress = childProgressOf(children);
    const hasChildren = progress !== null;

    return (
        <div data-slot="w-task-parent-section-organism" aria-busy={busy || undefined} className={cn('grid gap-4', className)}>
            <div className="grid gap-2">
                <h3 className="font-mono text-xs tracking-wider text-muted-foreground uppercase">Card pai</h3>
                {!canEdit && !parent ? (
                    <p className="text-sm text-muted-foreground">—</p>
                ) : (
                    <WTaskPickerMolecule
                        cards={cards}
                        mode="parent"
                        value={parent}
                        onChange={onChangeParent}
                        label="Card pai"
                        disabled={!canEdit || busy || hasChildren}
                        error={errors?.parent_task_id}
                    />
                )}
                {hasChildren && <p className="text-xs text-muted-foreground">Card com filhos não pode ter pai: um nível só (P57).</p>}
            </div>

            {progress && (
                <div className="grid gap-2">
                    <h3 className="font-mono text-xs tracking-wider text-muted-foreground uppercase">Cards filhos</h3>
                    <p className="text-sm">{childrenSummaryText(progress)}</p>
                    <WChildProgressMolecule progress={progress} />
                    <ul aria-label={`Cards filhos de ${taskKey}`} className="grid divide-y divide-border rounded-md border border-border">
                        {children.map((child) => (
                            <li key={child.id} className="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2 text-sm">
                                <WTaskKeyAtom taskKey={child.key} href={child.url} title={child.title} />
                                <Link href={child.url} className="min-w-0 flex-1 truncate hover:underline">
                                    {child.title}
                                </Link>
                                <Badge variant="outline">{child.stage.name}</Badge>
                                <span className={cn('shrink-0 text-xs', !child.developer && 'text-muted-foreground')}>
                                    {child.developer?.name ?? 'sem dev'}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
