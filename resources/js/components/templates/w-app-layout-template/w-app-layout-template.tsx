import { router, usePage } from '@inertiajs/react';
import { useEffect, type ReactNode } from 'react';
import { toast } from 'sonner';

import { WPageContainerAtom, type WPageWidth } from '@/components/atoms/w-page-container-atom/w-page-container-atom';
import { WPageHeaderMolecule } from '@/components/molecules/w-page-header-molecule/w-page-header-molecule';
import { WAppSidebarOrganism } from '@/components/organisms/w-app-sidebar-organism/w-app-sidebar-organism';
import { WAppTopbarOrganism } from '@/components/organisms/w-app-topbar-organism/w-app-topbar-organism';
import { SidebarInset, SidebarProvider } from '@/components/shadcn/sidebar';
import { Toaster } from '@/components/shadcn/sonner';
import { TooltipProvider } from '@/components/shadcn/tooltip';
import type { FlashMessages, SharedProps } from '@/types';

export interface WAppLayoutTemplateProps {
    /** O h1 da página. */
    title: string;
    subtitle?: string;
    /** Grupo ou contexto acima do título (ex.: "Cadastros"). */
    eyebrow?: string;
    /** Botões à direita do título. */
    actions?: ReactNode;
    /** Largura nomeada da página; ver WPageContainerAtom. */
    width?: WPageWidth;
    children: ReactNode;
}

/** Server flash → Sonner. The id dedupes the same flash seen on mount and on Inertia's success event. */
function showFlash(flash: FlashMessages | undefined) {
    if (flash?.success) toast.success(flash.success, { id: `flash-success-${flash.success}` });
    if (flash?.error) toast.error(flash.error, { id: `flash-error-${flash.error}` });
}

/** The shadcn Sidebar stores its desktop state in this cookie; reopen as the person left it. */
function sidebarWasOpen(): boolean {
    if (typeof document === 'undefined') return true;
    return !document.cookie.split('; ').includes('sidebar_state=false');
}

/**
 * Shell do backoffice: menu lateral (Sidebar do shadcn), topbar, cabeçalho da
 * página, conteúdo e os avisos do servidor (flash) em toast (Sonner).
 */
export function WAppLayoutTemplate({ title, subtitle, eyebrow, actions, width, children }: WAppLayoutTemplateProps) {
    const flash = usePage<SharedProps>().props.flash;

    // First render reads the flash that came with the page; later visits that
    // keep this layout mounted are picked up from Inertia's own event.
    useEffect(() => {
        showFlash(flash);
        return router.on('success', (event) => showFlash((event.detail.page.props as unknown as SharedProps).flash));
        // eslint-disable-next-line react-hooks/exhaustive-deps -- only the flash present at mount
    }, []);

    return (
        <TooltipProvider>
            <SidebarProvider defaultOpen={sidebarWasOpen()}>
                <a
                    href="#conteudo"
                    className="sr-only z-[70] rounded-md bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
                >
                    Ir para o conteúdo
                </a>

                <WAppSidebarOrganism />

                <SidebarInset className="min-w-0">
                    <WAppTopbarOrganism />
                    <main id="conteudo" tabIndex={-1} className="outline-none">
                        <WPageContainerAtom width={width}>
                            <WPageHeaderMolecule eyebrow={eyebrow} title={title} subtitle={subtitle} actions={actions} />
                            {children}
                        </WPageContainerAtom>
                    </main>
                </SidebarInset>
            </SidebarProvider>
            <Toaster />
        </TooltipProvider>
    );
}
