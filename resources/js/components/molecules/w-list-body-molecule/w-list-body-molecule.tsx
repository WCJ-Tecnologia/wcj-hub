import { AlertTriangle } from 'lucide-react';
import type { ReactNode } from 'react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { Button } from '@/components/shadcn/button';
import type { useListVisit } from '@/hooks/use-list-visit';
import { cn } from '@/lib/utils';

export interface WListBodyMoleculeProps {
    /** O `useListVisit()` da página. */
    list: ReturnType<typeof useListVisit>;
    /** "Não foi possível carregar os usuários". */
    failedTitle: string;
    /** "Carregando usuários…". */
    loadingText: string;
    children: ReactNode;
}

/**
 * O corpo de uma listagem com os estados de erro e de carregando (stacks/ui.md §2), pelo
 * `useListVisit` da página: no erro, o aviso com Tentar de novo; senão, a lista esmaecida e
 * marcada como ocupada enquanto a visita roda, com o aviso para o leitor de tela.
 */
export function WListBodyMolecule({ list, failedTitle, loadingText, children }: WListBodyMoleculeProps) {
    if (list.failed) {
        return (
            <WEmptyStateMolecule
                icon={<AlertTriangle className="size-5" aria-hidden />}
                title={failedTitle}
                description="Confira a conexão e tente de novo."
                action={<Button onClick={list.retry}>Tentar de novo</Button>}
            />
        );
    }

    return (
        <div data-slot="w-list-body-molecule" aria-busy={list.loading} className={cn('transition-opacity', list.loading && 'opacity-60')}>
            <p role="status" className="sr-only">
                {list.loading ? loadingText : ''}
            </p>
            {children}
        </div>
    );
}
