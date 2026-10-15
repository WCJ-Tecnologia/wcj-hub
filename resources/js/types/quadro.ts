/**
 * Contrato do quadro (F3) entre o front e o `TaskPresenter` do B3.
 *
 * O servidor manda exatamente estas formas (snake_case, como os Presenters do Núcleo);
 * os componentes do quadro só leem. Regra de negócio vale no servidor (inegociável 9):
 * `can`/abilities aqui só escondem botão, e `classifyMove` (lib/quadro.ts) só decide
 * qual diálogo abrir antes de mandar o movimento.
 */

/** Espelho de `stage_roles.slug` (banco.md): o papel da etapa, nunca o nome (P11). */
export type StageRoleSlug = 'queue' | 'sprint' | 'development' | 'code_review' | 'test' | 'done' | 'paused' | 'support';

/** Espelho de `task_priorities.slug` (P19). */
export type PrioritySlug = 'low' | 'normal' | 'high' | 'urgent';

/** Seletor *Ordem: Prioridade | Manual* (P13); lembrado no navegador por pessoa. */
export type BoardOrder = 'priority' | 'manual';

/** Uma coluna do quadro: etapa ativa do projeto (`board_stages`). */
export interface BoardStage {
    id: number;
    name: string;
    role: StageRoleSlug;
    position: number;
}

export interface PersonRef {
    id: number;
    name: string;
}

/**
 * Cor da etiqueta de tipo e de área (decisão de Lucas Scholz em 14/10, como no Trello): paleta fixa
 * de 10, escolhida no cadastro da lista geral (tipos) e na configuração do projeto (áreas). Espelho das
 * colunas `task_types.color` e `project_areas.color` (varchar(10), null; pendente da conferência do
 * Lucas Alencar). null = etiqueta sem cor (cinza neutro).
 */
export type TagColor = 'gray' | 'blue' | 'sky' | 'green' | 'lime' | 'yellow' | 'orange' | 'red' | 'pink' | 'purple';

/** Área do projeto (P18): a "etiqueta" do card, uma por card. */
export interface AreaRef {
    id: number;
    name: string;
    color: TagColor | null;
}

/**
 * Tipo do card (P18): **do projeto** (decisão de 14/10): todo projeto nasce com os padrão (Melhoria,
 * Correção de bug, Suporte, Mudança (GMUD), Desenvolvimento) e cria ou edita os seus sem propagar.
 * `slug` só nos itens que o código conhece (`improvement`, `support`, `development`), um por projeto. O card criado
 * só com o título nasce **sem tipo** (P17, decisão de 14/10): quem for editar define na ficha ou pela etiqueta.
 */
export interface TaskTypeRef {
    id: number;
    slug: string | null;
    label: string;
    color: TagColor | null;
}

/** Card pai (P57), um nível. */
export interface ParentRef {
    id: number;
    key: string;
    title: string;
    url: string;
}

/** Progresso dos filhos pelo papel da etapa (P57): a fazer, em andamento, concluídos. */
export interface ChildProgress {
    todo: number;
    doing: number;
    done: number;
}

/** O card como aparece no quadro (F3 §03). Tudo que é "quando aparece" vem nulo ou zero quando não há. */
export interface BoardCard {
    id: number;
    /** `SGM-17` = prefixo do projeto + número (P16). */
    key: string;
    title: string;
    stage_id: number;
    /** Ordem manual na coluna (P13); a ordem por prioridade não lê isto. */
    position: number;
    /** null = sem prioridade: o card rápido nasce assim (P17, decisão de 14/10) até alguém definir. */
    priority: PrioritySlug | null;
    /** null = sem tipo: o card rápido nasce assim (P17, 14/10). */
    type: TaskTypeRef | null;
    area: AreaRef | null;
    parent: ParentRef | null;
    /** URL da capa (anexo de imagem escolhido, P59); null = sem imagem. */
    cover_url: string | null;
    estimated_hours: number | null;
    /** Reestimativa proposta pelo dev, à espera do gestor (P27). */
    proposed_hours: number | null;
    developer: PersonRef | null;
    qa: PersonRef | null;
    /** Além do dev e do QA (P58). */
    participants: PersonRef[];
    /** Data prevista `AAAA-MM-DD` (P28); null quando não calculada. */
    due_on: string | null;
    /** Passou da data prevista sem chegar ao Pronto, descontada a pausa (P53, R16): a conta é do servidor. */
    late: boolean;
    attachments_count: number;
    comments_count: number;
    /** Só no card pai; filho arquivado não conta (P57). */
    children: ChildProgress | null;
    /** Só quando a etapa é de pausa: o motivo do último movimento, ou null sem motivo (P15). */
    pause_reason: string | null;
    /** Quantas vezes o card já voltou (P39). */
    rework_count: number;
    /** Card de suporte: quem pediu (P52); contato anonimizado vem como "removido a pedido do titular" (N12). */
    support_requester: string | null;
    /** Roteiro de QA (F4, B4): null até o B4; com valor, mover para o Pronto avisa (P38). */
    qa_summary: { rejected: number; pending: number } | null;
    /** URL da ficha: *Abrir ficha* e *Copiar link*. */
    url: string;
}

/** Decidido pela `TaskPolicy`/`ProjectPolicy` no servidor; a tela só esconde botão. */
export interface BoardAbilities {
    /** Alocado, projeto aberto (P56, P7). */
    create_card: boolean;
    /** Gestor do projeto e Administrador, projeto aberto (P52, P7). */
    create_support: boolean;
    /** Gestor do projeto e Administrador: tirar do Backlog e levar de volta (P45). */
    manage_sprint: boolean;
    /** Alocado: mover a partir do To do e do Support (P13). */
    move: boolean;
    /** Gestor do projeto e Administrador: arquivar e excluir (P26). */
    archive: boolean;
    /** Gestor do projeto e Administrador: criar tipo e editar nome e cor pela etiqueta do card, só neste projeto (14/10). */
    manage_types: boolean;
    /** Gestor do projeto e Administrador: criar área e editar nome e cor pela etiqueta do card, ao estilo Trello (14/10). */
    manage_areas: boolean;
}

/** O projeto, como o quadro precisa dele. */
export interface BoardProject {
    id: number;
    key_prefix: string;
    name: string;
    /** Encerrado não recebe card novo (P7); mover, comentar e anexar seguem. */
    finished: boolean;
    /** F2: a pausa pede motivo (P15). */
    pause_requires_reason: boolean;
    /** Sprint aberta (P45); null abre o diálogo *Nova sprint* ao arrastar para o To do (R5, F5). */
    open_sprint: { id: number; name: string } | null;
    /** Duração padrão da sprint em dias úteis (P46), para preencher o diálogo. */
    sprint_days: number;
    /** Quantas sprints o projeto já teve: "Sprint N+1" é o nome proposto (F5 §02). */
    sprints_count: number;
}

/** Opções das listas que o quadro e os diálogos usam. */
export interface BoardOptions {
    areas: AreaRef[];
    /** Tipos de card ativos do projeto (P18, 14/10): filtro *Tipo* e o popover da etiqueta. */
    types: TaskTypeRef[];
    /** Pessoas alocadas e ativas (P20, P58). */
    members: PersonRef[];
    /** Motivos de retrabalho ativos, lista geral (P40); `slug === 'other'` pede texto. */
    rework_reasons: { id: number; slug: string | null; label: string }[];
    /** Origem do retrabalho (`validation_types`, P39). */
    rework_origins: { id: number; label: string }[];
    /** Contatos ativos do cliente do projeto, para o card de suporte (P52). */
    contacts: PersonRef[];
    /** Por onde o pedido chegou (`contact_channels`, P52). */
    channels: { id: number; label: string }[];
}

/**
 * A intenção de mover, antes de qualquer diálogo. Do arrastar, `index` é a posição em que o card foi
 * solto na coluna de destino e `sequence` os ids da coluna na ordem que ficou na tela (como no
 * Trello: o card cai onde foi solto, P13; decisão de Lucas Scholz em 14/10). Do *Mover para…*, os dois
 * vêm nulos e o card entra no fim.
 */
export interface MoveIntent {
    card: BoardCard;
    from: BoardStage;
    to: BoardStage;
    index: number | null;
    sequence: number[] | null;
}

/** O que a tela manda ao servidor depois dos diálogos (`POST /projetos/{id}/cards/{chave}/mover`, proposta do kit). */
export interface MovePayload {
    stage_id: number;
    /** Posição em que o card foi solto (índice a partir de 0); vem junto de `sequence`. */
    position?: number;
    /** Ids dos cards da coluna de destino na ordem final: o servidor renumera `position` 1..n nessa ordem (P13). */
    sequence?: number[];
    pause_reason?: string;
    /** Retrabalho (P39, P40). `mistake` = "foi engano": grava o movimento sem origem nem motivo. */
    mistake?: boolean;
    rework_origin_id?: number;
    rework_reason_id?: number;
    rework_reason_text?: string;
    /** Sem sprint aberta: a sprint proposta, criada na mesma transação (R5). */
    new_sprint?: { name: string; goal: string; starts_on: string; ends_on: string };
}

/** As marcas do card que o filtro do quadro conhece (F3 §05); os ids são os do `WTaskMarksMolecule`. */
export type BoardMark = 'late' | 'pause' | 'rework' | 'support' | 'proposed';

/**
 * Filtros do quadro (F3 §05), como o controller do B3 lê da URL e devolve em `filters`.
 * `null`, `''`, `[]` e `false` = sem filtro. A filtragem é do servidor; a tela repete no
 * cliente só para o retorno instantâneo (`applyFilters`, lib/quadro.ts).
 */
export interface BoardFilters {
    /** Chave (`SGM-17`) ou trecho do título. */
    search: string;
    /** *Só os meus*: sou dev, QA ou participante (P58). */
    mine: boolean;
    developer_id: number | null;
    qa_id: number | null;
    type_id: number | null;
    area_id: number | null;
    priority: PrioritySlug | null;
    /** Cards com qualquer uma das marcas escolhidas. */
    marks: BoardMark[];
    /** *Mostrar prontos antigos*: prontos de sprints anteriores à última encerrada (P13). Só o servidor sabe. */
    old_done: boolean;
    /** *Arquivados*: troca o quadro pela lista de arquivados (P26). */
    archived: boolean;
}

/* ------------------------------------------------------------------------------------------------
 * Ficha do card (F3 §02 "Card (ficha)", §03 "Dentro da ficha", §04 "Na ficha"; F5 §02 "Horas e data
 * prevista"). O que o `TaskPresenter::detail()` do B3 manda para `/projetos/{id}/cards/{chave}`.
 * Kit da ficha, sem backend: o Cauã confirma as formas e as rotas (topo de `w-task-detail-page.tsx`).
 * ---------------------------------------------------------------------------------------------- */

/** Espelho de `test_results.slug` (P36): o resultado do critério, marcado pelo QA em F4. */
export type CriterionResult = 'pending' | 'approved' | 'failed';

/**
 * Critério de aceite `CA_n`, com os subitens `CA_n.m` logo abaixo (um nível, P17, T9).
 * `number` já vem montado pelo servidor ("CA_3", "CA_4.1"); excluir não renumera.
 */
export interface AcceptanceCriterion {
    id: number;
    /** "CA_3" no critério, "CA_4.1" no subitem. */
    number: string;
    description: string;
    result: CriterionResult;
    /** Só no critério; o subitem vem com `[]`. */
    children: AcceptanceCriterion[];
}

/** Link de PR colado no card (P23). */
export interface TaskPullRequest {
    id: number;
    url: string;
    added_by: PersonRef;
    /** `AAAA-MM-DD HH:MM` no fuso de São Paulo. */
    added_at: string;
}

/** Comentário (P24): só o autor edita e exclui o próprio (F3 §10.10); o servidor decide pelo `can`. */
export interface TaskComment {
    id: number;
    author: PersonRef;
    body: string;
    /** `AAAA-MM-DD HH:MM` no fuso de São Paulo. */
    created_at: string;
    /** Quando foi editado, ou null (o antes fica na auditoria). */
    edited_at: string | null;
    can: { update: boolean; delete: boolean };
}

/** Anexo (P24): download com o nome original; só imagem pode ser a capa (P59). */
export interface TaskAttachment {
    id: number;
    /** `original_name`: o nome no download. */
    name: string;
    /** Tamanho em bytes, para a tela mostrar "1,2 MB". */
    size: number;
    /** `mime_type` começa com `image/`: mostra *Usar como capa* / *Tirar a capa*. */
    is_image: boolean;
    /** URL de download (e de miniatura, quando imagem). */
    url: string;
    uploaded_by: PersonRef;
    /** `AAAA-MM-DD HH:MM` no fuso de São Paulo. */
    uploaded_at: string;
    /** Evidência do QA ligada a um critério (F4); null = anexo comum. */
    criterion_id: number | null;
}

/** Outro card do mesmo projeto, como link: tarefa de origem, bug de produção ligado, card pai. */
export interface TaskRef {
    id: number;
    key: string;
    title: string;
    url: string;
}

/**
 * Um card do projeto como opção das buscas da ficha (card pai e tarefa de origem, F3 §05):
 * a lista inteira vem em `options`, leve, e a tela filtra. Card arquivado não vem.
 */
export interface TaskPickRef extends TaskRef {
    /** Já é filho de alguém: não pode ser pai (P57). `parent_key` = a chave do pai, para a mensagem. */
    parent_key: string | null;
    /** Já tem filhos: não pode ser filho (P57). */
    has_children: boolean;
}

/** Card filho na ficha do pai (F3 §02): chave, título, coluna e dev; arquivado não vem (P57). */
export interface ChildCard extends TaskRef {
    stage: BoardStage;
    developer: PersonRef | null;
}

/** Card de suporte: o pedido do cliente (P52). */
export interface SupportRequest {
    /** Nome do contato; anonimizado vem como "removido a pedido do titular" (N12). */
    requester: string;
    channel: string;
    /** `AAAA-MM-DD HH:MM` no fuso de São Paulo. */
    requested_at: string;
}

/** Decidido pela `TaskPolicy`/`ProjectPolicy` no servidor; a ficha só esconde botão (inegociável 9). */
export interface TaskAbilities {
    /** Alocado (F3 §09): título, tipo, área, prioridade, dev, QA, requisição, critérios, PRs, origem, pai, participantes, capa. */
    update: boolean;
    /** Alocado: comentar e anexar (P24). */
    comment: boolean;
    attach: boolean;
    /** Gestor do projeto e Administrador: definir ou reestimar as horas direto (P27). */
    set_hours: boolean;
    /** Desenvolvedor alocado: propor um valor novo (P27). */
    propose_hours: boolean;
    /** Gestor do projeto e Administrador: aprovar ou recusar a proposta (P27). */
    decide_hours: boolean;
    /** Alocado: *Mover para…* (P13); sair do Backlog ou voltar a ele precisa de `manage_sprint`. */
    move: boolean;
    manage_sprint: boolean;
    /** Gestor do projeto e Administrador: arquivar e desarquivar (P26). */
    archive: boolean;
    /** Gestor do projeto e Administrador, e só o card nunca usado (P26): com histórico o servidor manda false. */
    delete: boolean;
    /** Gestor do projeto e Administrador: criar tipo e editar nome e cor pela etiqueta da ficha (14/10). */
    manage_types: boolean;
    /** Gestor do projeto e Administrador: criar área e editar nome e cor pela etiqueta da ficha (14/10). */
    manage_areas: boolean;
}

/** A ficha inteira (F3 §02). Tudo que é "quando há" vem null ou `[]`. */
export interface TaskDetail {
    id: number;
    key: string;
    title: string;
    /** null = sem tipo (o card rápido nasce assim, P17, 14/10). */
    type: TaskTypeRef | null;
    area: AreaRef | null;
    /** null = sem prioridade (idem). */
    priority: PrioritySlug | null;
    /** A etapa atual, com o papel: decide a marca de pausa e sai do *Mover para…* (P11). */
    stage: BoardStage;
    developer: PersonRef | null;
    qa: PersonRef | null;
    participants: PersonRef[];
    /** Documento de requisição (P17): Sendo / Quero / Para / Informações adicionais, as colunas de `tasks`. */
    requisition: {
        story_as: string | null;
        story_want: string | null;
        story_so_that: string | null;
        description: string | null;
    };
    /** Na ordem `CA_1, CA_2…`, subitens dentro do pai (F3 §03). */
    criteria: AcceptanceCriterion[];
    /** Na ordem em que entraram. */
    pull_requests: TaskPullRequest[];
    /** Do mais antigo para o mais novo. */
    comments: TaskComment[];
    /** Na ordem em que entraram. */
    attachments: TaskAttachment[];
    /** `tasks.cover_attachment_id` (P59); null = sem capa. */
    cover_attachment_id: number | null;
    /** Bug de produção: a tarefa original (P25). */
    origin_task: TaskRef | null;
    /** No card original: os bugs de produção que apontam para ele (F3 §03). */
    production_bugs: TaskRef[];
    parent: ParentRef | null;
    /** Só no card pai; vazio no resto (P57). */
    children: ChildCard[];
    /** Horas previstas, total dev + QA (P27). */
    estimated_hours: number | null;
    /** Reestimativa proposta pelo dev (P27), com quem propôs. */
    proposed_hours: number | null;
    proposed_by: PersonRef | null;
    /** Soma dos lançamentos de qualquer pessoa (P30; F5 §02). */
    logged_hours: number;
    /** Carga diária do dev (`users.daily_hours`, N3): a prévia da data prevista ao reestimar (P28). null sem dev ou sem carga. */
    developer_daily_hours: number | null;
    /** Dia da 1ª entrada em etapa de desenvolvimento, `AAAA-MM-DD` (P28); null = nunca entrou em Doing. */
    development_started_on: string | null;
    /** Data prevista `AAAA-MM-DD` (P28); null quando não calculada. */
    due_on: string | null;
    /** Passou da data prevista sem chegar ao Pronto (P53, R16): conta do servidor. */
    late: boolean;
    /** Nome da sprint, ou null = "Fora de sprint" (P45). */
    sprint: { id: number; name: string } | null;
    /** Nome da versão, ou null (P41; F6). */
    package: { id: number; name: string } | null;
    /** "Testado por Fulano em DD/MM/AAAA" (P34); null = sem teste cruzado. `at` em `AAAA-MM-DD`. */
    cross_tested: { by: PersonRef; at: string } | null;
    /** Arquivado (P26): quando (`AAAA-MM-DD`), motivo e quem; null = Ativo. */
    archived: { at: string; reason: string; by: PersonRef } | null;
    /** Só quando a etapa é de pausa: o motivo do último movimento (P15). */
    pause_reason: string | null;
    rework_count: number;
    /** Card de suporte (P52); null nos demais. */
    support: SupportRequest | null;
    /** Quem criou e quando (`AAAA-MM-DD HH:MM`). */
    created_by: PersonRef;
    created_at: string;
    /** URL da ficha (*Copiar link*). */
    url: string;
    /** URL da linha do tempo (F7, B7); null até o B7 existir: o botão fica desabilitado. */
    history_url: string | null;
    abilities: TaskAbilities;
}

/** Opções que a ficha usa além das do quadro (`BoardOptions`). */
export interface TaskDetailOptions extends Pick<BoardOptions, 'areas' | 'types' | 'members' | 'rework_origins' | 'rework_reasons'> {
    /** Etapas ativas do projeto, na ordem: o *Mover para…* (P13). */
    stages: BoardStage[];
    /** Os cards do projeto, para as buscas de card pai e tarefa de origem (F3 §05); sem os arquivados e sem este card. */
    cards: TaskPickRef[];
}

/** O que a ficha manda no `PATCH /projetos/{id}/cards/{chave}` (proposta do kit): só os campos tocados. */
export interface TaskPatchPayload {
    title?: string;
    type_id?: number;
    area_id?: number | null;
    priority?: PrioritySlug;
    developer_id?: number | null;
    qa_id?: number | null;
    story_as?: string | null;
    story_want?: string | null;
    story_so_that?: string | null;
    description?: string | null;
    origin_task_id?: number | null;
    parent_task_id?: number | null;
    cover_attachment_id?: number | null;
    /** Só gestor e Administrador (P27); o dev propõe por outra rota. */
    estimated_hours?: number | null;
}
