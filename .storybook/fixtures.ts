/**
 * Dados de exemplo das stories (organismos, templates e telas). Mesmo formato
 * que os controllers mandam; nomes e e-mails no padrão do ExemploSeeder.
 */
import type { AuthUser, Paginated, SharedProps } from '@/types';

import { SHARED_PROPS } from './inertia-mock';

export const ROLES = [
    { id: 1, label: 'Administrador' },
    { id: 2, label: 'Gestor de projetos' },
    { id: 3, label: 'Desenvolvedor' },
    { id: 4, label: 'Comercial' },
];

export const DEVELOPER: AuthUser = {
    id: 3,
    name: 'Bruno Lima',
    email: 'bruno@exemplo.wcj.test',
    role: 'developer',
    role_label: 'Desenvolvedor',
    must_change_password: false,
    abilities: { manage_users: false, delete_records: false, view_audit_logs: false, reset_passwords: false },
};

/** Props compartilhadas com outro usuário logado (ex.: ver o menu de quem não é admin). */
export const asUser = (user: AuthUser): Partial<SharedProps> => ({ auth: { user } });

/** Paginator do Laravel com uma página só (sem rodapé de paginação). */
export function onePage<T>(data: T[]): Paginated<T> {
    return {
        data,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: data.length,
        from: data.length ? 1 : null,
        to: data.length || null,
        links: [],
    };
}

/** Paginator com várias páginas, para ver o rodapé. */
export function manyPages<T>(data: T[], last = 3): Paginated<T> {
    const page = (n: number) => ({ url: `?page=${n}`, label: String(n), active: n === 1 });
    return {
        data,
        current_page: 1,
        last_page: last,
        per_page: data.length,
        total: data.length * last,
        from: 1,
        to: data.length,
        links: [
            { url: null, label: '&laquo; Previous', active: false },
            ...Array.from({ length: last }, (_, i) => page(i + 1)),
            { url: '?page=2', label: 'Next &raquo;', active: false },
        ],
    };
}

export const USERS = [
    {
        id: 1,
        name: 'Ana Souza',
        email: 'ana@exemplo.wcj.test',
        active: true,
        deactivated_at: null,
        deactivated_by: null,
        role_id: 1,
        role_label: 'Administrador',
        daily_hours: '8.00',
        must_change_password: false,
    },
    {
        id: 2,
        name: 'Bruno Lima',
        email: 'bruno@exemplo.wcj.test',
        active: true,
        deactivated_at: null,
        deactivated_by: null,
        role_id: 3,
        role_label: 'Desenvolvedor',
        daily_hours: '6.50',
        must_change_password: true,
    },
    {
        id: 3,
        name: 'Carla Dias',
        email: 'carla@exemplo.wcj.test',
        active: false,
        deactivated_at: '02/10/2026 14:10',
        deactivated_by: 'Ana Souza',
        role_id: 2,
        role_label: 'Gestor de projetos',
        daily_hours: null,
        must_change_password: false,
    },
];

export const LOGS = [
    {
        id: 12,
        user: 'Ana Souza',
        user_email: 'ana@exemplo.wcj.test',
        action: 'updated',
        action_label: 'Alterado',
        entity: 'user',
        entity_id: 3,
        outcome: 'success' as const,
        changes: { active: [true, false] as [unknown, unknown] },
        ip_address: '10.0.0.4',
        created_at: '02/10/2026 14:10',
    },
    {
        id: 11,
        user: 'Bruno Lima',
        user_email: 'bruno@exemplo.wcj.test',
        action: 'deleted',
        action_label: 'Excluído',
        entity: 'user',
        entity_id: null,
        outcome: 'denied' as const,
        changes: null,
        ip_address: null,
        created_at: '02/10/2026 11:02',
    },
    {
        id: 10,
        user: 'Ana Souza',
        user_email: 'ana@exemplo.wcj.test',
        action: 'login',
        action_label: 'Login',
        entity: 'user',
        entity_id: 1,
        outcome: 'success' as const,
        changes: null,
        ip_address: '10.0.0.4',
        created_at: '02/10/2026 08:57',
    },
];

export { SHARED_PROPS };
