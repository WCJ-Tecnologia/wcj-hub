import { CalendarPlus } from 'lucide-react';

import { Button } from '@/components/shadcn/button';
import { batchSummary, batchSummaryText } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { BoardCard, BoardProject } from '@/types/quadro';

export interface WSprintBatchBarOrganismProps {
    project: Pick<BoardProject, 'open_sprint'>;
    /** Os cards marcados no Backlog. */
    selected: Pick<BoardCard, 'estimated_hours'>[];
    /** Sai do modo de seleção sem gravar. */
    onCancel: () => void;
    /** Abre o diálogo de confirmação (*Iniciar sprint* ou *Pôr na Sprint N*). */
    onConfirm: () => void;
    className?: string;
}

/**
 * A barra do *Montar sprint* (D-GER-35), presa no pé da área do conteúdo (`sticky`, sem cobrir a barra
 * lateral) enquanto o modo de seleção do quadro está ligado: "3 selecionadas · 14 h previstas",
 * *Cancelar* e *Iniciar sprint* (sem sprint aberta) ou *Pôr na Sprint N*. O total é só referência
 * (P49): nada aqui olha a capacidade.
 */
export function WSprintBatchBarOrganism({ project, selected, onCancel, onConfirm, className }: WSprintBatchBarOrganismProps) {
    const summary = batchSummary(selected);

    return (
        <div
            data-slot="w-sprint-batch-bar-organism"
            role="region"
            aria-label="Montar sprint"
            className={cn(
                'sticky bottom-4 z-40 mx-auto flex w-full max-w-2xl flex-col gap-3 rounded-lg border border-border bg-card p-3 shadow-2xl sm:flex-row sm:items-center',
                className,
            )}
        >
            <p role="status" className="flex-1 text-sm">
                <span className="font-semibold">{batchSummaryText(summary)}</span>
                <span className="block text-xs text-muted-foreground">Marque as tarefas na coluna Backlog.</span>
            </p>
            <div className="flex flex-col-reverse gap-2 sm:flex-row">
                <Button variant="outline" onClick={onCancel}>
                    Cancelar
                </Button>
                <Button onClick={onConfirm} disabled={summary.count === 0}>
                    <CalendarPlus aria-hidden /> {project.open_sprint ? `Pôr na ${project.open_sprint.name}` : 'Iniciar sprint'}
                </Button>
            </div>
        </div>
    );
}
