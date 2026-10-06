import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, History, X } from 'lucide-react';
import { useState } from 'react';

import { AuditLogDetailDialog } from '@/components/audit-logs/audit-log-detail-dialog';
import { AUDIT_URL, AuditLogFilters, type AuditFilters, type AuditOptions } from '@/components/audit-logs/audit-log-filters';
import { AuditLogTable, type AuditLogRow } from '@/components/audit-logs/audit-log-table';
import { EmptyState } from '@/components/empty-state';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { useListVisit } from '@/hooks/use-list-visit';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

interface Props {
    logs: Paginated<AuditLogRow>;
    filters: AuditFilters;
    options: AuditOptions;
    /** "Usuário Maria" quando a tela veio do Histórico de uma ficha (F1 §01). */
    record: string | null;
}

/**
 * Administração → Auditoria (G3; F1 §03, §05): quem alterou o quê, quando e por onde, e toda
 * tentativa negada. Só ver e filtrar: não existe editar, apagar nem exportar.
 */
export default function AuditLogsIndex({ logs, filters, options, record }: Props) {
    const list = useListVisit();
    const [selected, setSelected] = useState<AuditLogRow | null>(null);

    const clearFilters = () => router.get(AUDIT_URL, {}, { preserveState: true, replace: true });

    return (
        <AppLayout eyebrow="Administração" title="Auditoria" subtitle="Quem alterou o quê, quando e por onde">
            <Head title="Auditoria" />

            {record && (
                <div className="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-muted px-4 py-3 text-sm">
                    <p className="flex items-center gap-2">
                        <History className="size-4 shrink-0 text-muted-foreground" aria-hidden />
                        <span>
                            Histórico de <strong className="font-semibold">{record}</strong>
                            {filters.from === null && ', desde o início'}
                        </span>
                    </p>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={AUDIT_URL}>
                            <X className="size-4" aria-hidden /> Ver toda a auditoria
                        </Link>
                    </Button>
                </div>
            )}

            <AuditLogFilters filters={filters} options={options} />

            {list.failed ? (
                <EmptyState
                    icon={<AlertTriangle className="size-5" aria-hidden />}
                    title="Não foi possível carregar a auditoria"
                    description="Confira a conexão e tente de novo."
                    action={<Button onClick={list.retry}>Tentar de novo</Button>}
                />
            ) : (
                <div aria-busy={list.loading} className={cn('transition-opacity', list.loading && 'opacity-60')}>
                    <p role="status" className="sr-only">
                        {list.loading ? 'Carregando a auditoria…' : ''}
                    </p>
                    <AuditLogTable
                        rows={logs.data}
                        onOpen={setSelected}
                        emptyState={
                            <EmptyState
                                icon={<History className="size-5" aria-hidden />}
                                title="Nenhum registro com esses filtros"
                                description="Amplie o período ou limpe os filtros."
                                action={
                                    <Button variant="outline" onClick={clearFilters}>
                                        Limpar filtros
                                    </Button>
                                }
                            />
                        }
                    />
                </div>
            )}

            <Pagination meta={logs} />

            <AuditLogDetailDialog log={selected} onClose={() => setSelected(null)} />
        </AppLayout>
    );
}
