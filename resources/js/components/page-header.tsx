import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * Topo de toda página: eyebrow (grupo/contexto) → h1 → subtítulo, com as ações
 * à direita. É o único h1 da tela.
 */
export function PageHeader({
    eyebrow,
    title,
    subtitle,
    actions,
    className,
}: {
    eyebrow?: string;
    title: string;
    subtitle?: string;
    actions?: ReactNode;
    className?: string;
}) {
    return (
        <header className={cn('mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between', className)}>
            <div className="min-w-0">
                {eyebrow && <p className="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">{eyebrow}</p>}
                <h1 className="truncate font-heading text-xl font-bold tracking-tight sm:text-2xl">{title}</h1>
                {subtitle && <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
        </header>
    );
}
