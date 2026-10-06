import { Head, router } from '@inertiajs/react';
import { History } from 'lucide-react';
import { useState } from 'react';

import { DataTable, type Column } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { Paginated } from '@/types';

interface LogRow {
    id: number;
    user: string;
    user_email: string | null;
    action: string;
    action_label: string;
    entity: string;
    entity_id: number;
    before_data: Record<string, unknown> | null;
    after_data: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
}

interface Props {
    logs: Paginated<LogRow>;
    filters: Record<string, string | number | null>;
    actionOptions: { value: string; label: string }[];
}

export default function AuditLogsIndex({ logs, filters, actionOptions }: Props) {
    const [selected, setSelected] = useState<LogRow | null>(null);

    const navigate = (params: Record<string, string | number>) => {
        router.get('/admin/audit-logs', { ...filters, ...params }, { preserveState: true, replace: true });
    };

    const columns: Column<LogRow>[] = [
        { key: 'created_at', header: 'Data', className: 'whitespace-nowrap text-muted-foreground tabular-nums' },
        { key: 'user', header: 'Usuário', className: 'font-medium' },
        { key: 'action_label', header: 'Ação', cell: (log) => <Badge variant="outline">{log.action_label}</Badge> },
        { key: 'entity', header: 'Registro', className: 'text-muted-foreground', cell: (log) => `${log.entity} #${log.entity_id}` },
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
        <AppLayout eyebrow="Conta" title="Logs de auditoria" subtitle="Histórico de todas as ações administrativas">
            <Head title="Logs de auditoria" />

            <div className="mb-4 grid gap-2 sm:grid-cols-3">
                <Select
                    value={String(filters.action ?? '')}
                    onChange={(event) => navigate({ action: event.target.value })}
                    aria-label="Ação"
                >
                    <option value="">Todas as ações</option>
                    {actionOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </Select>
                <Input
                    type="date"
                    value={String(filters.from ?? '')}
                    onChange={(event) => navigate({ from: event.target.value })}
                    aria-label="De"
                />
                <Input
                    type="date"
                    value={String(filters.to ?? '')}
                    onChange={(event) => navigate({ to: event.target.value })}
                    aria-label="Até"
                />
            </div>

            <DataTable
                caption="Registros de auditoria"
                columns={columns}
                rows={logs.data}
                rowKey={(log) => log.id}
                emptyState={
                    <EmptyState
                        icon={<History className="size-5" aria-hidden />}
                        title="Nenhum registro de auditoria"
                        description="As ações administrativas aparecem aqui automaticamente."
                    />
                }
                mobileCard={(log) => (
                    <button
                        type="button"
                        onClick={() => setSelected(log)}
                        className="block w-full rounded-md text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <div className="flex items-start justify-between gap-2">
                            <p className="font-medium">{log.user}</p>
                            <Badge variant="outline">{log.action_label}</Badge>
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {log.entity} #{log.entity_id} · {log.created_at}
                        </p>
                    </button>
                )}
            />

            <Pagination meta={logs} />

            <Dialog open={selected !== null} onOpenChange={(next) => !next && setSelected(null)}>
                <DialogContent size="lg">
                    <DialogHeader>
                        <DialogTitle>{selected ? `${selected.action_label} · ${selected.entity} #${selected.entity_id}` : ''}</DialogTitle>
                        <DialogDescription>{selected ? `${selected.user} em ${selected.created_at}` : ''}</DialogDescription>
                    </DialogHeader>
                    <DialogBody>
                        {selected && (
                            <div className="space-y-4">
                                <div>
                                    <p className="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Antes</p>
                                    <pre className="scrollbar-slim max-h-56 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs">
                                        {JSON.stringify(selected.before_data ?? {}, null, 2)}
                                    </pre>
                                </div>
                                <div>
                                    <p className="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Depois</p>
                                    <pre className="scrollbar-slim max-h-56 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs">
                                        {JSON.stringify(selected.after_data ?? {}, null, 2)}
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
        </AppLayout>
    );
}
