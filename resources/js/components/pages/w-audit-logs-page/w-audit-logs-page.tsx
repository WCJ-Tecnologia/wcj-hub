import { Head, router } from '@inertiajs/react';
import { History } from 'lucide-react';
import { useState } from 'react';

import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';
import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WPaginationMolecule } from '@/components/molecules/w-pagination-molecule/w-pagination-molecule';
import { WDataTableOrganism, type WColumn } from '@/components/organisms/w-data-table-organism/w-data-table-organism';
import { Badge } from '@/components/shadcn/badge';
import { Button } from '@/components/shadcn/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import type { Paginated } from '@/types';

export interface LogRow {
    id: number;
    user: string;
    user_email: string | null;
    action: string;
    action_label: string;
    /** Alias do morph map (user, client, task…). */
    entity: string;
    /** Nulo quando a negação veio antes de achar o registro. */
    entity_id: number | null;
    outcome: 'success' | 'denied';
    /** {"campo": [antes, depois]} (docs/banco.txt, audit_logs). */
    changes: Record<string, [unknown, unknown]> | null;
    ip_address: string | null;
    created_at: string;
}

export interface WAuditLogsPageProps {
    logs: Paginated<LogRow>;
    filters: Record<string, string | number | null>;
    actionOptions: { value: string; label: string }[];
}

/** O Radix Select não aceita item com valor vazio: "todas" usa este valor e vira filtro vazio no request. */
const ALL = 'all';

const recordLabel = (log: LogRow) => (log.entity_id === null ? log.entity : `${log.entity} #${log.entity_id}`);

/** Um lado de changes: 0 = antes, 1 = depois. */
const side = (log: LogRow, index: 0 | 1) =>
    Object.fromEntries(Object.entries(log.changes ?? {}).map(([field, pair]) => [field, pair[index]]));

/** Tela Conta › Logs de auditoria (Inertia: `admin/audit-logs/index`). */
export function WAuditLogsPage({ logs, filters, actionOptions }: WAuditLogsPageProps) {
    const [selected, setSelected] = useState<LogRow | null>(null);

    const navigate = (params: Record<string, string | number>) => {
        router.get('/admin/audit-logs', { ...filters, ...params }, { preserveState: true, replace: true });
    };

    const columns: WColumn<LogRow>[] = [
        { key: 'created_at', header: 'Data', className: 'whitespace-nowrap text-muted-foreground tabular-nums' },
        { key: 'user', header: 'Usuário', className: 'font-medium' },
        { key: 'action_label', header: 'Ação', cell: (log) => <Badge variant="outline">{log.action_label}</Badge> },
        { key: 'entity', header: 'Registro', className: 'text-muted-foreground', cell: (log) => recordLabel(log) },
        { key: 'ip_address', header: 'IP', className: 'font-mono text-xs text-muted-foreground', cell: (log) => log.ip_address ?? '—' },
        {
            key: 'actions',
            header: <span className="sr-only">Detalhes</span>,
            className: 'text-right',
            cell: (log) => (
                <Button variant="link" size="sm" onClick={() => setSelected(log)} aria-label={`Ver detalhes do registro ${log.id}`}>
                    Ver detalhes
                </Button>
            ),
        },
    ];

    return (
        <WAppLayoutTemplate eyebrow="Conta" title="Logs de auditoria" subtitle="Histórico de todas as ações administrativas">
            <Head title="Logs de auditoria" />

            <div className="mb-4 grid gap-2 sm:grid-cols-3">
                <Select
                    value={filters.action ? String(filters.action) : ALL}
                    onValueChange={(value) => navigate({ action: value === ALL ? '' : value })}
                >
                    <SelectTrigger aria-label="Ação">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todas as ações</SelectItem>
                        {actionOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <WDatePickerMolecule
                    value={filters.from ? String(filters.from) : null}
                    onChange={(value) => navigate({ from: value ?? '' })}
                    label="De"
                    placeholder="De (dd/mm/aaaa)"
                />
                <WDatePickerMolecule
                    value={filters.to ? String(filters.to) : null}
                    onChange={(value) => navigate({ to: value ?? '' })}
                    label="Até"
                    placeholder="Até (dd/mm/aaaa)"
                />
            </div>

            <WDataTableOrganism
                caption="Registros de auditoria"
                columns={columns}
                rows={logs.data}
                rowKey={(log) => log.id}
                emptyState={
                    <WEmptyStateMolecule
                        icon={<History className="size-5" aria-hidden />}
                        title="Nenhum registro de auditoria"
                        description="As ações administrativas aparecem aqui automaticamente."
                    />
                }
                mobileCard={(log) => (
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => setSelected(log)}
                        className="block h-auto w-full p-0 text-left font-normal whitespace-normal hover:bg-transparent"
                    >
                        <span className="flex items-start justify-between gap-2">
                            <span className="font-medium">{log.user}</span>
                            <Badge variant="outline">{log.action_label}</Badge>
                        </span>
                        <span className="mt-1 block text-xs text-muted-foreground">
                            {recordLabel(log)} · {log.created_at}
                        </span>
                    </Button>
                )}
            />

            <WPaginationMolecule meta={logs} />

            <Dialog open={selected !== null} onOpenChange={(next) => !next && setSelected(null)}>
                <DialogContent size="lg">
                    <DialogHeader>
                        <DialogTitle>{selected ? `${selected.action_label} · ${recordLabel(selected)}` : ''}</DialogTitle>
                        <DialogDescription>{selected ? `${selected.user} em ${selected.created_at}` : ''}</DialogDescription>
                    </DialogHeader>
                    <DialogBody>
                        {selected && (
                            <div className="space-y-4">
                                {selected.outcome === 'denied' && <Badge variant="warning">Tentativa negada</Badge>}
                                <div>
                                    <p className="mb-1 font-mono text-xs tracking-wide text-muted-foreground uppercase">Antes</p>
                                    <pre className="scrollbar-slim max-h-56 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs">
                                        {JSON.stringify(side(selected, 0), null, 2)}
                                    </pre>
                                </div>
                                <div>
                                    <p className="mb-1 font-mono text-xs tracking-wide text-muted-foreground uppercase">Depois</p>
                                    <pre className="scrollbar-slim max-h-56 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs">
                                        {JSON.stringify(side(selected, 1), null, 2)}
                                    </pre>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {selected.user_email ? `${selected.user_email} · ` : ''}IP {selected.ip_address ?? 'não registrado'}
                                </p>
                            </div>
                        )}
                    </DialogBody>
                </DialogContent>
            </Dialog>
        </WAppLayoutTemplate>
    );
}
