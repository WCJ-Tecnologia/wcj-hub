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

/** Área do projeto (P18): a "etiqueta" do card, uma por card. */
export interface AreaRef {
    id: number;
    name: string;
}

/** Tipo do card (P18): `slug` só nos itens que o código conhece (`improvement`, `support`). */
export interface TaskTypeRef {
    id: number;
    slug: string | null;
    label: string;
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
    priority: PrioritySlug;
    type: TaskTypeRef;
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
    /** Tipos de card ativos, lista geral (P18): opções do filtro *Tipo* do quadro (F3 §05). */
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
