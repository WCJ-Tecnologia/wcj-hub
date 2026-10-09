import { expect, type Locator, type Page } from '@playwright/test';

import { AdminPage } from './admin.page';

/** Uma lista do Núcleo e os textos dos estados dela (stacks/ui.md §2; spec B1, P3 AC 1). */
export interface ListScreen {
    title: string;
    path: string;
    /** aria-label da busca. */
    search: string;
    empty: string;
    /** A ação do estado vazio: criar, ou o caminho até onde se cria. */
    emptyAction: { role: 'button' | 'link'; name: string };
    loading: string;
    error: string;
}

export const LIST_SCREENS: ListScreen[] = [
    {
        title: 'Usuários',
        path: '/admin/users',
        search: 'Buscar usuários',
        empty: 'Nenhum usuário com esses filtros',
        emptyAction: { role: 'button', name: 'Convidar usuário' },
        loading: 'Carregando usuários…',
        error: 'Não foi possível carregar os usuários',
    },
    {
        title: 'Clientes',
        path: '/admin/clients',
        search: 'Buscar clientes',
        empty: 'Nenhum cliente com esses filtros',
        emptyAction: { role: 'button', name: 'Novo cliente' },
        loading: 'Carregando clientes…',
        error: 'Não foi possível carregar os clientes',
    },
    {
        title: 'Contatos',
        path: '/admin/contacts',
        search: 'Buscar contatos',
        empty: 'Nenhum contato com esses filtros',
        emptyAction: { role: 'link', name: 'Ir para Clientes' },
        loading: 'Carregando contatos…',
        error: 'Não foi possível carregar os contatos',
    },
    {
        title: 'Auditoria',
        path: '/admin/audit-logs',
        search: 'Buscar pelo registro',
        empty: 'Nenhum registro com esses filtros',
        emptyAction: { role: 'button', name: 'Limpar filtros' },
        loading: 'Carregando a auditoria…',
        error: 'Não foi possível carregar a auditoria',
    },
];

export class ListPage {
    readonly admin: AdminPage;
    readonly search: Locator;
    /** O bloco da lista enquanto a visita roda (aria-busy). */
    readonly busy: Locator;

    constructor(
        readonly page: Page,
        readonly screen: ListScreen,
    ) {
        this.admin = new AdminPage(page);
        this.search = page.getByRole('searchbox', { name: screen.search });
        this.busy = page.locator('[aria-busy="true"]');
    }

    async goto() {
        await this.admin.goto(this.screen.path);
        await expect(this.admin.heading).toHaveText(this.screen.title);
    }

    /** Uma busca que não acha nada: dispara a visita Inertia da lista. */
    async searchNothing() {
        await this.search.fill(`nada-${Date.now()}`);
    }

    /** Intercepta a visita da busca (a requisição Inertia, não a navegação da página). */
    async interceptSearchVisit(handler: Parameters<Page['route']>[1]) {
        await this.page.route(
            (url) => url.pathname === this.screen.path && url.searchParams.has('search'),
            async (route, request) => {
                if (request.headers()['x-inertia'] !== 'true') return route.continue();

                return handler(route, request);
            },
        );
    }

    loadingStatus(): Locator {
        return this.page.getByRole('status').filter({ hasText: this.screen.loading });
    }

    /** O estado vazio ou de erro, pelo título dele. */
    state(title: string): Locator {
        return this.page.getByRole('heading', { name: title, exact: true }).locator('..');
    }
}
