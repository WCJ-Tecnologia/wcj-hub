import { Head, Link, router } from '@inertiajs/react';
import { History, X } from 'lucide-react';
import { useState } from 'react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WListBodyMolecule } from '@/components/molecules/w-list-body-molecule/w-list-body-molecule';
import { WPaginationMolecule } from '@/components/molecules/w-pagination-molecule/w-pagination-molecule';
import { WAuditLogDetailDialogOrganism } from '@/components/organisms/w-audit-log-detail-dialog-organism/w-audit-log-detail-dialog-organism';
import {
    AUDIT_URL,
    WAuditLogFiltersOrganism,
    type AuditFilters,
    type AuditOptions,
} from '@/components/organisms/w-audit-log-filters-organism/w-audit-log-filters-organism';
import { WAuditLogTableOrganism, type AuditLogRow } from '@/components/organisms/w-audit-log-table-organism/w-audit-log-table-organism';
import { Button } from '@/components/shadcn/button';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useListVisit } from '@/hooks/use-list-visit';
import type { Paginated } from '@/types';

export type { AuditLogRow };

export interface WAuditLogsPageProps {
    logs: Paginated<AuditLogRow>;
    filters: AuditFilters;
    options: AuditOptions;
    /** "Usuário Maria" quando a tela veio do Histórico de uma ficha (F1 §01). */
    record: string | null;
}

/**
 * Tela Administração › Auditoria (Inertia: `admin/audit-logs/index`; G3; F1 §03, §05): quem
 * alterou o quê, quando e por onde, e toda tentativa negada. Só ver e filtrar: não existe
 * editar, apagar nem exportar.
 */
export function WAuditLogsPage({ logs, filters, options, record }: WAuditLogsPageProps) {
    const list = useListVisit();
    const [selected, setSelected] = useState<AuditLogRow | null>(null);

    const clearFilters = () => router.get(AUDIT_URL, {}, { preserveState: true, replace: true });

    return (
        <WAppLayoutTemplate eyebrow="Administração" title="Auditoria" subtitle="Quem alterou o quê, quando e por onde">
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

            <WAuditLogFiltersOrganism filters={filters} options={options} />

            <WListBodyMolecule list={list} failedTitle="Não foi possível carregar a auditoria" loadingText="Carregando a auditoria…">
                <WAuditLogTableOrganism
                    rows={logs.data}
                    onOpen={setSelected}
                    emptyState={
                        <WEmptyStateMolecule
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
            </WListBodyMolecule>

            <WPaginationMolecule meta={logs} />

            <WAuditLogDetailDialogOrganism log={selected} onClose={() => setSelected(null)} />
        </WAppLayoutTemplate>
    );
}
