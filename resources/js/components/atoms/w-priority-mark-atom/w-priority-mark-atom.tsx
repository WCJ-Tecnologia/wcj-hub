import { ArrowDown, ArrowUp, ChevronsUp, Minus, type LucideIcon } from 'lucide-react';

import { PRIORITY_LABEL } from '@/lib/quadro';
import { cn } from '@/lib/utils';
import type { PrioritySlug } from '@/types/quadro';

/** Cor da faixa e do ícone por prioridade (P19). Só tokens. */
/** Cor da faixa e do ícone por prioridade (P19): tokens `--priority-*` de design/tokens.json (14/10). Classes inteiras, para o Tailwind achar. */
const TONE: Record<PrioritySlug, { bar: string; icon: string; Icon: LucideIcon }> = {
    low: { bar: 'bg-priority-low', icon: 'text-priority-low', Icon: ArrowDown },
    normal: { bar: 'bg-priority-normal', icon: 'text-priority-normal', Icon: Minus },
    high: { bar: 'bg-priority-high', icon: 'text-priority-high', Icon: ArrowUp },
    urgent: { bar: 'bg-priority-urgent', icon: 'text-priority-urgent', Icon: ChevronsUp },
};

export interface WPriorityMarkAtomProps {
    priority: PrioritySlug;
    /**
     * `bar`: a aba colorida no topo do card com o nome da prioridade (pasta suspensa, decisão de 14/10). O pai precisa de
     * `relative`, `overflow-hidden`, `pt-6` e um painel por cima com `rounded-t-xl`, encostado nas laterais.
     * `chip`: ícone + rótulo em linha, para menu e filtros.
     */
    variant?: 'bar' | 'chip';
    className?: string;
}

/** A prioridade do card (P19): a faixa do topo com o nome, ou ícone + rótulo em linha. Nunca só cor. */
export function WPriorityMarkAtom({ priority, variant = 'bar', className }: WPriorityMarkAtomProps) {
    const tone = TONE[priority];

    if (variant === 'chip') {
        const Icon = tone.Icon;
        return (
            <span data-slot="w-priority-mark-atom" data-priority={priority} className={cn('inline-flex items-center gap-1.5', className)}>
                <Icon className={cn('size-4 shrink-0', tone.icon)} aria-hidden />
                {PRIORITY_LABEL[priority]}
            </span>
        );
    }

    // A faixa é a aba da pasta suspensa (referência do Lucas, 14/10): cor sólida atrás do topo; o painel do conteúdo entra
    // por cima, encostado nas laterais e com o canto de cima bem arredondado, e a cor só aparece nas orelhas, afinando
    // até sumir. Texto em primary-foreground: contrasta nos dois temas.
    return (
        <span
            data-slot="w-priority-mark-atom"
            data-priority={priority}
            className={cn(
                'absolute inset-x-0 top-0 flex h-10 items-start justify-center pt-1 font-mono text-2xs font-medium tracking-wider text-primary-foreground uppercase',
                tone.bar,
                className,
            )}
        >
            <span className="sr-only">Prioridade: </span>
            {PRIORITY_LABEL[priority]}
        </span>
    );
}
