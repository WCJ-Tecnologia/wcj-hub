import { Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';

import { Pagination, PaginationContent, PaginationEllipsis, PaginationItem, PaginationLink } from '@/components/shadcn/pagination';
import type { Paginated, PaginationLink as PaginatorLink } from '@/types';

export interface WPaginationMoleculeProps<T> {
    /** O paginator do Laravel como vem do controller (`->paginate()`). */
    meta: Paginated<T>;
}

type Kind = 'previous' | 'next' | 'ellipsis' | 'page';

/** The Laravel paginator labels its links; this tells them apart. */
function kindOf(link: PaginatorLink): Kind {
    if (/Previous|pagination\.previous/.test(link.label)) return 'previous';
    if (/Next|pagination\.next/.test(link.label)) return 'next';
    if (link.label === '...') return 'ellipsis';
    return 'page';
}

/**
 * Rodapé de listagem: "mostrando X–Y de Z" e os links do paginator do Laravel,
 * no Pagination do shadcn com o Link do Inertia (preserveScroll + preserveState).
 */
export function WPaginationMolecule<T>({ meta }: WPaginationMoleculeProps<T>) {
    if (meta.last_page <= 1) return null;

    return (
        <div data-slot="w-pagination-molecule" className="mt-3 flex flex-col items-center justify-between gap-3 px-1 sm:flex-row">
            <p className="text-xs text-muted-foreground">
                Mostrando <strong className="text-foreground">{meta.from ?? 0}</strong>–
                <strong className="text-foreground">{meta.to ?? 0}</strong> de <strong className="text-foreground">{meta.total}</strong>
            </p>

            <Pagination className="mx-0 w-auto">
                <PaginationContent className="flex-wrap">
                    {meta.links.map((link, index) => {
                        const kind = kindOf(link);

                        if (kind === 'ellipsis') {
                            return (
                                <PaginationItem key={index}>
                                    <PaginationEllipsis />
                                </PaginationItem>
                            );
                        }

                        const content =
                            kind === 'previous' ? (
                                <>
                                    <ChevronLeftIcon aria-hidden />
                                    <span className="hidden sm:block">Anterior</span>
                                </>
                            ) : kind === 'next' ? (
                                <>
                                    <span className="hidden sm:block">Próxima</span>
                                    <ChevronRightIcon aria-hidden />
                                </>
                            ) : (
                                link.label
                            );
                        const ariaLabel =
                            kind === 'previous' ? 'Página anterior' : kind === 'next' ? 'Próxima página' : `Página ${link.label}`;
                        const size = kind === 'page' ? 'icon' : 'default';

                        if (!link.url) {
                            return (
                                <PaginationItem key={index}>
                                    <PaginationLink
                                        size={size}
                                        aria-disabled="true"
                                        aria-label={ariaLabel}
                                        className="pointer-events-none text-muted-foreground opacity-50"
                                    >
                                        {content}
                                    </PaginationLink>
                                </PaginationItem>
                            );
                        }

                        return (
                            <PaginationItem key={index}>
                                <PaginationLink asChild size={size} isActive={link.active} aria-label={ariaLabel}>
                                    <Link href={link.url} preserveScroll preserveState>
                                        {content}
                                    </Link>
                                </PaginationLink>
                            </PaginationItem>
                        );
                    })}
                </PaginationContent>
            </Pagination>
        </div>
    );
}
