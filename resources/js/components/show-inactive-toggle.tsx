import { useId } from 'react';

import { Checkbox } from '@/components/ui/checkbox';

/**
 * "Mostrar desativados" das listas. Só a caixa liga e desliga: o texto não é <label>, para um
 * clique perto do filtro não trocar a lista sem querer (pedido do Cauã, 07/10). O texto continua
 * sendo o nome acessível da caixa, por aria-labelledby.
 */
export function ShowInactiveToggle({ checked, onChange }: { checked: boolean; onChange: (checked: boolean) => void }) {
    const labelId = useId();

    return (
        <div className="flex items-center gap-2 sm:px-2">
            <Checkbox aria-labelledby={labelId} checked={checked} onCheckedChange={(value) => onChange(value === true)} />
            <span id={labelId} className="text-sm font-medium select-none">
                Mostrar desativados
            </span>
        </div>
    );
}
