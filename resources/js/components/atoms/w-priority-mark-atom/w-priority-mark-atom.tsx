import { ArrowDown, ArrowUp, ChevronsUp, Minus, type LucideIcon } from 'lucide-react';

import { PRIORITY_LABEL } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { PrioritySlug } from '@/types/quadro';

/** Cor da faixa e do ícone por prioridade (P19). Só tokens. */
const TONE: Record<PrioritySlug, { bar: string; icon: string; badge: string; Icon: LucideIcon }> = {
    low: {
        bar: 'bg-muted-foreground/40',
        icon: 'text-muted-foreground',
        badge: 'border-border bg-card text-muted-foreground',
        Icon: ArrowDown,
    },
    normal: {
        bar: 'bg-primary/60',
        icon: 'text-primary-text',
        badge: 'border-transparent bg-status-neutral-bg text-status-neutral',
        Icon: Minus,
    },
    high: {
        bar: 'bg-status-pending',
        icon: 'text-status-pending',
        badge: 'border-transparent bg-status-pending-bg text-status-pending',
        Icon: ArrowUp,
    },
    urgent: {
        bar: 'bg-status-rejected',
        icon: 'text-status-rejected',
        badge: 'border-transparent bg-status-rejected-bg text-status-rejected',
        Icon: ChevronsUp,
    },
};

export interface WPriorityMarkAtomProps {
    priority: PrioritySlug;
    /**
     * `bar`: a faixa na borda superior do card (o pai precisa de `relative`).
     * `chip`: ícone + rótulo em linha, para menu e filtros.
     * `badge`: pílula com fundo, ícone e rótulo: o canto inferior direito do card, como no Trello (decisão de 14/10).
     */
    variant?: 'bar' | 'chip' | 'badge';
    className?: string;
}

/** A prioridade do card (P19): faixa colorida na borda, ícone + rótulo em linha, ou a pílula do rodapé. Nunca só cor. */
export function WPriorityMarkAtom({ priority, variant = 'bar', className }: WPriorityMarkAtomProps) {
    const tone = TONE[priority];
    const text = `Prioridade: ${PRIORITY_LABEL[priority]}`;

    if (variant === 'badge') {
        const Icon = tone.Icon;
        return (
            <span
                data-slot="w-priority-mark-atom"
                data-priority={priority}
                className={cn(
                    'inline-flex items-center gap-1 rounded-md border px-1.5 py-px text-2xs font-medium whitespace-nowrap',
                    tone.badge,
                    className,
                )}
            >
                <Icon className="size-3 shrink-0" aria-hidden />
                <span className="sr-only">Prioridade: </span>
                {PRIORITY_LABEL[priority]}
            </span>
        );
    }

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
            className={cn('absolute inset-x-0 top-0 h-1', tone.bar, className)}
        >
            <span className="sr-only">{text}</span>
        </span>
    );
}
