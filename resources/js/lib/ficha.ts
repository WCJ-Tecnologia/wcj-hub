/**
 * Regras de tela da ficha do card (F3 §02 a §04, F5 §02), puras: textos, contagens e prévias que
 * a tela mostra antes de mandar ao servidor. **Nada aqui decide regra**: quem recusa é o servidor
 * (inegociável 9); aqui só se escolhe o aviso e a confirmação.
 */
import { addBusinessDays } from '@/lib/dias-uteis';
import { dateBr } from '@/lib/utils';
import type {
    AcceptanceCriterion,
    BoardCard,
    ChildCard,
    ChildProgress,
    CriterionResult,
    TaskAttachment,
    TaskDetail,
    TaskPickRef,
    TaskPullRequest,
} from '@/types/quadro';

/* ---------- Textos fixos do F3 §04 (a mesma frase na tela e no teste) ---------- */

export const PR_ALREADY_TEXT = 'Este PR já está no card';
export const CRITERION_RETEST_TEXT = 'Este critério volta para A testar';
export const REMOVE_ATTACHMENT_TEXT = 'Remover o anexo? O arquivo será apagado.';
export const DELETE_COMMENT_TEXT = 'Excluir o comentário?';
export const HISTORY_SOON_TEXT = 'Em breve (B7)';

/** "Excluir CA_3? O número não será reaproveitado." (F3 §04). */
export const deleteCriterionText = (number: string) => `Excluir ${number}? O número não será reaproveitado.`;

/** "Excluir de verdade. Não dá para desfazer. A chave SGM-17 não volta a ser usada." (F3 §04, P26). */
export const deleteTaskText = (key: string) => `Excluir de verdade. Não dá para desfazer. A chave ${key} não volta a ser usada.`;

/** "Fulano será avisado no sistema e por e-mail" (F3 §04, P22). */
export const assignNoticeText = (name: string) => `${name} será avisado no sistema e por e-mail`;

/** "Arquivar SGM-17? Ele sai do quadro e volta pelo filtro Arquivados." (F3 §04). */
export const archiveText = (key: string) => `Arquivar ${key}? Ele sai do quadro e volta pelo filtro Arquivados.`;

/* ---------- Datas e números ---------- */

/** `AAAA-MM-DD HH:MM` (fuso de São Paulo, como o servidor manda) → "DD/MM/AAAA HH:MM", sem passar por `Date`. */
export function dateTimeBr(value: string | null | undefined): string {
    if (!value) return '—';
    const [date, time] = value.split(' ');
    return time ? `${dateBr(date)} ${time}` : dateBr(date);
}

/** `AAAA-MM-DD` → "DD/MM", para a frase da reestimativa (F3 §04). */
export function dayMonth(value: string): string {
    const [, month, day] = value.split('-');
    return `${day}/${month}`;
}

/** Bytes → "920 B", "1,2 KB", "3,5 MB". */
export function formatBytes(size: number): string {
    if (size < 1024) return `${size} B`;
    const units = ['KB', 'MB', 'GB'];
    let value = size / 1024;
    let index = 0;
    while (value >= 1024 && index < units.length - 1) {
        value /= 1024;
        index++;
    }
    return `${value.toLocaleString('pt-BR', { maximumFractionDigits: 1 })} ${units[index]}`;
}

/* ---------- Situação e só leitura (F3 §02) ---------- */

/** "Testado por Fulano em DD/MM/AAAA" ou "—" (P34). */
export const crossTestedText = (crossTested: TaskDetail['cross_tested']) =>
    crossTested ? `Testado por ${crossTested.by.name} em ${dateBr(crossTested.at)}` : '—';

/** "Ativo" ou "Arquivado em DD/MM/AAAA: motivo" (P26). */
export const situationText = (archived: TaskDetail['archived']) =>
    archived ? `Arquivado em ${dateBr(archived.at)}: ${archived.reason}` : 'Ativo';

/** O documento de requisição está vazio nos quatro campos (P17). */
export const requisitionIsEmpty = (requisition: TaskDetail['requisition']) =>
    !requisition.story_as?.trim() &&
    !requisition.story_want?.trim() &&
    !requisition.story_so_that?.trim() &&
    !requisition.description?.trim();

/* ---------- Critérios de aceite (F3 §02, P17, P36, P37, T1, T9) ---------- */

export const CRITERION_RESULT_LABEL: Record<CriterionResult, string> = { pending: 'A testar', approved: 'Aprovado', failed: 'Reprovado' };

/** Excluir só enquanto o critério e cada subitem dele estão A testar (F3 §04, T1); testado, o botão não aparece. */
export function criterionDeletable(criterion: AcceptanceCriterion): boolean {
    return criterion.result === 'pending' && criterion.children.every((child) => child.result === 'pending');
}

/** Quantos critérios e subitens o card tem, para o resumo da seção. */
export function countCriteria(criteria: AcceptanceCriterion[]): number {
    return criteria.reduce((total, criterion) => total + 1 + criterion.children.length, 0);
}

/**
 * O resumo do roteiro para o aviso do Done (P38), como o `TaskPresenter` manda em `qa_summary`:
 * reprovados e a testar, contando os subitens; null sem critério (nada a avisar).
 */
export function qaSummaryOf(criteria: AcceptanceCriterion[]): BoardCard['qa_summary'] {
    const all = criteria.flatMap((criterion) => [criterion, ...criterion.children]);
    if (all.length === 0) return null;
    return {
        rejected: all.filter((item) => item.result === 'failed').length,
        pending: all.filter((item) => item.result === 'pending').length,
    };
}

/* ---------- PRs (P23) ---------- */

/** Dois links iguais a menos de espaço nas pontas e da barra final são o mesmo PR (`unique (task_id, url)`). */
export function normalizeUrl(url: string): string {
    return url.trim().replace(/\/+$/, '');
}

/** O link já está no card (F3 §04: "Este PR já está no card"). */
export function hasPullRequest(pullRequests: Pick<TaskPullRequest, 'url'>[], url: string): boolean {
    const wanted = normalizeUrl(url);
    return pullRequests.some((pr) => normalizeUrl(pr.url) === wanted);
}

/* ---------- Horas e data prevista (F5 §02, P27, P28) ---------- */

/** Previstas − lançadas, nunca abaixo de zero; null sem horas previstas (F5 §02, "Faltam"). */
export function remainingHours(estimated: number | null, logged: number): number | null {
    if (estimated === null) return null;
    return Math.max(0, estimated - logged);
}

/**
 * Prévia da data prevista com outras horas (P28): horas ÷ carga diária do dev, arredondado para cima,
 * em dias úteis seg–sex a partir da 1ª entrada em desenvolvimento, que conta como o 1º dia. **A conta
 * que vale é a do servidor** (`DueDateService`); esta só preenche a frase de confirmação.
 * null quando o card nunca entrou em Doing, não tem dev com carga ou as horas não valem.
 */
export function previewDueOn(detail: Pick<TaskDetail, 'development_started_on' | 'developer_daily_hours'>, hours: number): string | null {
    if (!detail.development_started_on || !detail.developer_daily_hours || detail.developer_daily_hours <= 0) return null;
    if (!Number.isFinite(hours) || hours <= 0) return null;
    const days = Math.max(1, Math.ceil(hours / detail.developer_daily_hours));
    return addBusinessDays(detail.development_started_on, days);
}

/** "A data prevista vai de DD/MM para DD/MM. Confirmar?" (F3 §04); null quando a data não muda ou não existe. */
export function dueChangeText(from: string | null, to: string | null): string | null {
    if (!from || !to || from === to) return null;
    return `A data prevista vai de ${dayMonth(from)} para ${dayMonth(to)}. Confirmar?`;
}

/** "Proposta: 12 h, aguardando o gestor" (F3 §02). */
export const proposalText = (hours: string) => `Proposta: ${hours}, aguardando o gestor`;

/* ---------- Card pai e cards filhos (P57) ---------- */

/**
 * Progresso dos filhos pela coluna de cada um (P57), a mesma leitura do `ChildProgressQuery` do B3:
 * `done` = concluído; `queue` e `sprint` = a fazer; o resto (`development`, `paused`, `code_review`,
 * `test`, `support`) = em andamento. null sem filhos.
 */
export function childProgressOf(children: ChildCard[]): ChildProgress | null {
    if (children.length === 0) return null;
    const progress: ChildProgress = { todo: 0, doing: 0, done: 0 };
    children.forEach((child) => {
        if (child.stage.role === 'done') progress.done++;
        else if (child.stage.role === 'queue' || child.stage.role === 'sprint') progress.todo++;
        else progress.doing++;
    });
    return progress;
}

/** "2 a fazer · 1 em andamento · 3 concluídos" (F3 §02). */
export function childrenSummaryText(progress: ChildProgress): string {
    return `${progress.todo} a fazer · ${progress.doing} em andamento · ${progress.done} ${progress.done === 1 ? 'concluído' : 'concluídos'}`;
}

/** Para que serve a busca de cards da ficha: card pai (P57) ou tarefa de origem (P25). */
export type TaskPickMode = 'parent' | 'origin';

/** Minúsculas e sem acento, como no filtro do quadro. */
const fold = (text: string) =>
    text
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLocaleLowerCase('pt-BR');

/**
 * Por que este card não pode ser escolhido, com a frase do F3 §04; null quando pode.
 * Tarefa de origem aceita qualquer card do projeto (menos o próprio, que `options.cards` já não traz).
 */
export function pickExclusionText(card: TaskPickRef, mode: TaskPickMode): string | null {
    if (mode !== 'parent') return null;
    if (card.has_children) return `${card.key} já é pai de outros cards; um nível só.`;
    if (card.parent_key) return `${card.key} já é filho de ${card.parent_key}.`;
    return null;
}

/**
 * Busca por chave ou trecho do título (F3 §05), sem diferença de acento nem maiúscula, no máximo
 * `limit` resultados. Os cards que a regra exclui ficam **na lista, marcados** com `exclusion`,
 * para a pessoa ler o motivo em vez de não achar o card.
 */
export function pickCards(
    cards: TaskPickRef[],
    term: string,
    mode: TaskPickMode,
    limit = 8,
): { card: TaskPickRef; exclusion: string | null }[] {
    const wanted = fold(term.trim());
    if (!wanted) return [];
    return cards
        .filter((card) => fold(card.key).includes(wanted) || fold(card.title).includes(wanted))
        .slice(0, limit)
        .map((card) => ({ card, exclusion: pickExclusionText(card, mode) }));
}

/* ---------- Anexos e capa (P59) ---------- */

/** A capa atual, ou null. */
export function coverOf(attachments: TaskAttachment[], coverId: number | null): TaskAttachment | null {
    return attachments.find((attachment) => attachment.id === coverId) ?? null;
}

/* ---------- Ponte com o kit do quadro ---------- */

/**
 * A ficha como `BoardCard`, para reusar o que o quadro já tem: `useBoardMove` (*Mover para…*, com o
 * `classifyMove` e os diálogos) e o `WArchiveDialogOrganism`. `position` e contadores são os da ficha.
 */
export function detailToBoardCard(detail: TaskDetail): BoardCard {
    return {
        id: detail.id,
        key: detail.key,
        title: detail.title,
        stage_id: detail.stage.id,
        position: 0,
        priority: detail.priority,
        type: detail.type,
        area: detail.area,
        parent: detail.parent,
        cover_url: coverOf(detail.attachments, detail.cover_attachment_id)?.url ?? null,
        estimated_hours: detail.estimated_hours,
        proposed_hours: detail.proposed_hours,
        developer: detail.developer,
        qa: detail.qa,
        participants: detail.participants,
        due_on: detail.due_on,
        late: detail.late,
        attachments_count: detail.attachments.length,
        comments_count: detail.comments.length,
        children: childProgressOf(detail.children),
        pause_reason: detail.pause_reason,
        rework_count: detail.rework_count,
        support_requester: detail.support?.requester ?? null,
        qa_summary: qaSummaryOf(detail.criteria),
        url: detail.url,
    };
}
