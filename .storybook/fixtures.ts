/**
 * Dados de exemplo das stories (organismos, templates e telas). Mesmo formato
 * que os controllers mandam (Presenters do Núcleo); nomes e e-mails no padrão do ExemploSeeder.
 */
import type { AuditFilters, AuditOptions } from '@/components/organisms/w-audit-log-filters-organism/w-audit-log-filters-organism';
import type { AuditLogRow } from '@/components/organisms/w-audit-log-table-organism/w-audit-log-table-organism';
import type { Country } from '@/components/organisms/w-client-form-dialog-organism/w-client-form-dialog-organism';
import type { ClientRow } from '@/components/organisms/w-client-table-organism/w-client-table-organism';
import type { ContactRow } from '@/components/organisms/w-contact-table-organism/w-contact-table-organism';
import type { ProjectFilters } from '@/components/organisms/w-project-filters-organism/w-project-filters-organism';
import type { UserRow } from '@/components/organisms/w-user-table-organism/w-user-table-organism';
import type { ProjectRow } from '@/components/pages/w-projects-page/w-projects-page';
import type { AuthUser, NotificationsPayload, Paginated, SharedProps } from '@/types';

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
    abilities: {
        manage_users: false,
        delete_records: false,
        view_audit_logs: false,
        reset_passwords: false,
        view_clients: false,
        view_contacts: false,
        view_projects: true,
        manage_projects: false,
    },
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

const CAN_USER = { update: true, deactivate: true, reactivate: false, delete: false, resend_invite: true };

export const USERS: UserRow[] = [
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
        ai_write_allowed: true,
        must_change_password: false,
        last_login_at: '09/10/2026 08:57',
        can: { ...CAN_USER, deactivate: false, resend_invite: false },
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
        ai_write_allowed: false,
        must_change_password: true,
        last_login_at: null,
        can: { ...CAN_USER, delete: true },
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
        ai_write_allowed: true,
        must_change_password: false,
        last_login_at: '30/09/2026 17:02',
        can: { update: false, deactivate: false, reactivate: true, delete: false, resend_invite: false },
    },
];

export const COUNTRIES: Country[] = [
    { code: 'BR', name: 'Brasil' },
    { code: 'US', name: 'Estados Unidos' },
    { code: 'NL', name: 'Países Baixos' },
];

const CAN_CLIENT = { update: true, deactivate: true, reactivate: false, delete: false };

export const CLIENTS: ClientRow[] = [
    {
        id: 1,
        name: 'WCJ Tecnologia',
        country: 'BR',
        country_name: 'Brasil',
        document: '38614817000122',
        active: true,
        deactivated_at: null,
        deactivated_by: null,
        open_projects: 2,
        can: { ...CAN_CLIENT, deactivate: false },
    },
    {
        id: 2,
        name: '[Exemplo] Rezende Bouw',
        country: 'NL',
        country_name: 'Países Baixos',
        document: 'NL123456789B01',
        active: true,
        deactivated_at: null,
        deactivated_by: null,
        open_projects: 1,
        can: CAN_CLIENT,
    },
    {
        id: 3,
        name: '[Exemplo] Padaria do Bairro',
        country: 'BR',
        country_name: 'Brasil',
        document: '12ABC34501DE35',
        active: false,
        deactivated_at: '01/10/2026 10:00',
        deactivated_by: 'Ana Souza',
        open_projects: null,
        can: { update: false, deactivate: false, reactivate: true, delete: true },
    },
];

const CAN_CONTACT = { update: true, deactivate: true, reactivate: false, delete: false, anonymize: true };

export const CONTACTS: ContactRow[] = [
    {
        id: 1,
        name: 'Wilson Correia',
        email: 'wilson@exemplo.wcj.test',
        phone: '+55 11 99999-0001',
        client_id: 1,
        client_name: 'WCJ Tecnologia',
        active: true,
        anonymized: false,
        deactivated_at: null,
        deactivated_by: null,
        can: CAN_CONTACT,
    },
    {
        id: 2,
        name: 'Paola Rezende',
        email: null,
        phone: '+31 6 1234 5678',
        client_id: 2,
        client_name: '[Exemplo] Rezende Bouw',
        active: false,
        anonymized: false,
        deactivated_at: '03/10/2026 09:30',
        deactivated_by: 'Ana Souza',
        can: { update: false, deactivate: false, reactivate: true, delete: false, anonymize: true },
    },
    {
        id: 3,
        name: 'removido a pedido do titular',
        email: null,
        phone: null,
        client_id: 1,
        client_name: 'WCJ Tecnologia',
        active: false,
        anonymized: true,
        deactivated_at: '04/10/2026 11:15',
        deactivated_by: 'Ana Souza',
        can: { update: false, deactivate: false, reactivate: false, delete: false, anonymize: false },
    },
];

export const LOGS: AuditLogRow[] = [
    {
        id: 12,
        created_at: '02/10/2026 14:10:31',
        user: 'Ana Souza',
        user_email: 'ana@exemplo.wcj.test',
        channel: 'Tela',
        action: 'deactivated',
        action_label: 'Desativado',
        type: 'user',
        type_label: 'Usuário',
        auditable_id: 3,
        record: 'Usuário Carla Dias',
        outcome: 'success',
        changes: [{ field: 'active', label: 'Situação', before: 'Ativo', after: 'Desativado' }],
        ip_address: '10.0.0.4',
    },
    {
        id: 11,
        created_at: '02/10/2026 11:02:08',
        user: 'Bruno Lima',
        user_email: 'bruno@exemplo.wcj.test',
        channel: 'IA: Claude',
        action: 'deleted',
        action_label: 'Excluído',
        type: 'client',
        type_label: 'Cliente',
        auditable_id: 2,
        record: 'Cliente [Exemplo] Rezende Bouw',
        outcome: 'denied',
        changes: [],
        ip_address: null,
    },
    {
        id: 10,
        created_at: '02/10/2026 08:57:44',
        user: 'Ana Souza',
        user_email: 'ana@exemplo.wcj.test',
        channel: 'Tela',
        action: 'login',
        action_label: 'Login',
        type: 'user',
        type_label: 'Usuário',
        auditable_id: 1,
        record: 'Usuário Ana Souza',
        outcome: 'success',
        changes: [],
        ip_address: '10.0.0.4',
    },
];

/** Os filtros como o controller devolve sem nada escolhido: "De" vem com 7 dias. */
export const AUDIT_FILTERS: AuditFilters = {
    search: '',
    from: '2026-09-25',
    to: null,
    user_id: null,
    channel: null,
    action: null,
    type: null,
    id: null,
    outcome: null,
};

export const AUDIT_OPTIONS: AuditOptions = {
    users: USERS.map((user) => ({ value: user.id, label: user.name })),
    channels: [
        { value: 'screen', label: 'Tela' },
        { value: 'claude', label: 'IA: Claude' },
        { value: 'codex', label: 'IA: Codex' },
    ],
    actions: [
        { value: 'created', label: 'Criado' },
        { value: 'updated', label: 'Alterado' },
        { value: 'deactivated', label: 'Desativado' },
        { value: 'deleted', label: 'Excluído' },
        { value: 'login', label: 'Login' },
    ],
    types: [
        { value: 'user', label: 'Usuário' },
        { value: 'client', label: 'Cliente' },
        { value: 'contact', label: 'Contato' },
    ],
    outcomes: [
        { value: 'success', label: 'Ok' },
        { value: 'denied', label: 'Negado' },
    ],
};

export const PROJECTS: ProjectRow[] = [
    {
        id: 1,
        key_prefix: 'SGM',
        name: 'Gestão de multas',
        client: { id: 2, name: '[Exemplo] Rezende Bouw' },
        manager: { id: 3, name: 'Carla Dias' },
        starts_on: '2026-10-06',
        due_on: '2026-12-18',
        finished_at: null,
        prefix_locked: true,
        open_cards: 4,
        can: { manage: true, delete: false },
    },
    {
        id: 2,
        key_prefix: 'WPAY',
        name: 'Wpay',
        client: { id: 1, name: 'WCJ Tecnologia' },
        manager: { id: 1, name: 'Ana Souza' },
        starts_on: '2026-09-01',
        due_on: null,
        finished_at: null,
        prefix_locked: false,
        open_cards: 0,
        can: { manage: true, delete: true },
    },
    {
        id: 3,
        key_prefix: 'PAD',
        name: 'Pedidos da padaria',
        client: { id: 3, name: '[Exemplo] Padaria do Bairro' },
        manager: { id: 3, name: 'Carla Dias' },
        starts_on: '2026-05-04',
        due_on: '2026-08-29',
        finished_at: '30/09/2026',
        prefix_locked: true,
        open_cards: 1,
        can: { manage: true, delete: false },
    },
];

export const PROJECT_FILTERS: ProjectFilters = { search: '', client_id: null, manager_id: null, type: null, finished: false };

/** Avisos da pessoa logada (F3 §07), não lidos primeiro; o 4º é de um card excluído (`url: null`). */
export const NOTIFICATIONS: NotificationsPayload = {
    unread_count: 2,
    items: [
        {
            id: '9b1f6c2e-4d7a-4c1e-9f3a-1a2b3c4d5e01',
            text: 'SGM-17 — Emitir a guia de IPTU com o código de barras no portal do cidadão voltou para To do: origem QA, motivo Ajuste visual, por Carla Mendes.',
            url: '/projetos/1/cards/SGM-17',
            created_at: '2026-10-14 16:42',
            read_at: null,
        },
        {
            id: '9b1f6c2e-4d7a-4c1e-9f3a-1a2b3c4d5e02',
            text: 'Você é o QA de SGM-20 — Consulta de débitos por CPF (projeto [Exemplo] Sistema de Gestão Municipal), definido por Rafael Garcia.',
            url: '/projetos/1/cards/SGM-20',
            created_at: '2026-10-14 10:05',
            read_at: null,
        },
        {
            id: '9b1f6c2e-4d7a-4c1e-9f3a-1a2b3c4d5e03',
            text: 'Você é o dev de SGM-17 — Emitir a guia de IPTU com o código de barras no portal do cidadão (projeto [Exemplo] Sistema de Gestão Municipal), definido por Rafael Garcia.',
            url: '/projetos/1/cards/SGM-17',
            created_at: '2026-10-13 09:30',
            read_at: '2026-10-13 09:41',
        },
        {
            id: '9b1f6c2e-4d7a-4c1e-9f3a-1a2b3c4d5e04',
            text: 'Você é o dev de SGM-9 — Tela de login do portal (projeto [Exemplo] Sistema de Gestão Municipal), definido por Bruno Lima.',
            url: null,
            created_at: '2026-10-09 14:12',
            read_at: '2026-10-09 15:00',
        },
    ],
};

export const NOTIFICATIONS_VAZIO: NotificationsPayload = { unread_count: 0, items: [] };

export { SHARED_PROPS };
