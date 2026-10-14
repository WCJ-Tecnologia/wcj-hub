import { ListTree } from 'lucide-react';

import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/shadcn/tooltip';
import { childProgressPercent } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { ChildProgress } from '@/types/quadro';

export interface WChildProgressMoleculeProps {
    /** `card.children`: só o card pai tem; null não renderiza nada (P57). */
    progress: ChildProgress | null;
    className?: string;
}

/**
 * Progresso dos cards filhos no card pai (F3 §03, P57): "2 · 1 · 3" (a fazer · em andamento ·
 * concluídos) e a barra pelo percentual concluído.
 */
export function WChildProgressMolecule({ progress, className }: WChildProgressMoleculeProps) {
    if (!progress) return null;

    const percent = childProgressPercent(progress);
    const detail = `${progress.todo} a fazer · ${progress.doing} em andamento · ${progress.done} ${progress.done === 1 ? 'concluído' : 'concluídos'}`;

    return (
        <div data-slot="w-child-progress-molecule" className={cn('grid gap-1', className)}>
            <div className="flex items-center justify-between gap-2 text-xs text-muted-foreground">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <span className="inline-flex items-center gap-1">
                            <ListTree className="size-3.5" aria-hidden />
                            <span aria-hidden className="font-mono tabular-nums">
                                {progress.todo} · {progress.doing} · {progress.done}
                            </span>
                            <span className="sr-only">Cards filhos: {detail}</span>
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>{detail}</TooltipContent>
                </Tooltip>
                <span aria-hidden className="font-mono tabular-nums">
                    {percent}%
                </span>
            </div>
            <div
                role="progressbar"
                aria-label="Progresso dos cards filhos"
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={percent}
                aria-valuetext={`${percent}% concluído`}
                className="h-1.5 overflow-hidden rounded-full bg-muted"
            >
                <div className="h-full rounded-full bg-primary transition-[width]" style={{ width: `${percent}%` }} />
            </div>
        </div>
    );
}
