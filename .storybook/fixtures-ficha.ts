/**
 * Dados de exemplo da ficha do card (F3 §02) para as stories: o mesmo formato que o
 * `TaskPresenter::detail()` do B3 manda (`resources/js/types/quadro.ts`, bloco "Ficha do card").
 * Projeto, etapas e pessoas são os do quadro (`fixtures-quadro.ts`).
 */
import type {
    AcceptanceCriterion,
    BoardStage,
    ChildCard,
    TaskAbilities,
    TaskAttachment,
    TaskComment,
    TaskDetail,
    TaskDetailOptions,
    TaskPickRef,
    TaskPullRequest,
} from '@/types/quadro';

import { ANA, BOARD_OPTIONS, BRUNO, CARLA, COVER_URL, DIEGO, RAFAEL, STAGES } from './fixtures-quadro';

export const ABILITIES_FICHA_GESTOR: TaskAbilities = {
    update: true,
    comment: true,
    attach: true,
    set_hours: true,
    propose_hours: false,
    decide_hours: true,
    move: true,
    manage_sprint: true,
    archive: true,
    delete: false,
    manage_types: true,
    manage_areas: true,
};

export const ABILITIES_FICHA_DEV: TaskAbilities = {
    update: true,
    comment: true,
    attach: true,
    set_hours: false,
    propose_hours: true,
    decide_hours: false,
    move: true,
    manage_sprint: false,
    archive: false,
    delete: false,
    manage_types: false,
    manage_areas: false,
};

/** Gestor num card nunca usado: *Excluir* aparece (P26). */
export const ABILITIES_FICHA_NUNCA_USADO: TaskAbilities = { ...ABILITIES_FICHA_GESTOR, delete: true };

/** Quem só vê (não alocado que é Administrador de outro… na fatia 1 não existe; serve para a story "só leitura"). */
export const ABILITIES_FICHA_LEITURA: TaskAbilities = {
    update: false,
    comment: false,
    attach: false,
    set_hours: false,
    propose_hours: false,
    decide_hours: false,
    move: false,
    manage_sprint: false,
    archive: false,
    delete: false,
    manage_types: false,
    manage_areas: false,
};

const stage = (role: BoardStage['role']) => STAGES.find((item) => item.role === role)!;
const BACKLOG = stage('queue');
const TODO = stage('sprint');
const DOING = stage('development');
const PAUSED = stage('paused');
const DONE = stage('done');

/** Os cards do projeto para as buscas de card pai e tarefa de origem (sem o próprio card nem arquivados). */
export const PICK_CARDS: TaskPickRef[] = [
    {
        id: 10,
        key: 'SGM-10',
        title: 'Portal do cidadão: 2ª via de tributos',
        url: '/projetos/1/cards/SGM-10',
        parent_key: null,
        has_children: true,
    },
    { id: 11, key: 'SGM-11', title: 'Boleto com QR Code Pix', url: '/projetos/1/cards/SGM-11', parent_key: null, has_children: false },
    {
        id: 14,
        key: 'SGM-14',
        title: 'Cadastro de imóvel com geolocalização',
        url: '/projetos/1/cards/SGM-14',
        parent_key: null,
        has_children: false,
    },
    {
        id: 15,
        key: 'SGM-15',
        title: 'Integração com o banco para baixa automática',
        url: '/projetos/1/cards/SGM-15',
        parent_key: null,
        has_children: false,
    },
    { id: 16, key: 'SGM-16', title: 'Tela de impressão do carnê', url: '/projetos/1/cards/SGM-16', parent_key: null, has_children: false },
    {
        id: 18,
        key: 'SGM-18',
        title: 'Parcelamento da dívida ativa em até 12 vezes',
        url: '/projetos/1/cards/SGM-18',
        parent_key: null,
        has_children: false,
    },
    {
        id: 19,
        key: 'SGM-19',
        title: 'Corrigir máscara do CEP no cadastro',
        url: '/projetos/1/cards/SGM-19',
        parent_key: null,
        has_children: false,
    },
    {
        id: 20,
        key: 'SGM-20',
        title: 'Consulta de débitos por CPF',
        url: '/projetos/1/cards/SGM-20',
        parent_key: 'SGM-10',
        has_children: false,
    },
    {
        id: 21,
        key: 'SGM-21',
        title: 'Relatório de inadimplência por bairro',
        url: '/projetos/1/cards/SGM-21',
        parent_key: null,
        has_children: false,
    },
    {
        id: 22,
        key: 'SGM-22',
        title: 'Exportar cadastro de contribuintes em CSV',
        url: '/projetos/1/cards/SGM-22',
        parent_key: null,
        has_children: false,
    },
    { id: 23, key: 'SGM-23', title: 'Login com gov.br', url: '/projetos/1/cards/SGM-23', parent_key: null, has_children: false },
];

export const DETAIL_OPTIONS: TaskDetailOptions = {
    areas: BOARD_OPTIONS.areas,
    types: BOARD_OPTIONS.types,
    members: BOARD_OPTIONS.members,
    rework_origins: BOARD_OPTIONS.rework_origins,
    rework_reasons: BOARD_OPTIONS.rework_reasons,
    stages: STAGES,
    cards: PICK_CARDS,
};

export const CRITERIA: AcceptanceCriterion[] = [
    {
        id: 1,
        number: 'CA_1',
        description: 'A guia sai com o código de barras no padrão FEBRABAN e o leitor do banco reconhece.',
        result: 'approved',
        children: [],
    },
    {
        id: 2,
        number: 'CA_2',
        description: 'O contribuinte sem débito vê a mensagem "Nenhuma guia em aberto" (SGM-20).',
        result: 'failed',
        children: [
            { id: 4, number: 'CA_2.1', description: 'A mensagem aparece também no celular.', result: 'pending', children: [] },
            { id: 5, number: 'CA_2.2', description: 'O botão "Emitir" fica desabilitado.', result: 'failed', children: [] },
        ],
    },
    {
        id: 3,
        number: 'CA_3',
        description: 'A guia emitida fica no histórico do contribuinte por 5 anos.',
        result: 'pending',
        children: [{ id: 6, number: 'CA_3.1', description: 'O download repete o mesmo PDF.', result: 'pending', children: [] }],
    },
    // CA_4 foi excluído: o número não volta (T9).
    { id: 7, number: 'CA_5', description: 'A emissão fica registrada na auditoria com o CPF mascarado.', result: 'pending', children: [] },
];

export const PULL_REQUESTS: TaskPullRequest[] = [
    { id: 1, url: 'https://github.com/WCJ-Tecnologia/sgm/pull/118', added_by: BRUNO, added_at: '2026-10-09 10:12' },
    { id: 2, url: 'https://github.com/WCJ-Tecnologia/sgm-portal/pull/41', added_by: BRUNO, added_at: '2026-10-10 16:40' },
];

export const COMMENTS: TaskComment[] = [
    {
        id: 1,
        author: RAFAEL,
        body: 'O layout da guia segue o modelo que a prefeitura mandou (anexo guia-modelo.pdf). O código de barras vai no rodapé.',
        created_at: '2026-10-07 09:15',
        edited_at: null,
        can: { update: false, delete: false },
    },
    {
        id: 2,
        author: BRUNO,
        body: 'Subi o PR #118 com a geração do código de barras. Falta só o teste com o leitor do banco.',
        created_at: '2026-10-09 10:14',
        edited_at: '2026-10-09 10:20',
        can: { update: true, delete: true },
    },
    {
        id: 3,
        author: CARLA,
        body: 'Reprovei o CA_2.2: o botão continua ativo quando não há débito.',
        created_at: '2026-10-13 15:02',
        edited_at: null,
        can: { update: false, delete: false },
    },
];

export const ATTACHMENTS: TaskAttachment[] = [
    {
        id: 1,
        name: 'guia-modelo.pdf',
        size: 184_320,
        is_image: false,
        url: '/anexos/1/guia-modelo.pdf',
        uploaded_by: RAFAEL,
        uploaded_at: '2026-10-07 09:14',
        criterion_id: null,
    },
    {
        id: 2,
        name: 'tela-guia-ok.png',
        size: 412_900,
        is_image: true,
        url: COVER_URL,
        uploaded_by: BRUNO,
        uploaded_at: '2026-10-09 10:30',
        criterion_id: null,
    },
    {
        id: 3,
        name: 'print do erro no celular.png',
        size: 98_210,
        is_image: true,
        url: COVER_URL,
        uploaded_by: CARLA,
        uploaded_at: '2026-10-13 15:01',
        criterion_id: 5,
    },
];

export const CHILDREN: ChildCard[] = [
    {
        id: 17,
        key: 'SGM-17',
        title: 'Emitir a guia de IPTU com o código de barras no portal do cidadão',
        url: '/projetos/1/cards/SGM-17',
        stage: DOING,
        developer: BRUNO,
    },
    { id: 20, key: 'SGM-20', title: 'Consulta de débitos por CPF', url: '/projetos/1/cards/SGM-20', stage: DOING, developer: ANA },
    { id: 25, key: 'SGM-25', title: 'Parcelar a 2ª via em até 3 vezes', url: '/projetos/1/cards/SGM-25', stage: TODO, developer: null },
    {
        id: 26,
        key: 'SGM-26',
        title: 'Aviso por e-mail quando a guia vence',
        url: '/projetos/1/cards/SGM-26',
        stage: BACKLOG,
        developer: null,
    },
    { id: 11, key: 'SGM-11', title: 'Boleto com QR Code Pix', url: '/projetos/1/cards/SGM-11', stage: DONE, developer: BRUNO },
    { id: 12, key: 'SGM-12', title: 'Autenticação da guia pelo QR Code', url: '/projetos/1/cards/SGM-12', stage: DONE, developer: DIEGO },
    {
        id: 13,
        key: 'SGM-13',
        title: 'Layout da guia no padrão da prefeitura',
        url: '/projetos/1/cards/SGM-13',
        stage: DONE,
        developer: ANA,
    },
];

/** Card mínimo: só o que o criar rápido preenche (P17, R4). Nunca usado: dá para excluir (P26). */
export function detail(partial: Partial<TaskDetail> & Pick<TaskDetail, 'id' | 'key' | 'title'>): TaskDetail {
    return {
        // O criar rápido nasce sem tipo, área nem prioridade (P17, 14/10).
        type: null,
        area: null,
        priority: null,
        stage: BACKLOG,
        developer: null,
        qa: null,
        participants: [],
        requisition: { story_as: null, story_want: null, story_so_that: null, description: null },
        criteria: [],
        pull_requests: [],
        comments: [],
        attachments: [],
        cover_attachment_id: null,
        origin_task: null,
        production_bugs: [],
        parent: null,
        children: [],
        estimated_hours: null,
        proposed_hours: null,
        proposed_by: null,
        logged_hours: 0,
        developer_daily_hours: null,
        development_started_on: null,
        due_on: null,
        late: false,
        sprint: null,
        package: null,
        cross_tested: null,
        archived: null,
        pause_reason: null,
        rework_count: 0,
        support: null,
        created_by: RAFAEL,
        created_at: '2026-10-14 09:00',
        url: `/projetos/1/cards/${partial.key}`,
        history_url: null,
        abilities: ABILITIES_FICHA_NUNCA_USADO,
        ...partial,
    };
}

export const TASK_MINIMO: TaskDetail = detail({ id: 27, key: 'SGM-27', title: 'Aviso de vencimento no app' });

/** A ficha com tudo preenchido: o SGM-17 do quadro, filho do SGM-10, em Doing, com reestimativa proposta e atraso. */
export const TASK_COMPLETO: TaskDetail = detail({
    id: 17,
    key: 'SGM-17',
    title: 'Emitir a guia de IPTU com o código de barras no portal do cidadão',
    type: BOARD_OPTIONS.types[0],
    area: { id: 3, name: 'Portal do cidadão', color: 'sky' },
    priority: 'high',
    stage: DOING,
    developer: BRUNO,
    qa: CARLA,
    participants: [RAFAEL, DIEGO],
    requisition: {
        story_as: 'contribuinte com IPTU em aberto',
        story_want: 'emitir a 2ª via da guia pelo portal, com o código de barras',
        story_so_that: 'pagar no banco ou no app sem ir à prefeitura',
        description:
            'O layout segue o modelo da prefeitura (anexo). A guia vencida sai com juros e multa já calculados. Imagens vão como anexo.',
    },
    criteria: CRITERIA,
    pull_requests: PULL_REQUESTS,
    comments: COMMENTS,
    attachments: ATTACHMENTS,
    cover_attachment_id: 2,
    origin_task: null,
    production_bugs: [{ id: 28, key: 'SGM-28', title: 'Guia emitida sem o valor da multa', url: '/projetos/1/cards/SGM-28' }],
    parent: { id: 10, key: 'SGM-10', title: 'Portal do cidadão: 2ª via de tributos', url: '/projetos/1/cards/SGM-10' },
    estimated_hours: 16,
    proposed_hours: 24,
    proposed_by: BRUNO,
    logged_hours: 11.5,
    developer_daily_hours: 8,
    development_started_on: '2026-10-08',
    due_on: '2026-10-09',
    late: true,
    sprint: { id: 3, name: 'Sprint 3' },
    package: { id: 1, name: 'v1.4.0' },
    cross_tested: { by: ANA, at: '2026-10-12' },
    rework_count: 2,
    created_by: RAFAEL,
    created_at: '2026-10-06 14:20',
    abilities: ABILITIES_FICHA_GESTOR,
});

/** O mesmo card visto pelo dev (Bruno Lima): propõe horas, não arquiva. */
export const TASK_COMPLETO_DEV: TaskDetail = { ...TASK_COMPLETO, abilities: ABILITIES_FICHA_DEV };

/** O card pai: 7 filhos (2 a fazer, 2 em andamento, 3 concluídos), sem dev próprio. */
export const TASK_PAI: TaskDetail = detail({
    id: 10,
    key: 'SGM-10',
    title: 'Portal do cidadão: 2ª via de tributos',
    area: { id: 3, name: 'Portal do cidadão', color: 'sky' },
    stage: DONE,
    developer: RAFAEL,
    requisition: {
        story_as: 'contribuinte',
        story_want: 'tirar a 2ª via de qualquer tributo pelo portal',
        story_so_that: 'não depender do atendimento presencial',
        description: null,
    },
    children: CHILDREN,
    estimated_hours: 4,
    logged_hours: 4,
    sprint: { id: 1, name: 'Sprint 1' },
    comments: [COMMENTS[0]],
    created_at: '2026-09-02 10:00',
    abilities: ABILITIES_FICHA_GESTOR,
});

/** Card arquivado (P26): a ficha abre, a situação mostra quando e por quê; *Desarquivar* no menu. */
export const TASK_ARQUIVADO: TaskDetail = detail({
    id: 9,
    key: 'SGM-9',
    title: 'Importar a base antiga de contribuintes',
    stage: DOING,
    developer: DIEGO,
    estimated_hours: 30,
    logged_hours: 12,
    comments: [COMMENTS[0]],
    archived: { at: '2026-10-01', reason: 'A prefeitura desistiu da importação: vai recadastrar.', by: RAFAEL },
    sprint: { id: 2, name: 'Sprint 2' },
    abilities: ABILITIES_FICHA_GESTOR,
});

/** Card de suporte (P52), em pausa com motivo (P15). */
export const TASK_SUPORTE: TaskDetail = detail({
    id: 24,
    key: 'SGM-24',
    title: 'Contribuinte não consegue abrir a 2ª via no celular',
    type: BOARD_OPTIONS.types[2],
    priority: 'urgent',
    stage: PAUSED,
    developer: DIEGO,
    support: { requester: 'Marcos Vieira', channel: 'WhatsApp', requested_at: '2026-10-13 08:40' },
    pause_reason: 'Aguardando o print do contribuinte',
    comments: [COMMENTS[1]],
    abilities: ABILITIES_FICHA_GESTOR,
});

/** Bug de produção (P25): aponta para a tarefa de origem. */
export const TASK_BUG_PRODUCAO: TaskDetail = detail({
    id: 28,
    key: 'SGM-28',
    title: 'Guia emitida sem o valor da multa',
    type: BOARD_OPTIONS.types[1],
    priority: 'high',
    stage: TODO,
    origin_task: {
        id: 17,
        key: 'SGM-17',
        title: 'Emitir a guia de IPTU com o código de barras no portal do cidadão',
        url: '/projetos/1/cards/SGM-17',
    },
    sprint: { id: 3, name: 'Sprint 3' },
    abilities: ABILITIES_FICHA_GESTOR,
});
