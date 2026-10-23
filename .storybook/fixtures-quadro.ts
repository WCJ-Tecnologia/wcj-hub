/**
 * Dados de exemplo do quadro (F3) para as stories: o mesmo formato que o TaskPresenter do B3 manda
 * (`resources/js/types/quadro.ts`). Projeto e nomes no padrão do ExemploSeeder.
 */
import type {
    ArchivedCard,
    BoardAbilities,
    BoardCard,
    BoardFilters,
    BoardOptions,
    BoardProject,
    BoardStage,
    PersonRef,
    SprintPlanning,
} from '@/types/quadro';

export const BOARD_PROJECT: BoardProject = {
    id: 1,
    key_prefix: 'SGM',
    name: '[Exemplo] Sistema de Gestão Municipal',
    finished: false,
    pause_requires_reason: true,
    open_sprint: { id: 3, name: 'Sprint 3' },
    sprint_days: 10,
    sprints_count: 3,
};

/** O mesmo projeto sem sprint aberta: arrastar para o To do abre o diálogo Nova sprint (R5). */
export const BOARD_PROJECT_SEM_SPRINT: BoardProject = { ...BOARD_PROJECT, open_sprint: null };

/** As 8 etapas padrão, na ordem da P10 (decisão de 14/10): Support primeiro, Paused logo depois do To do. */
export const STAGES: BoardStage[] = [
    { id: 8, name: 'Support', role: 'support', position: 1 },
    { id: 1, name: 'Backlog', role: 'queue', position: 2 },
    { id: 2, name: 'To do', role: 'sprint', position: 3 },
    { id: 4, name: 'Paused', role: 'paused', position: 4 },
    { id: 3, name: 'Doing', role: 'development', position: 5 },
    { id: 5, name: 'Code-review', role: 'code_review', position: 6 },
    { id: 6, name: 'In test', role: 'test', position: 7 },
    { id: 7, name: 'Done', role: 'done', position: 8 },
];

export const ANA: PersonRef = { id: 1, name: 'Ana Souza' };
export const RAFAEL: PersonRef = { id: 2, name: 'Rafael Garcia' };
export const BRUNO: PersonRef = { id: 3, name: 'Bruno Lima' };
export const CARLA: PersonRef = { id: 4, name: 'Carla Mendes' };
export const DIEGO: PersonRef = { id: 5, name: 'Diego Prado' };

export const MEMBERS: PersonRef[] = [ANA, RAFAEL, BRUNO, CARLA, DIEGO];

const MELHORIA = { id: 1, slug: 'improvement', label: 'Melhoria', color: 'blue' as const };
const BUG = { id: 2, slug: null, label: 'Correção de bug', color: 'red' as const };
const SUPORTE = { id: 3, slug: 'support', label: 'Suporte', color: 'orange' as const };
const GMUD = { id: 4, slug: null, label: 'Mudança (GMUD)', color: 'purple' as const };
/** Funcionalidade do zero, sem nada feito ainda (decisão de 14/10). */
const DESENVOLVIMENTO = { id: 5, slug: 'development', label: 'Desenvolvimento', color: 'green' as const };

export const BOARD_OPTIONS: BoardOptions = {
    // Os 5 tipos padrão com que todo projeto nasce (14/10); o projeto cria os seus a partir daqui.
    types: [MELHORIA, BUG, SUPORTE, GMUD, DESENVOLVIMENTO],
    areas: [
        { id: 1, name: 'Cadastro', color: 'lime' },
        { id: 2, name: 'Financeiro', color: 'green' },
        { id: 3, name: 'Portal do cidadão', color: 'sky' },
        { id: 4, name: 'Relatórios', color: null },
    ],
    members: MEMBERS,
    rework_reasons: [
        { id: 1, slug: null, label: 'Ajuste visual' },
        { id: 2, slug: null, label: 'Regra de negócio' },
        { id: 3, slug: null, label: 'Erro / bug' },
        { id: 4, slug: null, label: 'Requisito incompleto ou mal documentado' },
        { id: 5, slug: 'other', label: 'Outro' },
    ],
    rework_origins: [
        { id: 1, slug: 'code_review', label: 'Code review' },
        { id: 2, slug: 'cross_test', label: 'Teste cruzado' },
        { id: 3, slug: 'qa', label: 'QA' },
        { id: 4, slug: 'homologation', label: 'Homologação' },
    ],
    contacts: [
        { id: 1, name: 'Marcos Vieira' },
        { id: 2, name: 'Juliana Ramos' },
    ],
    channels: [
        { id: 1, label: 'WhatsApp' },
        { id: 2, label: 'Telefone' },
        { id: 3, label: 'E-mail' },
        { id: 4, label: 'Reunião' },
        { id: 5, label: 'Outro' },
    ],
};

export const ABILITIES_GESTOR: BoardAbilities = {
    create_card: true,
    create_support: true,
    manage_sprint: true,
    move: true,
    archive: true,
    manage_types: true,
    manage_areas: true,
};
export const ABILITIES_DEV: BoardAbilities = {
    create_card: true,
    create_support: false,
    manage_sprint: false,
    move: true,
    archive: false,
    manage_types: false,
    manage_areas: false,
};
/** Projeto encerrado: ninguém cria card; mover segue (P7). */
export const ABILITIES_ENCERRADO: BoardAbilities = { ...ABILITIES_GESTOR, create_card: false, create_support: false };

/** Card mínimo: só o que o criar rápido preenche (P17, R4, 14/10): título; sem tipo, área nem prioridade. */
export function card(partial: Partial<BoardCard> & Pick<BoardCard, 'id' | 'key' | 'title' | 'stage_id'>): BoardCard {
    return {
        position: partial.id,
        priority: null,
        type: null,
        area: null,
        parent: null,
        cover_url: null,
        estimated_hours: null,
        proposed_hours: null,
        developer: null,
        qa: null,
        participants: [],
        due_on: null,
        late: false,
        attachments_count: 0,
        comments_count: 0,
        children: null,
        pause_reason: null,
        rework_count: 0,
        support_requester: null,
        qa_summary: null,
        url: `/projetos/1/cards/${partial.key}`,
        ...partial,
    };
}

/** Capa de exemplo: SVG inline (sem arquivo externo), um gradiente com o logo em texto. */
export const COVER_URL =
    'data:image/svg+xml;utf8,' +
    encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="320" viewBox="0 0 640 320"><defs><linearGradient id="g" x1="0" x2="1"><stop offset="0" stop-color="#0C0E13"/><stop offset="1" stop-color="#1A6CF3"/></linearGradient></defs><rect width="640" height="320" fill="url(#g)"/><circle cx="520" cy="90" r="38" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="3"/><circle cx="140" cy="230" r="22" fill="#fff" fill-opacity=".25"/><line x1="140" y1="230" x2="520" y2="90" stroke="#fff" stroke-opacity=".35" stroke-width="2"/></svg>',
    );

/** Um card com tudo: todas as zonas do F3 §03 preenchidas. */
export const CARD_COMPLETO: BoardCard = card({
    id: 17,
    key: 'SGM-17',
    title: 'Emitir a guia de IPTU com o código de barras no portal do cidadão',
    stage_id: 3,
    priority: 'high',
    type: MELHORIA,
    area: { id: 3, name: 'Portal do cidadão', color: 'sky' },
    parent: { id: 10, key: 'SGM-10', title: 'Portal do cidadão: 2ª via de tributos', url: '/projetos/1/cards/SGM-10' },
    cover_url: COVER_URL,
    estimated_hours: 16,
    proposed_hours: 24,
    developer: BRUNO,
    qa: CARLA,
    participants: [RAFAEL, DIEGO],
    due_on: '2026-10-09',
    late: true,
    attachments_count: 3,
    comments_count: 5,
    rework_count: 2,
});

export const CARDS: BoardCard[] = [
    // Backlog
    // SGM-21: como nasce do criar rápido (14/10): só o título, sem tipo, área nem prioridade.
    card({ id: 21, key: 'SGM-21', title: 'Relatório de inadimplência por bairro', stage_id: 1 }),
    card({
        id: 22,
        key: 'SGM-22',
        title: 'Exportar cadastro de contribuintes em CSV',
        stage_id: 1,
        type: DESENVOLVIMENTO,
        priority: 'low',
    }),
    card({
        id: 23,
        key: 'SGM-23',
        title: 'Login com gov.br',
        stage_id: 1,
        priority: 'urgent',
        type: DESENVOLVIMENTO,
        area: { id: 3, name: 'Portal do cidadão', color: 'sky' },
    }),
    // To do
    card({
        id: 18,
        key: 'SGM-18',
        title: 'Parcelamento da dívida ativa em até 12 vezes',
        stage_id: 2,
        area: { id: 2, name: 'Financeiro', color: 'green' },
        estimated_hours: 8,
        developer: ANA,
        attachments_count: 1,
    }),
    card({
        id: 19,
        key: 'SGM-19',
        title: 'Corrigir máscara do CEP no cadastro',
        stage_id: 2,
        type: BUG,
        priority: 'high',
        developer: DIEGO,
        estimated_hours: 2,
    }),
    // Doing
    CARD_COMPLETO,
    card({
        id: 20,
        key: 'SGM-20',
        title: 'Consulta de débitos por CPF',
        stage_id: 3,
        developer: ANA,
        qa: BRUNO,
        estimated_hours: 12,
        due_on: '2026-10-20',
        comments_count: 2,
        parent: { id: 10, key: 'SGM-10', title: 'Portal do cidadão: 2ª via de tributos', url: '/projetos/1/cards/SGM-10' },
    }),
    // Paused
    card({
        id: 15,
        key: 'SGM-15',
        title: 'Integração com o banco para baixa automática',
        stage_id: 4,
        area: { id: 2, name: 'Financeiro', color: 'green' },
        developer: DIEGO,
        estimated_hours: 40,
        due_on: '2026-10-15',
        pause_reason: 'Aguardando o convênio com o banco',
    }),
    // Code-review
    card({
        id: 16,
        key: 'SGM-16',
        title: 'Tela de impressão do carnê',
        stage_id: 5,
        developer: BRUNO,
        qa: CARLA,
        estimated_hours: 6,
        due_on: '2026-10-14',
        comments_count: 1,
    }),
    // In test
    card({
        id: 14,
        key: 'SGM-14',
        title: 'Cadastro de imóvel com geolocalização',
        stage_id: 6,
        area: { id: 1, name: 'Cadastro', color: 'lime' },
        developer: ANA,
        qa: DIEGO,
        estimated_hours: 20,
        due_on: '2026-10-13',
        rework_count: 1,
        attachments_count: 6,
        qa_summary: { rejected: 1, pending: 2 },
    }),
    // Done
    card({
        id: 10,
        key: 'SGM-10',
        title: 'Portal do cidadão: 2ª via de tributos',
        stage_id: 7,
        area: { id: 3, name: 'Portal do cidadão', color: 'sky' },
        developer: RAFAEL,
        estimated_hours: 4,
        children: { todo: 2, doing: 1, done: 3 },
        comments_count: 8,
    }),
    card({
        id: 11,
        key: 'SGM-11',
        title: 'Boleto com QR Code Pix',
        stage_id: 7,
        developer: BRUNO,
        qa: ANA,
        estimated_hours: 10,
        due_on: '2026-10-02',
    }),
    // Support
    card({
        id: 24,
        key: 'SGM-24',
        title: 'Contribuinte não consegue abrir a 2ª via no celular',
        stage_id: 8,
        type: SUPORTE,
        priority: 'urgent',
        support_requester: 'Marcos Vieira',
        developer: DIEGO,
    }),
];

/** O quadro de um projeto novo: nenhum card (F3 §03, estado vazio). */
export const CARDS_VAZIO: BoardCard[] = [];

/** A lista de arquivados (F3 §03), do mais recente para o mais antigo, como o `TaskPresenter::archivedRow` manda. */
export const ARCHIVED_CARDS: ArchivedCard[] = [
    {
        id: 41,
        key: 'SGM-41',
        title: 'Exportar multas em planilha',
        type: MELHORIA,
        archived_at: '2026-10-19 16:42',
        archive_reason: 'O cliente desistiu: o relatório do ERP já atende.',
        url: '/projetos/1/cards/SGM-41',
    },
    {
        id: 37,
        key: 'SGM-37',
        title: 'Login pelo gov.br',
        type: null,
        archived_at: '2026-10-14 09:05',
        archive_reason: 'Duplicado do SGM-12.',
        url: '/projetos/1/cards/SGM-37',
    },
    {
        id: 9,
        key: 'SGM-9',
        title: 'Corrigir o cálculo de juros do recurso de multa quando a notificação chega depois do prazo legal de defesa prévia',
        type: BUG,
        archived_at: '2026-09-30 11:20',
        archive_reason: 'Coberto pelo SGM-17, que refez a tela de recurso inteira.',
        url: '/projetos/1/cards/SGM-9',
    },
];

/** Quadro sem filtro nenhum: o que o controller manda na primeira visita (F3 §05). */
export const BOARD_FILTERS_VAZIO: BoardFilters = {
    search: '',
    mine: false,
    developer_id: null,
    qa_id: null,
    type_id: null,
    area_id: null,
    priority: null,
    marks: [],
    old_done: false,
    archived: false,
};

/**
 * *Montar sprint* (D-GER-35): o mesmo quadro com o Backlog mais cheio e horas previstas, para o modo de
 * seleção somar ("3 selecionadas · 14 h previstas (1 sem horas)").
 */
export const CARDS_MONTAR_SPRINT: BoardCard[] = [
    ...CARDS.map((item) =>
        item.key === 'SGM-22'
            ? { ...item, estimated_hours: 6 }
            : item.key === 'SGM-23'
              ? { ...item, estimated_hours: 8, developer: ANA }
              : item,
    ),
    // SGM-30: 44 h com o Bruno (4 h/dia) passa da capacidade dele numa sprint de 10 dias úteis (40 h): o aviso do plano.
    card({
        id: 30,
        key: 'SGM-30',
        title: 'Segunda via do IPTU pelo portal',
        stage_id: 1,
        priority: 'high',
        estimated_hours: 44,
        developer: BRUNO,
    }),
    card({ id: 31, key: 'SGM-31', title: 'Aviso de vencimento por e-mail', stage_id: 1, priority: 'normal', estimated_hours: 4.5 }),
];

/** Os ids marcados nas stories do modo de seleção: SGM-23, SGM-30 e SGM-21 (este sem horas). */
export const MONTAR_SPRINT_MARCADAS: number[] = [23, 30, 21];

/**
 * O planejamento do *Montar sprint* (D-GER-35), como o `SprintPlanningQuery` manda: as pessoas alocadas com a
 * carga diária (Diego sem carga) e, sem sprint aberta, nada mais.
 */
export const SPRINT_PLANNING: SprintPlanning = {
    people: [
        { id: ANA.id, name: ANA.name, daily_hours: 8, role: 'developer' },
        { id: BRUNO.id, name: BRUNO.name, daily_hours: 4, role: 'developer' },
        { id: CARLA.id, name: CARLA.name, daily_hours: 6, role: 'developer' },
        { id: DIEGO.id, name: DIEGO.name, daily_hours: null, role: 'developer' },
        { id: RAFAEL.id, name: RAFAEL.name, daily_hours: 8, role: 'project_manager' },
    ],
    open_sprint: null,
};

/** Com a Sprint 3 aberta: o que já falta de cada dev nela entra na conta do plano. */
export const SPRINT_PLANNING_COM_ABERTA: SprintPlanning = {
    ...SPRINT_PLANNING,
    open_sprint: {
        id: 3,
        name: 'Sprint 3',
        starts_on: '2026-10-06',
        ends_on: '2026-10-19',
        capacity: {
            business_days: 10,
            developers: [
                { id: ANA.id, name: ANA.name, daily_hours: 8, capacity: 80, remaining: 24, difference: 56 },
                { id: DIEGO.id, name: DIEGO.name, daily_hours: null, capacity: null, remaining: 40, difference: null },
            ],
            without_developer: { tasks: 1, hours: 2 },
            without_hours: { tasks: 1 },
        },
    },
};
