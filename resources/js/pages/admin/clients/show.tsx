import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, History, Plus, UserRound } from 'lucide-react';

import { documentLabel } from '@/components/clients/client-form-dialog';
import { formatDocument, type ClientRow } from '@/components/clients/client-table';
import { useContactActions } from '@/components/contacts/contact-actions';
import { ContactTable, type ContactRow } from '@/components/contacts/contact-table';
import { EmptyState } from '@/components/empty-state';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { deactivation } from '@/lib/situation';
import type { SharedProps } from '@/types';

interface Props {
    client: ClientRow;
    /** Nulo para quem não vê os contatos deste cliente (F1 §09). */
    contacts: ContactRow[] | null;
    /** ContactPolicy::create, e o cliente ativo (N11). */
    can_create_contact: boolean;
}

/** Ficha do cliente (F1 §01; ADR-004): dados e a aba Contatos, com criar e as ações de cada contato. */
export default function ClientShow({ client, contacts, can_create_contact }: Props) {
    const abilities = usePage<SharedProps>().props.auth.user?.abilities;
    const { openCreate, rowActions, dialogs } = useContactActions();

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

    const newContact = can_create_contact ? (
        <Button onClick={() => openCreate({ id: client.id, name: client.name })}>
            <Plus className="size-4" aria-hidden /> Novo contato
        </Button>
    ) : undefined;

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
                    <div className="mb-3 flex items-center justify-between gap-3">
                        <h2 id="client-contacts" className="font-heading text-base font-semibold">
                            Contatos
                        </h2>
                        {contacts.length > 0 && newContact}
                    </div>
                    <ContactTable
                        caption={`Contatos de ${client.name}`}
                        rows={contacts}
                        actions={rowActions}
                        emptyState={
                            <EmptyState
                                icon={<UserRound className="size-5" aria-hidden />}
                                title="Nenhum contato ainda"
                                description="As pessoas deste cliente com quem a equipe fala aparecem aqui."
                                action={newContact}
                            />
                        }
                    />
                </section>
            )}

            {dialogs}
        </AppLayout>
    );
}
