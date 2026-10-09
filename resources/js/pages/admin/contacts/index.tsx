import { Head, Link, router } from '@inertiajs/react';
import { Building2, UserRound } from 'lucide-react';

import { useContactActions } from '@/components/contacts/contact-actions';
import { ContactTable, type ContactRow } from '@/components/contacts/contact-table';
import { EmptyState } from '@/components/empty-state';
import { ListBody } from '@/components/list-body';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { ShowInactiveToggle } from '@/components/show-inactive-toggle';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { useListVisit } from '@/hooks/use-list-visit';
import AppLayout from '@/layouts/app-layout';
import type { Paginated } from '@/types';

interface Props {
    contacts: Paginated<ContactRow>;
    filters: { search: string; client_id: number | null; show_inactive: boolean };
    /** Os clientes que a pessoa vê: todos para o Administrador, os dos projetos dele para o gestor. */
    clients: { id: number; name: string }[];
}

/**
 * Administração → Contatos (F1 §03): achar uma pessoa sem saber a empresa. O contato nasce na
 * ficha do cliente (o cliente é fixo no cadastro); aqui se busca, edita e desativa.
 */
export default function ContactsIndex({ contacts, filters, clients }: Props) {
    const list = useListVisit();
    const { rowActions, dialogs } = useContactActions();

    const filterParams = {
        search: filters.search || undefined,
        client_id: filters.client_id ?? undefined,
        show_inactive: filters.show_inactive ? 1 : undefined,
    };
    const visit = (next: Record<string, string | number | undefined>) =>
        router.get('/admin/contacts', { ...filterParams, ...next }, { preserveState: true, replace: true });

    const filtered = Boolean(filters.search || filters.client_id);

    return (
        <AppLayout eyebrow="Administração" title="Contatos" subtitle="Pessoas dos clientes com quem a equipe fala">
            <Head title="Contatos" />

            <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <SearchInput
                    url="/admin/contacts"
                    value={filters.search}
                    params={filterParams}
                    placeholder="Buscar por nome, e-mail ou telefone…"
                    label="Buscar contatos"
                />
                <Select
                    value={filters.client_id ?? ''}
                    onChange={(event) => visit({ client_id: event.target.value || undefined })}
                    aria-label="Cliente"
                >
                    <option value="">Todos os clientes</option>
                    {clients.map((client) => (
                        <option key={client.id} value={client.id}>
                            {client.name}
                        </option>
                    ))}
                </Select>
                <ShowInactiveToggle
                    checked={filters.show_inactive}
                    onChange={(checked) => visit({ show_inactive: checked ? 1 : undefined })}
                />
            </div>

            <ListBody list={list} failedTitle="Não foi possível carregar os contatos" loadingText="Carregando contatos…">
                <ContactTable
                    caption="Lista de contatos"
                    rows={contacts.data}
                    actions={rowActions}
                    showClient
                    emptyState={
                        <EmptyState
                            icon={<UserRound className="size-5" aria-hidden />}
                            title={filtered ? 'Nenhum contato com esses filtros' : 'Nenhum contato ainda'}
                            description="O contato é cadastrado na ficha do cliente, em Novo contato."
                            action={
                                <Button variant="outline" asChild>
                                    <Link href="/admin/clients">
                                        <Building2 className="size-4" aria-hidden /> Ir para Clientes
                                    </Link>
                                </Button>
                            }
                        />
                    }
                />
            </ListBody>

            <Pagination meta={contacts} />

            {dialogs}
        </AppLayout>
    );
}
