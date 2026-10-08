import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export interface WPageHeaderMoleculeProps {
    /** Grupo ou contexto acima do título (ex.: "Cadastros"). */
    eyebrow?: string;
    title: string;
    subtitle?: string;
    /** Botões à direita (ex.: "Novo usuário"). */
    actions?: ReactNode;
    className?: string;
}

/**
 * Topo de toda página: eyebrow (grupo/contexto) → h1 → subtítulo, com as ações
 * à direita. É o único h1 da tela.
 */
export function WPageHeaderMolecule({ eyebrow, title, subtitle, actions, className }: WPageHeaderMoleculeProps) {
    return (
        <header
            data-slot="w-page-header-molecule"
            className={cn('mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between', className)}
        >
            <div className="min-w-0">
                {eyebrow && <p className="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">{eyebrow}</p>}
                <h1 className="truncate font-heading text-xl font-bold tracking-tight sm:text-2xl">{title}</h1>
                {subtitle && <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
        </header>
    );
}
