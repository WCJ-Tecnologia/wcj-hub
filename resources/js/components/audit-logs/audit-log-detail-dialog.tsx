import { ShieldX } from 'lucide-react';

import type { AuditLogRow } from '@/components/audit-logs/audit-log-table';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const EMPTY = '—';

/** O detalhe da linha (Auditoria AC 4): cada campo alterado, com o antes e o depois. */
export function AuditLogDetailDialog({ log, onClose }: { log: AuditLogRow | null; onClose: () => void }) {
    return (
        <Dialog open={log !== null} onOpenChange={(next) => !next && onClose()}>
            <DialogContent size="lg">
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
                                    <table className="w-full text-sm">
                                        <caption className="sr-only">Campos alterados, com o valor anterior e o novo</caption>
                                        <thead className="bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <tr>
                                                <th scope="col" className="px-3 py-2 font-semibold">
                                                    Campo
                                                </th>
                                                <th scope="col" className="px-3 py-2 font-semibold">
                                                    Antes
                                                </th>
                                                <th scope="col" className="px-3 py-2 font-semibold">
                                                    Depois
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {log.changes.map((change) => (
                                                <tr key={change.field} className="border-t border-border">
                                                    <th scope="row" className="px-3 py-2 text-left font-medium">
                                                        {change.label}
                                                    </th>
                                                    <td className="px-3 py-2 break-words text-muted-foreground">
                                                        {change.before ?? EMPTY}
                                                    </td>
                                                    <td className="px-3 py-2 break-words">{change.after ?? EMPTY}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
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
