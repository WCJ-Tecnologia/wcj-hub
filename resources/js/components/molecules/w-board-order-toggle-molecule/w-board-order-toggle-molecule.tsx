import { useId } from 'react';

import { Button } from '@/components/shadcn/button';
import { cn } from '@/lib/utils';
import type { BoardOrder } from '@/types/quadro';

const OPTIONS: { value: BoardOrder; label: string }[] = [
    { value: 'priority', label: 'Prioridade' },
    { value: 'manual', label: 'Manual' },
];

export interface WBoardOrderToggleMoleculeProps {
    value: BoardOrder;
    onChange: (order: BoardOrder) => void;
    className?: string;
}

/**
 * O seletor *Ordem: Prioridade | Manual* do quadro (F3 §03, P13): dois botões segmentados com
 * `aria-pressed`. Não guarda nada: a página lembra a escolha no navegador.
 */
export function WBoardOrderToggleMolecule({ value, onChange, className }: WBoardOrderToggleMoleculeProps) {
    const labelId = useId();

    return (
        <div data-slot="w-board-order-toggle-molecule" className={cn('inline-flex items-center gap-2', className)}>
            <span id={labelId} className="font-mono text-xs tracking-wider text-muted-foreground uppercase">
                Ordem
            </span>
            <div role="group" aria-labelledby={labelId} className="inline-flex rounded-md border border-input bg-card p-0.5 shadow-sm">
                {OPTIONS.map((option) => {
                    const active = option.value === value;
                    return (
                        <Button
                            key={option.value}
                            type="button"
                            size="sm"
                            variant={active ? 'secondary' : 'ghost'}
                            aria-pressed={active}
                            onClick={() => !active && onChange(option.value)}
                            className={cn('h-7 px-2.5 shadow-none', !active && 'text-muted-foreground')}
                        >
                            {option.label}
                        </Button>
                    );
                })}
            </div>
        </div>
    );
}
