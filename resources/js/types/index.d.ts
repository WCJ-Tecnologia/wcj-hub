import type { PageProps as InertiaPageProps } from '@inertiajs/core';

/** Mirror of App\\Platform\\Enums\\RoleEnum (docs/banco.md, roles). */
export type RoleSlug = 'admin' | 'project_manager' | 'developer' | 'sales';

/**
 * Mirror of HandleInertiaRequests::userPayload()['abilities']. The front uses
 * these only to show or hide UI; the Policies decide.
 */
export interface Abilities {
    manage_users: boolean;
    delete_records: boolean;
    view_audit_logs: boolean;
    reset_passwords: boolean;
    /** ClientPolicy::viewAny: Administrador e Gestor de projetos. */
    view_clients: boolean;
    /** ContactPolicy::viewAny: Administrador e Gestor de projetos (só os clientes dele). */
    view_contacts: boolean;
    /** ProjectPolicy::viewAny: todos menos o Comercial (C14). */
    view_projects: boolean;
    /** ProjectPolicy::create: Administrador e Gestor de projetos. */
    manage_projects: boolean;
    /** ReworkReasonPolicy::viewAny: só o Administrador edita as listas gerais (G7, F2 §09). */
    manage_lists: boolean;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string | null;
    role: RoleSlug;
    role_label: string;
    must_change_password: boolean;
    abilities: Abilities;
}

export interface FlashMessages {
    success?: string;
    error?: string;
    /** Vem junto do success: a ação foi feita, com um aviso. */
    warning?: string;
    /** O id do registro que a ação acabou de criar (o "Novo cliente" do formulário do projeto, N6). */
    created_id?: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface Option {
    id: number;
    name?: string;
    label?: string;
    nickname?: string;
}

export interface AppBrand {
    /** Brand name shown in the shell (APP_BRAND_NAME). The logo files are fixed in WBrandLogoAtom. */
    name: string;
}

export interface AppInfo {
    /** APP_NAME — the product. */
    name: string;
    brand: AppBrand;
}

/** Um aviso da tabela `notifications` do Laravel (P22, R3), como o `HandleInertiaRequests` compartilha. */
export interface HubNotification {
    /** UUID da notificação. */
    id: string;
    /** O texto do F3 §07: "Você é o dev de SGM-17 — <título> (projeto <nome>), definido por Fulano." */
    text: string;
    /** URL da ficha do card; null quando o card foi excluído. */
    url: string | null;
    /** `AAAA-MM-DD HH:MM` no fuso de São Paulo. */
    created_at: string;
    read_at: string | null;
}

export interface NotificationsPayload {
    unread_count: number;
    /** Os 10 mais recentes, não lidos primeiro. */
    items: HubNotification[];
}

export interface SharedProps extends InertiaPageProps {
    app: AppInfo;
    auth: { user: AuthUser | null };
    flash: FlashMessages;
    /** Avisos da pessoa (F3 §07), do `NotificationPresenter` (B3, PR 3); nula sem login. O sino renderiza vazio sem ela. */
    notifications?: NotificationsPayload | null;
    [key: string]: unknown;
}
