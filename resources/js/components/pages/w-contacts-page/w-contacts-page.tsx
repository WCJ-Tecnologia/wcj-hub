import { Head, Link, router } from '@inertiajs/react';
import { Building2, UserRound } from 'lucide-react';

import { WEmptyStateMolecule } from '@/components/molecules/w-empty-state-molecule/w-empty-state-molecule';
import { WListBodyMolecule } from '@/components/molecules/w-list-body-molecule/w-list-body-molecule';
import { WPaginationMolecule } from '@/components/molecules/w-pagination-molecule/w-pagination-molecule';
import { WSearchInputMolecule } from '@/components/molecules/w-search-input-molecule/w-search-input-molecule';
import { WShowInactiveToggleMolecule } from '@/components/molecules/w-show-inactive-toggle-molecule/w-show-inactive-toggle-molecule';
import { WContactTableOrganism, type ContactRow } from '@/components/organisms/w-contact-table-organism/w-contact-table-organism';
import { Button } from '@/components/shadcn/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/shadcn/select';
import { WAppLayoutTemplate } from '@/components/templates/w-app-layout-template/w-app-layout-template';
import { useContactActions } from '@/hooks/use-contact-actions';
import { useListVisit } from '@/hooks/use-list-visit';
import type { Paginated } from '@/types';

export interface WContactsPageProps {
    contacts: Paginated<ContactRow>;
    filters: { search: string; client_id: number | null; show_inactive: boolean };
    /** Os clientes que a pessoa vê: todos para o Administrador, os dos projetos dele para o gestor. */
    clients: { id: number; name: string }[];
}

/** O Radix Select não aceita item com valor vazio: "todos" usa este valor e vira `client_id` ausente no request. */
const ALL = 'all';

/**
 * Tela Administração › Contatos (Inertia: `admin/contacts/index`; F1 §03): achar uma pessoa sem
 * saber a empresa. O contato nasce na ficha do cliente (o cliente é fixo no cadastro); aqui se
 * busca, edita e desativa.
 */
export function WContactsPage({ contacts, filters, clients }: WContactsPageProps) {
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
        <WAppLayoutTemplate eyebrow="Administração" title="Contatos" subtitle="Pessoas dos clientes com quem a equipe fala">
            <Head title="Contatos" />

            <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <WSearchInputMolecule
                    url="/admin/contacts"
                    value={filters.search}
                    params={filterParams}
                    placeholder="Buscar por nome, e-mail ou telefone…"
                    label="Buscar contatos"
                />
                <Select
                    value={filters.client_id ? String(filters.client_id) : ALL}
                    onValueChange={(value) => visit({ client_id: value === ALL ? undefined : value })}
                >
                    <SelectTrigger aria-label="Cliente" className="sm:w-56">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos os clientes</SelectItem>
                        {clients.map((client) => (
                            <SelectItem key={client.id} value={String(client.id)}>
                                {client.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <WShowInactiveToggleMolecule
                    checked={filters.show_inactive}
                    onChange={(checked) => visit({ show_inactive: checked ? 1 : undefined })}
                />
            </div>

            <WListBodyMolecule list={list} failedTitle="Não foi possível carregar os contatos" loadingText="Carregando contatos…">
                <WContactTableOrganism
                    caption="Lista de contatos"
                    rows={contacts.data}
                    actions={rowActions}
                    showClient
                    emptyState={
                        <WEmptyStateMolecule
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
            </WListBodyMolecule>

            <WPaginationMolecule meta={contacts} />

            {dialogs}
        </WAppLayoutTemplate>
    );
}
