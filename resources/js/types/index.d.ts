import type { PageProps as InertiaPageProps } from '@inertiajs/core';

/** Mirror of App\\Platform\\Enums\\RoleEnum (docs/banco.txt, roles). */
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

export interface SharedProps extends InertiaPageProps {
    app: AppInfo;
    auth: { user: AuthUser | null };
    flash: FlashMessages;
    [key: string]: unknown;
}
