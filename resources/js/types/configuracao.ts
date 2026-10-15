/**
 * Contrato da tela de configuração do projeto e das listas gerais (F2 §02 a §04), como o
 * `ProjectSettingsPresenter` manda. O servidor decide; a tela só mostra e esconde.
 */

export type SettingsTab = 'pessoas' | 'etapas' | 'areas' | 'padroes';

export interface ProjectDefaults {
    /** Levar o card para etapa de papel Pausa pede o motivo (P15). */
    require_pause_reason: boolean;
    /** Dias úteis, maior que 0 (P46). */
    sprint_days: number;
}

export interface SettingsProject {
    id: number;
    key_prefix: string;
    name: string;
    client: string;
    manager: string;
    /** DD/MM/AAAA; null = aberto (P7). */
    finished_at: string | null;
    defaults: ProjectDefaults;
    /** ProjectPolicy::manage: Administrador, responsável ou gestor alocado (F2 §09). */
    can: { manage: boolean };
}

/** Pessoa alocada (F2 §03): usuário desativado continua na lista, em cinza (N11). */
export interface MemberRow {
    id: number;
    user_id: number;
    name: string;
    role_label: string;
    /** Carga diária em horas, decimal como texto ("8.00"), ou null. */
    daily_hours: string | null;
    active: boolean;
    deactivated_at: string | null;
    deactivated_by: string | null;
    /** Tarefas deste projeto em que é dev / QA, para a confirmação de tirar (P5). */
    tasks_as_dev: number;
    tasks_as_qa: number;
}

/** Quem ainda pode entrar: ativo, com papel Desenvolvedor, Gestor de projetos ou Administrador, fora do projeto. */
export interface PersonOption {
    id: number;
    name: string;
    role_label: string;
}

export type StageRoleSlug = 'queue' | 'sprint' | 'development' | 'code_review' | 'test' | 'done' | 'paused' | 'support';

/** Etapa ativa do quadro (F2 §03), na ordem do quadro. */
export interface StageRow {
    id: number;
    name: string;
    position: number;
    role_id: number;
    role_slug: StageRoleSlug;
    role_label: string;
    /** Cards não arquivados na etapa hoje (para saber o que vai mover ao remover, P12). */
    cards: number;
    /** Fila, Sprint e Suporte, ou etapa por onde algum card já passou: o papel é só leitura (P8, P11). */
    role_locked: boolean;
    /** Fila, Sprint e Suporte não têm o botão Remover (P11). */
    removable: boolean;
}

/** Os papéis que a etapa nova pode ter (F2 §02): Desenvolvimento, Code review, Teste, Pronto e Pausa. */
export interface StageRoleOption {
    id: number;
    label: string;
}

export interface AreaRow {
    id: number;
    name: string;
    active: boolean;
    deactivated_at: string | null;
    deactivated_by: string | null;
}

export interface ProjectSettingsFilters {
    tab: SettingsTab;
    /** Só a aba Áreas usa (F2 §05). */
    show_inactive: boolean;
}

export interface ProjectSettingsOptions {
    people: PersonOption[];
    stage_roles: StageRoleOption[];
}

/** Motivo de retrabalho da lista geral (F2 §02; P40), como o Presenter manda. */
export interface ReworkReasonRow {
    id: number;
    label: string;
    requires_text: boolean;
    /** O "Outro": renomeia, mas não desliga o texto, não se desativa nem se exclui. */
    fixed: boolean;
    active: boolean;
    deactivated_at: string | null;
    deactivated_by: string | null;
    can: { update: boolean; deactivate: boolean; reactivate: boolean; delete: boolean };
}
