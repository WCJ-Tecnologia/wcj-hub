import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export interface WAuthBackLinkAtomProps {
    href: string;
    children: ReactNode;
}

/** Link centralizado no rodapé das telas de autenticação ("Voltar para o login"). */
export function WAuthBackLinkAtom({ href, children }: WAuthBackLinkAtomProps) {
    return (
        <p data-slot="w-auth-back-link-atom" className="mt-6 text-center text-sm text-muted-foreground">
            <Link href={href} className="font-medium text-primary hover:underline">
                {children}
            </Link>
        </p>
    );
}
