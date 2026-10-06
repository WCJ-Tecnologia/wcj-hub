import * as React from 'react';

import { cn } from '@/lib/utils';

/** Placeholder de carregamento. Dê a ele o mesmo tamanho do conteúdo que substitui. */
function Skeleton({ className, ...props }: React.ComponentProps<'div'>) {
    return <div data-slot="skeleton" aria-hidden className={cn('animate-pulse rounded-md bg-muted', className)} {...props} />;
}

export { Skeleton };
