import { Loader2 } from 'lucide-react';
import type { ReactNode } from 'react';

import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';

export interface WConfirmDialogMoleculeProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    /** Diga o que será afetado, com o nome do registro. */
    description?: ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    /** Botão de confirmar vermelho (excluir, desativar). */
    destructive?: boolean;
    /** Desabilita os botões, mostra o spinner e impede fechar. */
    loading?: boolean;
    onConfirm: () => void;
    /** Conteúdo extra abaixo da descrição (ex.: aviso, lista do que será afetado). */
    children?: ReactNode;
}

/**
 * Confirmação de ação destrutiva ou irreversível. Nunca use o confirm nativo do navegador.
 * Coloque o nome do registro em `description` para a pessoa saber o que está
 * confirmando.
 */
export function WConfirmDialogMolecule({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel = 'Confirmar',
    cancelLabel = 'Cancelar',
    destructive = false,
    loading = false,
    onConfirm,
    children,
}: WConfirmDialogMoleculeProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !loading && onOpenChange(next)}>
            {/* Sem descrição, tira o aria-describedby para o Radix não apontar para um id inexistente. */}
            <DialogContent data-slot="w-confirm-dialog-molecule" {...(description ? {} : { 'aria-describedby': undefined })}>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && <DialogDescription className="text-sm text-muted-foreground">{description}</DialogDescription>}
                </DialogHeader>
                {children && <DialogBody>{children}</DialogBody>}
                <DialogFooter className={children ? undefined : 'border-t-0 pt-0'}>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
                        {cancelLabel}
                    </Button>
                    <Button variant={destructive ? 'destructive' : 'default'} onClick={onConfirm} disabled={loading}>
                        {loading && <Loader2 className="size-4 animate-spin" aria-hidden />}
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
