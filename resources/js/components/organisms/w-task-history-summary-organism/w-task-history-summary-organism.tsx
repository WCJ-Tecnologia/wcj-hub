import type { ReactNode } from 'react';

import { WTaskKeyAtom } from '@/components/atoms/w-task-key-atom/w-task-key-atom';
import { businessDays, delayText, DEVELOPMENT_TIME_NOTE, missingText } from '@/lib/indicadores';
import { hours } from '@/lib/quadro';
import { dateBr } from '@/lib/utils';
import type { HistorySummary } from '@/types/indicadores';

export interface WTaskHistorySummaryOrganismProps {
    summary: HistorySummary;
}

function Item({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="rounded-md border border-border px-3 py-2">
            <dt className="font-mono text-xs text-muted-foreground uppercase">{label}</dt>
            <dd className="text-sm">{children}</dd>
        </div>
    );
}

/**
 * O resumo no topo da linha do tempo (F7 §02): etapa atual e desde quando, data prevista (ou o
 * que falta), atraso descontada a pausa (não aparece em card pronto nem arquivado), concluída em,
 * horas previstas e lançadas, tempo em desenvolvimento estimado pelo quadro (R17), tempo por papel
 * de etapa (P11), bug de produção de / ligados (P25) e arquivado (P26).
 */
export function WTaskHistorySummaryOrganism({ summary }: WTaskHistorySummaryOrganismProps) {
    const delay = delayText(summary.delay_days);

    return (
        <section data-slot="w-task-history-summary-organism" aria-label="Resumo" className="space-y-4">
            <dl className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <Item label="Etapa atual">
                    <span className="font-medium">{summary.stage.name}</span>
                    {summary.stage.since && <span className="text-muted-foreground"> · desde {summary.stage.since}</span>}
                </Item>
                <Item label="Data prevista">
                    {summary.due_on ? dateBr(summary.due_on) : <span className="text-muted-foreground">{missingText(summary.missing)}</span>}
                </Item>
                {summary.done_on ? (
                    <Item label="Concluída em">{dateBr(summary.done_on)}</Item>
                ) : (
                    delay !== null && (
                        <Item label="Atraso">
                            <span className={summary.delay_days && summary.delay_days > 0 ? 'font-medium text-destructive' : ''}>{delay}</span>
                        </Item>
                    )
                )}
                <Item label="Horas previstas">{summary.estimated_hours === null ? <span className="text-muted-foreground">—</span> : hours(summary.estimated_hours)}</Item>
                <Item label="Horas lançadas">{hours(summary.logged_hours)}</Item>
                <Item label="Tempo em desenvolvimento">
                    {summary.development_hours === null ? (
                        <span className="text-muted-foreground">—</span>
                    ) : (
                        <>
                            {hours(summary.development_hours)} <span className="text-muted-foreground">({businessDays(summary.development_days)})</span>
                        </>
                    )}
                    <span className="block text-xs text-muted-foreground">{DEVELOPMENT_TIME_NOTE}</span>
                </Item>
                {summary.origin && (
                    <Item label="Bug de produção de">
                        <WTaskKeyAtom taskKey={summary.origin.key} href={summary.origin.url} title={summary.origin.title} /> {summary.origin.title}
                    </Item>
                )}
                {summary.production_bugs.length > 0 && (
                    <Item label="Bugs de produção ligados">
                        <span className="flex flex-wrap gap-2">
                            {summary.production_bugs.map((bug) => (
                                <WTaskKeyAtom key={bug.id} taskKey={bug.key} href={bug.url} title={bug.title} />
                            ))}
                        </span>
                    </Item>
                )}
                {summary.archived && (
                    <Item label="Arquivado">
                        {summary.archived.on ? `em ${dateBr(summary.archived.on)}` : ''}
                        {summary.archived.by ? ` por ${summary.archived.by}` : ''}
                        {summary.archived.reason && <span className="block text-muted-foreground">{summary.archived.reason}</span>}
                    </Item>
                )}
            </dl>

            <div>
                <h3 className="mb-2 font-mono text-xs text-muted-foreground uppercase">Tempo por etapa</h3>
                {summary.by_role.length === 0 ? (
                    <p className="text-sm text-muted-foreground">O card ainda não se moveu.</p>
                ) : (
                    <ul className="flex flex-wrap gap-2">
                        {summary.by_role.map((row) => (
                            <li key={row.role} className="rounded-md border border-border px-3 py-1.5 text-sm">
                                <span className="font-medium">{row.label}</span> <span className="text-muted-foreground">· {businessDays(row.days)}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </section>
    );
}
