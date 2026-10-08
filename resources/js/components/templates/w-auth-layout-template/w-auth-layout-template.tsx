import { Head, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { WBrandLogoAtom } from '@/components/atoms/w-brand-logo-atom/w-brand-logo-atom';
import type { SharedProps } from '@/types';

export interface WAuthLayoutTemplateProps {
    /** Título da aba do navegador. O h1 fica no conteúdo da página. */
    title: string;
    children: ReactNode;
}

/**
 * Tela dividida das páginas de autenticação: painel da marca à esquerda
 * (desktop) e o formulário à direita.
 */
export function WAuthLayoutTemplate({ title, children }: WAuthLayoutTemplateProps) {
    const { name, brand } = usePage<SharedProps>().props.app;

    return (
        <>
            <Head title={title} />
            <div data-slot="w-auth-layout-template" className="grid min-h-screen lg:grid-cols-2">
                <div className="relative hidden overflow-hidden bg-sidebar lg:block">
                    <div className="absolute top-10 -left-24 size-96 rounded-full bg-primary/25 blur-3xl" aria-hidden />
                    <div className="absolute right-0 -bottom-24 size-96 rounded-full bg-primary/15 blur-3xl" aria-hidden />

                    <div className="relative z-10 flex h-full flex-col justify-between p-12 text-white">
                        <WBrandLogoAtom size="lg" href={false} />

                        <div className="max-w-md">
                            <h2 className="font-heading text-4xl leading-tight font-extrabold text-white">{brand.name}</h2>
                            <p className="mt-4 text-sm leading-relaxed text-sidebar-foreground/85">
                                Acesso restrito a pessoas autorizadas. Entre com o e-mail da sua conta.
                            </p>
                        </div>

                        <p className="text-xs text-sidebar-foreground/75">
                            © {new Date().getFullYear()} {brand.name} · {name}
                        </p>
                    </div>
                </div>

                <div className="relative flex items-center justify-center px-6 py-12">
                    <div className="absolute top-5 right-5 lg:hidden">
                        <WBrandLogoAtom showText={false} href={false} />
                    </div>

                    <main id="conteudo" className="w-full max-w-sm">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
}
