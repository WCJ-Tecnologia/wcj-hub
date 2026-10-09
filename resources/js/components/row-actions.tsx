import type { LucideIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export interface RowAction {
    /** Texto visível no celular e, por padrão, aria-label no desktop. */
    label: string;
    /** aria-label específico com o nome do registro ("Editar Loja 3"). */
    ariaLabel?: string;
    icon: LucideIcon;
    onClick: () => void;
    destructive?: boolean;
    /** Ação que a pessoa não pode executar simplesmente não aparece. */
    hidden?: boolean;
}

/**
 * Ações de uma linha de listagem. `labeled=false` (desktop): botões de ícone
 * com aria-label. `labeled=true` (card mobile): botões outline com texto.
 * `busy`: uma ação desta linha está rodando; os botões travam e a linha sinaliza.
 * `disabled`: a ação de outra linha está rodando; os botões só travam, sem esmaecer (CR-2-07).
 * Os dois vêm do `useRowAction` (`isBusy`, `isLocked`).
 */
export function RowActions({
    actions,
    labeled = false,
    busy = false,
    disabled = false,
    className,
}: {
    actions: RowAction[];
    labeled?: boolean;
    busy?: boolean;
    disabled?: boolean;
    className?: string;
}) {
    const visible = actions.filter((action) => !action.hidden);
    if (visible.length === 0) return null;

    return (
        <div
            aria-busy={busy || undefined}
            className={cn(labeled ? 'mt-3 flex flex-wrap gap-2' : 'flex justify-end gap-1', busy && 'opacity-60', className)}
        >
            {visible.map((action) => {
                const Icon = action.icon;
                return (
                    <Button
                        key={action.label}
                        variant={labeled ? 'outline' : 'ghost'}
                        size={labeled ? 'sm' : 'icon'}
                        onClick={action.onClick}
                        disabled={busy || disabled}
                        aria-label={action.ariaLabel ?? action.label}
                    >
                        <Icon className={cn('size-4', action.destructive && 'text-destructive')} aria-hidden />
                        {labeled && action.label}
                    </Button>
                );
            })}
        </div>
    );
}
