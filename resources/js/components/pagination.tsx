import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

/** Rodapé de listagem: "mostrando X–Y de Z" + links do paginator do Laravel. */
export function Pagination<T>({ meta }: { meta: Paginated<T> }) {
    if (meta.last_page <= 1) return null;

    return (
        <nav className="mt-3 flex flex-col items-center justify-between gap-3 px-1 sm:flex-row" aria-label="Paginação">
            <p className="text-xs text-muted-foreground">
                Mostrando <strong className="text-foreground">{meta.from ?? 0}</strong>–
                <strong className="text-foreground">{meta.to ?? 0}</strong> de <strong className="text-foreground">{meta.total}</strong>
            </p>

            <div className="flex flex-wrap items-center gap-1">
                {meta.links.map((link, index) => {
                    const label = link.label
                        .replace('&laquo; Previous', 'Anterior')
                        .replace('Next &raquo;', 'Próxima')
                        .replace('pagination.previous', 'Anterior')
                        .replace('pagination.next', 'Próxima');

                    if (!link.url) {
                        return (
                            <span key={index} className="rounded-md px-3 py-1.5 text-xs text-muted-foreground" aria-disabled="true">
                                {label}
                            </span>
                        );
                    }

                    return (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            preserveState
                            aria-current={link.active ? 'page' : undefined}
                            className={cn(
                                'rounded-md px-3 py-1.5 text-xs font-medium transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                            )}
                        >
                            {label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
