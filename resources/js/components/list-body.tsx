import { AlertTriangle } from 'lucide-react';
import type { ReactNode } from 'react';

import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import type { useListVisit } from '@/hooks/use-list-visit';
import { cn } from '@/lib/utils';

/**
 * O corpo de uma listagem com os estados de erro e de carregando (stacks/ui.md §2), pelo
 * `useListVisit` da página: no erro, o aviso com Tentar de novo; senão, a lista esmaecida e
 * marcada como ocupada enquanto a visita roda, com o aviso para o leitor de tela.
 */
export function ListBody({
    list,
    failedTitle,
    loadingText,
    children,
}: {
    list: ReturnType<typeof useListVisit>;
    /** "Não foi possível carregar os usuários". */
    failedTitle: string;
    /** "Carregando usuários…". */
    loadingText: string;
    children: ReactNode;
}) {
    if (list.failed) {
        return (
            <EmptyState
                icon={<AlertTriangle className="size-5" aria-hidden />}
                title={failedTitle}
                description="Confira a conexão e tente de novo."
                action={<Button onClick={list.retry}>Tentar de novo</Button>}
            />
        );
    }

    return (
        <div aria-busy={list.loading} className={cn('transition-opacity', list.loading && 'opacity-60')}>
            <p role="status" className="sr-only">
                {list.loading ? loadingText : ''}
            </p>
            {children}
        </div>
    );
}
