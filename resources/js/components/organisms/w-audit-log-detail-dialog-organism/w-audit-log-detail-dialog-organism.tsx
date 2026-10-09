import { ShieldX } from 'lucide-react';

import type { AuditLogRow } from '@/components/organisms/w-audit-log-table-organism/w-audit-log-table-organism';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/shadcn/dialog';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/shadcn/table';

const EMPTY = '—';

export interface WAuditLogDetailDialogOrganismProps {
    /** A linha aberta; nulo fecha o modal. */
    log: AuditLogRow | null;
    onClose: () => void;
}

/** O detalhe da linha (Auditoria AC 4): cada campo alterado, com o antes e o depois. */
export function WAuditLogDetailDialogOrganism({ log, onClose }: WAuditLogDetailDialogOrganismProps) {
    return (
        <Dialog open={log !== null} onOpenChange={(next) => !next && onClose()}>
            <DialogContent data-slot="w-audit-log-detail-dialog-organism" size="lg">
                <DialogHeader>
                    <DialogTitle>{log ? `${log.action_label} · ${log.record}` : ''}</DialogTitle>
                    <DialogDescription>{log ? `${log.user} em ${log.created_at} · Canal: ${log.channel}` : ''}</DialogDescription>
                </DialogHeader>
                <DialogBody>
                    {log && (
                        <div className="space-y-4">
                            {log.outcome === 'denied' && (
                                <p className="flex items-start gap-2 rounded-lg bg-status-rejected-bg p-3 text-sm text-status-rejected">
                                    <ShieldX className="mt-0.5 size-4 shrink-0" aria-hidden />
                                    <span>
                                        <strong className="font-semibold">Tentativa negada.</strong> A pessoa não tinha permissão; nada foi
                                        alterado.
                                    </span>
                                </p>
                            )}

                            {log.changes.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Nenhum campo alterado nesta linha.</p>
                            ) : (
                                <div className="scrollbar-slim overflow-x-auto rounded-lg border border-border">
                                    <Table>
                                        <TableCaption className="sr-only">Campos alterados, com o valor anterior e o novo</TableCaption>
                                        <TableHeader className="bg-muted/40">
                                            <TableRow className="hover:bg-transparent">
                                                <TableHead scope="col" className="px-3 font-mono text-xs tracking-wide text-muted-foreground uppercase">
                                                    Campo
                                                </TableHead>
                                                <TableHead scope="col" className="px-3 font-mono text-xs tracking-wide text-muted-foreground uppercase">
                                                    Antes
                                                </TableHead>
                                                <TableHead scope="col" className="px-3 font-mono text-xs tracking-wide text-muted-foreground uppercase">
                                                    Depois
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {log.changes.map((change) => (
                                                <TableRow key={change.field}>
                                                    <TableHead scope="row" className="h-auto px-3 py-2 text-left font-medium whitespace-normal">
                                                        {change.label}
                                                    </TableHead>
                                                    <TableCell className="px-3 py-2 break-words whitespace-normal text-muted-foreground">
                                                        {change.before ?? EMPTY}
                                                    </TableCell>
                                                    <TableCell className="px-3 py-2 break-words whitespace-normal">{change.after ?? EMPTY}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}

                            <p className="text-xs text-muted-foreground">
                                {log.user_email ? `${log.user_email} · ` : ''}IP {log.ip_address ?? 'não registrado'}
                            </p>
                        </div>
                    )}
                </DialogBody>
            </DialogContent>
        </Dialog>
    );
}
