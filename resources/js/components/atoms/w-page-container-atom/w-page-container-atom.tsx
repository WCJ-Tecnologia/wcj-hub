import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export type WPageWidth = 'narrow' | 'default' | 'wide' | 'full';

/**
 * Largura de página é decisão de sistema, não escolha por tela. `narrow` para
 * formulário e perfil, `default` para listagem, `wide` para painel denso, `full` para a tela que rola na
 * horizontal (o quadro kanban): sem largura máxima, o corte das colunas acontece na borda da janela.
 */
const WIDTHS: Record<WPageWidth, string> = {
    narrow: 'max-w-3xl',
    default: 'max-w-7xl',
    wide: 'max-w-[100rem]',
    full: 'max-w-none',
};

export interface WPageContainerAtomProps {
    width?: WPageWidth;
    className?: string;
    children: ReactNode;
}

export function WPageContainerAtom({ width = 'default', className, children }: WPageContainerAtomProps) {
    return (
        <div data-slot="w-page-container-atom" className={cn('mx-auto w-full px-4 py-5 lg:px-8 lg:py-7', WIDTHS[width], className)}>
            {children}
        </div>
    );
}
