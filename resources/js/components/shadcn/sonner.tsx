import { CircleCheckIcon, InfoIcon, Loader2Icon, OctagonXIcon, TriangleAlertIcon } from 'lucide-react';
import type * as React from 'react';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

/**
 * Toaster do shadcn sem o next-themes: as cores vêm das variáveis do tema
 * (--popover, --border), que já trocam com a classe .dark do <html>.
 * Dispare com `toast.success()` / `toast.error()` do pacote `sonner`.
 */
function Toaster({ ...props }: ToasterProps) {
    return (
        <Sonner
            className="toaster group"
            // No topo, centralizado (pedido do Cauã, 07/10): embaixo à direita ninguém via.
            position="top-center"
            duration={4500}
            closeButton
            containerAriaLabel="Avisos"
            icons={{
                success: <CircleCheckIcon className="size-4 text-status-approved" />,
                info: <InfoIcon className="size-4 text-status-neutral" />,
                warning: <TriangleAlertIcon className="size-4 text-status-pending" />,
                error: <OctagonXIcon className="size-4 text-destructive" />,
                loading: <Loader2Icon className="size-4 animate-spin" />,
            }}
            toastOptions={{ closeButtonAriaLabel: 'Fechar aviso' }}
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                    '--border-radius': 'var(--radius)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
