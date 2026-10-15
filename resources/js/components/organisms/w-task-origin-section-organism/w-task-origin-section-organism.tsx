import { Link } from '@inertiajs/react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { WTaskPickerMolecule } from '@/components/molecules/w-task-picker-molecule/w-task-picker-molecule';
import { cn } from '@/lib/utils';
import type { TaskPickRef, TaskRef } from '@/types/quadro';

/** "Bug de produção de SGM-17" / "2 bugs de produção ligados" / "Sem tarefa de origem". */
export function originSummary(originTask: TaskRef | null, productionBugs: TaskRef[]): string {
    const parts: string[] = [];
    if (originTask) parts.push(`Bug de produção de ${originTask.key}`);
    if (productionBugs.length > 0) {
        parts.push(`${productionBugs.length} ${productionBugs.length === 1 ? 'bug de produção ligado' : 'bugs de produção ligados'}`);
    }
    return parts.length > 0 ? parts.join(' · ') : 'Sem tarefa de origem';
}

export interface WTaskOriginSectionOrganismProps {
    originTask: TaskRef | null;
    /** No card original: os bugs de produção que apontam para ele (F3 §03). */
    productionBugs: TaskRef[];
    cards: TaskPickRef[];
    canEdit: boolean;
    busy?: boolean;
    /** Erros do servidor: `origin_task_id`. */
    errors?: Record<string, string>;
    onChangeOrigin: (card: TaskRef | null) => void;
    className?: string;
}

/**
 * Tarefa de origem da ficha (F3 §02, P25): preenchida, este card é um bug de produção da tarefa
 * indicada. No card original, a lista "Bugs de produção ligados", com chave e título (F3 §03).
 */
export function WTaskOriginSectionOrganism({
    originTask,
    productionBugs,
    cards,
    canEdit,
    busy = false,
    errors,
    onChangeOrigin,
    className,
}: WTaskOriginSectionOrganismProps) {
    return (
        <div data-slot="w-task-origin-section-organism" aria-busy={busy || undefined} className={cn('grid gap-4', className)}>
            <div className="grid gap-2">
                <h3 className="font-mono text-xs tracking-wider text-muted-foreground uppercase">Tarefa de origem</h3>
                <p className="text-xs text-muted-foreground">Preenchida, este card é um bug de produção da tarefa indicada (P25).</p>
                {!canEdit && !originTask ? (
                    <p className="text-sm text-muted-foreground">—</p>
                ) : (
                    <WTaskPickerMolecule
                        cards={cards}
                        mode="origin"
                        value={originTask}
                        onChange={onChangeOrigin}
                        label="Tarefa de origem"
                        disabled={!canEdit || busy}
                        error={errors?.origin_task_id}
                    />
                )}
            </div>

            {productionBugs.length > 0 && (
                <div className="grid gap-2">
                    <h3 className="font-mono text-xs tracking-wider text-muted-foreground uppercase">Bugs de produção ligados</h3>
                    <ul className="grid divide-y divide-border rounded-md border border-border">
                        {productionBugs.map((bug) => (
                            <li key={bug.id} className="flex items-center gap-3 px-3 py-2 text-sm">
                                <WTaskKeyAtom taskKey={bug.key} href={bug.url} title={bug.title} />
                                <Link href={bug.url} className="min-w-0 flex-1 truncate hover:underline">
                                    {bug.title}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
