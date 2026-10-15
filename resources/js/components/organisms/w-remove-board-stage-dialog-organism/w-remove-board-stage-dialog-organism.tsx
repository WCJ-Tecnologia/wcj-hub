import { useState } from 'react';

import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WFormFieldMolecule } from '@/components/molecules/w-form-field-molecule/w-form-field-molecule';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import type { StageRow } from '@/types/configuracao';

export interface WRemoveBoardStageDialogOrganismProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** A etapa que sai; null fecha. */
    stage: StageRow | null;
    /** As outras etapas ativas do projeto, na ordem do quadro: o destino dos cards (P12). */
    destinations: StageRow[];
    loading?: boolean;
    /** Erro do servidor em `destination_id`. */
    error?: string;
    onConfirm: (destinationId: number | null) => void;
}

/**
 * Remover etapa (F2 §04; P12): sem cards, só confirma; com cards, pede para onde eles vão, e o
 * destino é obrigatório. A etapa continua nos movimentos antigos; os cards vão com o movimento
 * gravado e, se o destino for a Fila, saem da sprint (P45).
 */
export function WRemoveBoardStageDialogOrganism({
    open,
    onOpenChange,
    stage,
    destinations,
    loading = false,
    error,
    onConfirm,
}: WRemoveBoardStageDialogOrganismProps) {
    const [destination, setDestination] = useState<number | ''>('');
    const [missing, setMissing] = useState(false);

    // Abrir (ou trocar de etapa) zera o destino: ajuste no render, sem efeito.
    const openKey = open ? (stage?.id ?? 0) : null;
    const [lastKey, setLastKey] = useState(openKey);
    if (openKey !== lastKey) {
        setLastKey(openKey);
        setDestination('');
        setMissing(false);
    }

    const withCards = (stage?.cards ?? 0) > 0;
    const confirm = () => {
        if (withCards && destination === '') {
            setMissing(true);
            return;
        }
        onConfirm(withCards ? Number(destination) : null);
    };
    const fieldError = error ?? (missing ? 'Escolha para onde os cards vão.' : undefined);

    return (
        <WConfirmDialogMolecule
            open={open && stage !== null}
            onOpenChange={onOpenChange}
            title={withCards ? `${stage?.cards} cards estão em ${stage?.name}. Para onde vão?` : `Remover a etapa ${stage?.name ?? ''}?`}
            description={
                withCards
                    ? 'Cada card vai com o movimento gravado (quem, quando, de onde e para onde), sem contar como retrabalho. Se o destino for a Fila, os cards também saem da sprint. Cards arquivados não se mexem.'
                    : 'A etapa sai do quadro, mas continua no histórico e nos indicadores do que já passou por ela. Não dá para restaurar: se precisar, crie outra com o mesmo papel.'
            }
            confirmLabel="Remover etapa"
            destructive
            loading={loading}
            onConfirm={confirm}
        >
            {withCards && (
                <WFormFieldMolecule label="Mover as tarefas para" htmlFor="stage-destination" required error={fieldError}>
                    <Select
                        value={destination === '' ? '' : String(destination)}
                        onValueChange={(value) => {
                            setDestination(value === '' ? '' : Number(value));
                            setMissing(false);
                        }}
                    >
                        <SelectTrigger
                            id="stage-destination"
                            aria-invalid={Boolean(fieldError)}
                            aria-describedby={fieldError ? 'stage-destination-error' : undefined}
                        >
                            <SelectValue placeholder="Escolha a etapa" />
                        </SelectTrigger>
                        <SelectContent>
                            {destinations.map((option) => (
                                <SelectItem key={option.id} value={String(option.id)}>
                                    {option.name} · {option.role_label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </WFormFieldMolecule>
            )}
        </WConfirmDialogMolecule>
    );
}
