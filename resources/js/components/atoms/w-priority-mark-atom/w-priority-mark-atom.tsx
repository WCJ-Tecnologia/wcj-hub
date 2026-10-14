import { ArrowDown, ArrowUp, ChevronsUp, Minus, type LucideIcon } from 'lucide-react';

import { PRIORITY_LABEL } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { PrioritySlug } from '@/types/quadro';

/** Cor da faixa e do ícone por prioridade (P19). Só tokens. */
const TONE: Record<PrioritySlug, { bar: string; icon: string; Icon: LucideIcon }> = {
    low: { bar: 'bg-muted-foreground/40', icon: 'text-muted-foreground', Icon: ArrowDown },
    normal: { bar: 'bg-primary/60', icon: 'text-primary-text', Icon: Minus },
    high: { bar: 'bg-status-pending', icon: 'text-status-pending', Icon: ArrowUp },
    urgent: { bar: 'bg-status-rejected', icon: 'text-status-rejected', Icon: ChevronsUp },
};

export interface WPriorityMarkAtomProps {
    priority: PrioritySlug;
    /**
     * `bar`: a faixa na borda esquerda do card (o pai precisa de `relative`).
     * `chip`: ícone + rótulo em linha, para menu e filtros.
     */
    variant?: 'bar' | 'chip';
    className?: string;
}

/** A prioridade do card (P19): faixa colorida na borda, ou ícone + rótulo em linha. Nunca só cor. */
export function WPriorityMarkAtom({ priority, variant = 'bar', className }: WPriorityMarkAtomProps) {
    const tone = TONE[priority];
    const text = `Prioridade: ${PRIORITY_LABEL[priority]}`;

    if (variant === 'chip') {
        const Icon = tone.Icon;
        return (
            <span data-slot="w-priority-mark-atom" data-priority={priority} className={cn('inline-flex items-center gap-1.5', className)}>
                <Icon className={cn('size-4 shrink-0', tone.icon)} aria-hidden />
                {PRIORITY_LABEL[priority]}
            </span>
        );
    }

    return (
        <span
            data-slot="w-priority-mark-atom"
            data-priority={priority}
            title={text}
            className={cn('absolute inset-y-0 left-0 w-1', tone.bar, className)}
        >
            <span className="sr-only">{text}</span>
        </span>
    );
}
