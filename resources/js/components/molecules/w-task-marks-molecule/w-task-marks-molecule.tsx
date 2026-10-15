import { AlertTriangle, LifeBuoy, PauseCircle, RotateCcw, Scale, type LucideIcon } from 'lucide-react';

import { Badge } from '@/components/shadcn/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/shadcn/tooltip';
import { hours } from '@/lib/quadro';
import { cn, dateBr } from '@/lib/utils';
import type { BoardCard } from '@/types/quadro';

type MarkVariant = 'warning' | 'info' | 'danger' | 'outline';

interface Mark {
    id: 'pause' | 'rework' | 'support' | 'proposed' | 'late';
    icon: LucideIcon;
    text: string;
    /** O detalhe: no tooltip e, para leitor de tela, num `sr-only` dentro da marca. */
    detail?: string;
    variant: MarkVariant;
}

/** As marcas que o card tem agora, na ordem em que aparecem (F3 §03, P15, P27, P39, P52, P53). */
export function taskMarks(card: BoardCard, inPausedStage: boolean): Mark[] {
    const marks: Mark[] = [];

    if (card.late) {
        marks.push({
            id: 'late',
            icon: AlertTriangle,
            text: 'Atrasado',
            detail: card.due_on
                ? `Passou da data prevista (${dateBr(card.due_on)}) sem chegar ao Done`
                : 'Passou da data prevista sem chegar ao Done',
            variant: 'danger',
        });
    }
    if (inPausedStage) {
        marks.push({ id: 'pause', icon: PauseCircle, text: 'Em pausa', detail: card.pause_reason ?? undefined, variant: 'warning' });
    }
    if (card.rework_count > 0) {
        marks.push({
            id: 'rework',
            icon: RotateCcw,
            text: card.rework_count === 1 ? '1 volta' : `${card.rework_count} voltas`,
            detail: `Retrabalho: voltou ${card.rework_count === 1 ? '1 vez' : `${card.rework_count} vezes`}`,
            variant: 'outline',
        });
    }
    if (card.type?.slug === 'support') {
        marks.push({
            id: 'support',
            icon: LifeBuoy,
            text: 'Suporte',
            detail: card.support_requester ? `Pedido por ${card.support_requester}` : undefined,
            variant: 'info',
        });
    }
    if (card.proposed_hours !== null) {
        marks.push({
            id: 'proposed',
            icon: Scale,
            text: `Proposta: ${hours(card.proposed_hours)}`,
            detail: 'Reestimativa proposta pelo dev, aguardando o gestor',
            variant: 'outline',
        });
    }

    return marks;
}

export interface WTaskMarksMoleculeProps {
    card: BoardCard;
    /** A etapa atual do card é de papel `paused`: só aí a marca de pausa aparece (P15). */
    inPausedStage: boolean;
    className?: string;
}

/**
 * As marcas do card (F3 §03): atraso, pausa com o motivo, retrabalho com o número de voltas,
 * suporte com quem pediu e reestimativa proposta. Cada uma é ícone + texto (nunca só cor), com o
 * detalhe no tooltip. Sem marca, não renderiza nada.
 */
export function WTaskMarksMolecule({ card, inPausedStage, className }: WTaskMarksMoleculeProps) {
    const marks = taskMarks(card, inPausedStage);
    if (marks.length === 0) return null;

    return (
        <ul data-slot="w-task-marks-molecule" aria-label="Marcas do card" className={cn('flex flex-wrap gap-1', className)}>
            {marks.map((mark) => {
                const Icon = mark.icon;
                const badge = (
                    <Badge variant={mark.variant} data-mark={mark.id} className="max-w-full">
                        <Icon aria-hidden />
                        <span className="truncate">{mark.text}</span>
                        {mark.detail && <span className="sr-only">: {mark.detail}</span>}
                    </Badge>
                );
                return (
                    <li key={mark.id} className="flex max-w-full">
                        {mark.detail ? (
                            <Tooltip>
                                <TooltipTrigger asChild>{badge}</TooltipTrigger>
                                <TooltipContent className="max-w-64">{mark.detail}</TooltipContent>
                            </Tooltip>
                        ) : (
                            badge
                        )}
                    </li>
                );
            })}
        </ul>
    );
}
