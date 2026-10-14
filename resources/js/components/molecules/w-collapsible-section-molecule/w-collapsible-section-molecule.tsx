import { ChevronRight } from 'lucide-react';
import { type ReactNode, useId, useState } from 'react';

import { cn } from '@/lib/utils';

export interface WCollapsibleSectionMoleculeProps {
    /** O nome da seção (o h2 do bloco): "Critérios de aceite". */
    title: string;
    /** Uma linha de resumo ao lado do título, visível recolhido e aberto: "3 critérios · 1 reprovado". */
    summary?: ReactNode;
    /** Contagem no cabeçalho, em Space Mono; 0 mostra "0". */
    count?: number;
    /** Botões à direita do cabeçalho (ex.: *Editar*). Fora do botão que abre e fecha. */
    actions?: ReactNode;
    /** Começa aberta. A ficha começa tudo recolhido (F3 §01, R4). */
    defaultOpen?: boolean;
    /** Controlado: a tela decide. */
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    className?: string;
    children: ReactNode;
}

/**
 * Seção recolhível da ficha (F3 §01: "tudo além do título é opcional e vem recolhido"). O cabeçalho
 * inteiro é o botão (`aria-expanded`, `aria-controls`); o resumo diz o que há dentro sem abrir.
 */
export function WCollapsibleSectionMolecule({
    title,
    summary,
    count,
    actions,
    defaultOpen = false,
    open: controlled,
    onOpenChange,
    className,
    children,
}: WCollapsibleSectionMoleculeProps) {
    const [internal, setInternal] = useState(defaultOpen);
    const open = controlled ?? internal;
    const contentId = useId();

    const toggle = () => {
        const next = !open;
        if (controlled === undefined) setInternal(next);
        onOpenChange?.(next);
    };

    return (
        <section
            data-slot="w-collapsible-section-molecule"
            data-open={open || undefined}
            className={cn('rounded-md border border-border bg-card', className)}
        >
            <div className="flex items-center gap-2 pr-2">
                <h2 className="min-w-0 flex-1">
                    <button
                        type="button"
                        aria-expanded={open}
                        aria-controls={contentId}
                        onClick={toggle}
                        className="flex w-full min-w-0 items-center gap-2 rounded-md px-3 py-2.5 text-left outline-none hover:bg-accent/60 focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <ChevronRight
                            className={cn('size-4 shrink-0 text-muted-foreground transition-transform', open && 'rotate-90')}
                            aria-hidden
                        />
                        <span className="font-heading text-sm font-semibold">{title}</span>
                        {count !== undefined && (
                            <span className="rounded-sm bg-muted px-1.5 font-mono text-xs text-muted-foreground tabular-nums">{count}</span>
                        )}
                        {summary && <span className="min-w-0 flex-1 truncate text-xs text-muted-foreground">{summary}</span>}
                    </button>
                </h2>
                {actions && <div className="flex shrink-0 items-center gap-1">{actions}</div>}
            </div>
            <div id={contentId} hidden={!open} className="border-t border-border px-4 py-3">
                {children}
            </div>
        </section>
    );
}
