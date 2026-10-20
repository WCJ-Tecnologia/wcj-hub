import { ArrowRight, Bot, PauseCircle, RotateCcw, Sparkles } from 'lucide-react';

import { Badge } from '@/components/shadcn/badge';
import { businessDays, movementText, stageName } from '@/lib/indicadores';
import type { HistoryMovement } from '@/types/indicadores';

export interface WTaskTimelineOrganismProps {
    movements: HistoryMovement[];
}

/**
 * A linha do tempo do card (F7 §02, §03; P14, P15, P39, R14): um movimento por linha, do mais
 * antigo para o mais recente: data e hora, quem (com "(desativado)"), o canal (Tela ou a IA), de
 * onde para onde (etapa removida com a marca), quanto tempo o card ficou na etapa de destino
 * ("até agora" na última), a origem e o motivo do retrabalho e o motivo da pausa. A primeira
 * linha é sempre a criação; só com ela: "Nenhum movimento além da criação".
 */
export function WTaskTimelineOrganism({ movements }: WTaskTimelineOrganismProps) {
    return (
        <section data-slot="w-task-timeline-organism" aria-label="Linha do tempo" className="space-y-3">
            <h2 className="font-heading text-lg font-semibold">Linha do tempo</h2>
            <ol className="relative space-y-0 border-l border-border pl-5">
                {movements.map((movement) => (
                    <li key={movement.id} className="relative pb-5 last:pb-0">
                        <span
                            aria-hidden
                            className={`absolute top-1.5 -left-[25px] size-2.5 rounded-full border-2 border-background ${movement.open ? 'bg-primary' : 'bg-muted-foreground/60'}`}
                        />
                        <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <time className="font-mono text-xs text-muted-foreground tabular-nums">{movement.at}</time>
                            <span className="text-sm">
                                {movement.by.name}
                                {movement.by.deactivated && <span className="text-muted-foreground"> (desativado)</span>}
                            </span>
                            <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                {movement.channel === 'Tela' ? <Sparkles className="size-3" aria-hidden /> : <Bot className="size-3" aria-hidden />}
                                {movement.channel}
                            </span>
                        </div>
                        <p className="mt-0.5 flex flex-wrap items-center gap-x-2 font-medium">
                            {movement.from ? (
                                <>
                                    <span>{stageName(movement.from)}</span>
                                    <ArrowRight className="size-4 text-muted-foreground" aria-hidden />
                                    <span>{stageName(movement.to)}</span>
                                    <span className="sr-only">{movementText(movement)}</span>
                                </>
                            ) : (
                                <span>Criou o card em {stageName(movement.to)}</span>
                            )}
                            {movement.rework && (
                                <Badge variant="warning">
                                    <RotateCcw className="size-3" aria-hidden /> Retrabalho
                                </Badge>
                            )}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {movement.days === null ? '—' : businessDays(movement.days)} em {stageName(movement.to)}
                            {movement.open ? ', até agora' : ''}
                        </p>
                        {movement.rework && (
                            <p className="mt-1 text-sm">
                                <span className="text-muted-foreground">Origem:</span> {movement.rework.origin ?? '—'} ·{' '}
                                <span className="text-muted-foreground">Motivo:</span> {movement.rework.reason ?? '—'}
                                {movement.rework.text && <span> — {movement.rework.text}</span>}
                            </p>
                        )}
                        {movement.pause_reason && (
                            <p className="mt-1 inline-flex items-center gap-1 text-sm">
                                <PauseCircle className="size-3.5 text-muted-foreground" aria-hidden />
                                <span className="text-muted-foreground">Motivo da pausa:</span> {movement.pause_reason}
                            </p>
                        )}
                    </li>
                ))}
            </ol>
            {movements.length <= 1 && <p className="text-sm text-muted-foreground">Nenhum movimento além da criação.</p>}
        </section>
    );
}
