import { router, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { CheckCircle2, X, XCircle } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';

import { AppSidebar } from '@/components/app-sidebar';
import { AppTopbar } from '@/components/app-topbar';
import { PageContainer, type PageWidth } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { TooltipProvider } from '@/components/ui/tooltip';
import type { FlashMessages, SharedProps } from '@/types';

interface Props {
    title: string;
    subtitle?: string;
    /** Grupo ou contexto acima do título (ex.: "Cadastros"). */
    eyebrow?: string;
    actions?: ReactNode;
    /** Largura nomeada da página; ver PageContainer. */
    width?: PageWidth;
    children: ReactNode;
}

type Toast = { type: 'success' | 'error'; msg: string } | null;

function toastFrom(flash: FlashMessages | undefined): Toast {
    if (flash?.success) return { type: 'success', msg: flash.success };
    if (flash?.error) return { type: 'error', msg: flash.error };
    return null;
}

export default function AppLayout({ title, subtitle, eyebrow, actions, width, children }: Props) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const flash = usePage<SharedProps>().props.flash;

    // First render reads the flash that came with the page; later visits are
    // picked up from Inertia's own event instead of a setState inside an effect.
    const [toast, setToast] = useState<Toast>(() => toastFrom(flash));

    useEffect(() => {
        return router.on('success', (event) => {
            const next = toastFrom((event.detail.page.props as unknown as SharedProps).flash);
            if (next) setToast(next);
        });
    }, []);

    useEffect(() => {
        if (!toast) return;
        const timer = setTimeout(() => setToast(null), 4500);
        return () => clearTimeout(timer);
    }, [toast]);

    return (
        <TooltipProvider>
            <div className="min-h-screen bg-background">
                <a
                    href="#conteudo"
                    className="sr-only z-[70] rounded-md bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
                >
                    Ir para o conteúdo
                </a>

                <div className="fixed inset-y-0 left-0 z-40 hidden lg:block">
                    <AppSidebar />
                </div>

                <AnimatePresence>
                    {mobileOpen && (
                        <>
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                onClick={() => setMobileOpen(false)}
                                className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
                            />
                            <motion.div
                                initial={{ x: '-100%' }}
                                animate={{ x: 0 }}
                                exit={{ x: '-100%' }}
                                transition={{ type: 'spring', damping: 30, stiffness: 300 }}
                                className="fixed inset-y-0 left-0 z-50 lg:hidden"
                            >
                                <AppSidebar onNavigate={() => setMobileOpen(false)} />
                            </motion.div>
                        </>
                    )}
                </AnimatePresence>

                <div className="lg:pl-[17rem]">
                    <AppTopbar onMenu={() => setMobileOpen(true)} />
                    <main id="conteudo" tabIndex={-1} className="outline-none">
                        <PageContainer width={width}>
                            <PageHeader eyebrow={eyebrow} title={title} subtitle={subtitle} actions={actions} />
                            {children}
                        </PageContainer>
                    </main>
                </div>

                {/* Server flash → toast. role=status + aria-live so screen readers announce it. */}
                <div role="status" aria-live="polite">
                    <AnimatePresence>
                        {toast && (
                            <motion.div
                                initial={{ opacity: 0, y: 24, scale: 0.96 }}
                                animate={{ opacity: 1, y: 0, scale: 1 }}
                                exit={{ opacity: 0, y: 24, scale: 0.96 }}
                                className="fixed right-4 bottom-6 z-[60] flex max-w-[calc(100vw-2rem)] items-center gap-3 rounded-xl border border-border bg-card px-4 py-3 shadow-lg sm:right-6"
                            >
                                {toast.type === 'success' ? (
                                    <CheckCircle2 className="size-5 shrink-0 text-status-approved" aria-hidden />
                                ) : (
                                    <XCircle className="size-5 shrink-0 text-destructive" aria-hidden />
                                )}
                                <span className="text-sm font-medium">{toast.msg}</span>
                                <button
                                    onClick={() => setToast(null)}
                                    className="ml-1 rounded-md text-muted-foreground outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    aria-label="Fechar aviso"
                                >
                                    <X className="size-4" aria-hidden />
                                </button>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>
            </div>
        </TooltipProvider>
    );
}
