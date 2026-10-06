import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, History, UserRound } from 'lucide-react';

import { documentLabel } from '@/components/clients/client-form-dialog';
import { deactivation, formatDocument, type ClientRow } from '@/components/clients/client-table';
import { DataTable, type Column } from '@/components/data-table';
import { EmptyState } from '@/components/empty-state';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatPhone } from '@/lib/masks';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types';

interface ContactRow {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    active: boolean;
    deactivated_at: string | null;
    deactivated_by: string | null;
}

interface Props {
    client: ClientRow;
    /** Nulo para quem não vê os contatos deste cliente (F1 §09). */
    contacts: ContactRow[] | null;
}

// Desativado fica em cinza (F1 §03).
const muted = (row: { active: boolean }) => cn(!row.active && 'text-muted-foreground');

const CONTACT_COLUMNS: Column<ContactRow>[] = [
    { key: 'name', header: 'Nome', cell: (row) => <span className={cn('font-medium', muted(row))}>{row.name}</span> },
    { key: 'email', header: 'E-mail', className: 'text-muted-foreground', cell: (row) => row.email ?? '—' },
    { key: 'phone', header: 'Telefone', className: 'text-muted-foreground tabular-nums', cell: (row) => formatPhone(row.phone) || '—' },
    {
        key: 'active',
        header: 'Situação',
        cell: (row) => (
            <>
                <StatusPill kind="contact" status={row.active} />
                {!row.active && <p className="mt-1 text-xs text-muted-foreground">{deactivation(row)}</p>}
            </>
        ),
    },
];

/** Ficha do cliente (F1 §01; ADR-004): dados e contatos. Criar e editar contato chegam na fase de contatos. */
export default function ClientShow({ client, contacts }: Props) {
    const abilities = usePage<SharedProps>().props.auth.user?.abilities;

    const actions = (
        <>
            <Button variant="outline" asChild>
                <Link href="/admin/clients">
                    <ArrowLeft className="size-4" aria-hidden /> Clientes
                </Link>
            </Button>
            {abilities?.view_audit_logs && (
                <Button variant="outline" onClick={() => router.visit(`/admin/audit-logs?type=client&id=${client.id}`)}>
                    <History className="size-4" aria-hidden /> Histórico
                </Button>
            )}
        </>
    );

    return (
        <AppLayout eyebrow="Cliente" title={client.name} subtitle={client.country_name} actions={actions}>
            <Head title={client.name} />

            <section aria-labelledby="client-data" className="mb-6">
                <h2 id="client-data" className="mb-3 font-heading text-base font-semibold">
                    Dados
                </h2>
                <Card className="p-5">
                    <dl className="grid gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt className="text-xs text-muted-foreground">País</dt>
                            <dd className="mt-1 font-medium">{client.country_name}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">{documentLabel(client.country)}</dt>
                            <dd className="mt-1 font-mono font-medium tabular-nums">{formatDocument(client)}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">Situação</dt>
                            <dd className="mt-1">
                                <StatusPill kind="client" status={client.active} />
                                {!client.active && <p className="mt-1 text-xs text-muted-foreground">{deactivation(client)}</p>}
                            </dd>
                        </div>
                    </dl>
                </Card>
            </section>

            {contacts !== null && (
                <section aria-labelledby="client-contacts">
                    <h2 id="client-contacts" className="mb-3 font-heading text-base font-semibold">
                        Contatos
                    </h2>
                    <DataTable
                        caption={`Contatos de ${client.name}`}
                        columns={CONTACT_COLUMNS}
                        rows={contacts}
                        rowKey={(row) => row.id}
                        emptyState={
                            <EmptyState
                                icon={<UserRound className="size-5" aria-hidden />}
                                title="Nenhum contato ainda"
                                description="As pessoas deste cliente com quem a equipe fala aparecem aqui."
                            />
                        }
                    />
                </section>
            )}
        </AppLayout>
    );
}
