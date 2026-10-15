import type {
    BoardAbilities,
    BoardCard,
    BoardFilters,
    BoardMark,
    BoardOrder,
    BoardProject,
    ChildProgress,
    MoveIntent,
    BoardStage,
    PrioritySlug,
    StageRoleSlug,
} from '@/types/quadro';

/** Ordem por prioridade: Urgente primeiro (F3 §03). */
export const PRIORITY_RANK: Record<PrioritySlug, number> = { urgent: 0, high: 1, normal: 2, low: 3 };

/** Sem prioridade vai depois da Baixa (o card rápido nasce assim, P17, 14/10). */
export const priorityRank = (priority: PrioritySlug | null) => (priority ? PRIORITY_RANK[priority] : 4);

export const PRIORITY_LABEL: Record<PrioritySlug, string> = { low: 'Baixa', normal: 'Normal', high: 'Alta', urgent: 'Urgente' };

export const PRIORITIES: PrioritySlug[] = ['low', 'normal', 'high', 'urgent'];

/**
 * Ordena os cards de uma coluna (P13): por prioridade e, na mesma, pela chave do mais
 * antigo para o mais novo; ou pela posição manual. Devolve cópia.
 */
export function sortCards(cards: BoardCard[], order: BoardOrder): BoardCard[] {
    const byNumber = (card: BoardCard) => Number(card.key.split('-').pop());
    return [...cards].sort((a, b) =>
        order === 'manual'
            ? a.position - b.position || byNumber(a) - byNumber(b)
            : priorityRank(a.priority) - priorityRank(b.priority) || byNumber(a) - byNumber(b),
    );
}

/** Percentual concluído do card pai (P57); 0 sem filhos. */
export function childProgressPercent(progress: ChildProgress | null): number {
    if (!progress) return 0;
    const total = progress.todo + progress.doing + progress.done;
    return total === 0 ? 0 : Math.round((progress.done / total) * 100);
}

/** Papéis de onde a volta para sprint ou desenvolvimento conta retrabalho (P39). */
const REWORK_FROM: StageRoleSlug[] = ['code_review', 'test', 'done'];
const REWORK_TO: StageRoleSlug[] = ['sprint', 'development'];

/** Um passo que a tela precisa cumprir antes de mandar o movimento. */
export type MoveStep = 'new_sprint' | 'pause_reason' | 'rework' | 'done_warning';

export type MoveDecision = { allowed: true; steps: MoveStep[] } | { allowed: false; reason: string };

export const SPRINT_DENIED = 'Só o gestor do projeto e o Administrador põem card na sprint';

/**
 * Classifica um movimento pelas regras do F3 §01/§04, só para a tela saber que diálogo
 * abrir ou que recusa mostrar. O servidor confere tudo de novo (inegociável 9).
 *
 * - Sair do Backlog ou voltar a ele é pôr ou tirar da sprint: gestor e Administrador (P45).
 * - Sair do Backlog sem sprint aberta abre o diálogo *Nova sprint* (R5, F5).
 * - Entrar na pausa pede o motivo quando o projeto exige (P15).
 * - De Code-review, In test ou Done para To do ou Doing é retrabalho: origem e motivo, ou "foi engano" (P39, P40).
 * - Entrar no Done com item do roteiro reprovado ou a testar avisa e deixa seguir (P38).
 * - Mover dentro da mesma coluna é reordenar: nada a perguntar (P13).
 */
export function classifyMove(intent: MoveIntent, project: BoardProject, abilities: BoardAbilities): MoveDecision {
    const { card, from, to } = intent;
    if (from.id === to.id) return { allowed: true, steps: [] };
    if (!abilities.move) return { allowed: false, reason: 'Você não está alocado neste projeto' };

    const steps: MoveStep[] = [];

    if (from.role === 'queue' || to.role === 'queue') {
        if (!abilities.manage_sprint) return { allowed: false, reason: SPRINT_DENIED };
        if (from.role === 'queue' && !project.open_sprint) steps.push('new_sprint');
    }

    if (to.role === 'paused' && project.pause_requires_reason) steps.push('pause_reason');

    if (REWORK_FROM.includes(from.role) && REWORK_TO.includes(to.role)) steps.push('rework');

    if (to.role === 'done' && card.qa_summary && (card.qa_summary.rejected > 0 || card.qa_summary.pending > 0)) steps.push('done_warning');

    return { allowed: true, steps };
}

/** Texto do aviso ao mover para o Pronto com roteiro pendente (P38). */
export function doneWarningText(summary: NonNullable<BoardCard['qa_summary']>): string {
    const rejected = summary.rejected === 1 ? '1 item reprovado' : `${summary.rejected} itens reprovados`;
    return `O roteiro tem ${rejected} e ${summary.pending} a testar. Mover mesmo assim?`;
}

/** Horas com decimais no padrão da tela: 12 → "12 h", 1.5 → "1,5 h". */
export function hours(value: number): string {
    return `${value.toLocaleString('pt-BR', { maximumFractionDigits: 2 })} h`;
}

/** Rótulo de cada marca no filtro do quadro (F3 §05), na ordem em que aparecem. */
export const BOARD_MARK_LABEL: Record<BoardMark, string> = {
    late: 'Atrasado',
    pause: 'Em pausa',
    rework: 'Com retrabalho',
    support: 'Suporte',
    proposed: 'Reestimativa proposta',
};

/** Algum filtro que esconde card está ligado (busca, pessoas, listas, marcas). Prontos antigos e Arquivados mostram mais, não menos. */
export function hasActiveFilters(filters: BoardFilters): boolean {
    return Boolean(
        filters.search.trim() ||
        filters.mine ||
        filters.developer_id ||
        filters.qa_id ||
        filters.type_id ||
        filters.area_id ||
        filters.priority ||
        filters.marks.length > 0,
    );
}

/** Minúsculas e sem acento: "Integração" acha por "integracao". */
const fold = (text: string) =>
    text
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLocaleLowerCase('pt-BR');

function hasMark(card: BoardCard, mark: BoardMark, stageRole: StageRoleSlug | undefined): boolean {
    switch (mark) {
        case 'late':
            return card.late;
        case 'pause':
            return stageRole === 'paused';
        case 'rework':
            return card.rework_count > 0;
        case 'support':
            return card.type?.slug === 'support';
        case 'proposed':
            return card.proposed_hours !== null;
    }
}

/**
 * Filtra os cards do quadro no navegador (F3 §05). **Só para o Storybook e para o filtro
 * instantâneo; o servidor é a fonte**: o controller do B3 devolve os cards já filtrados, e aplicar
 * de novo aqui não muda nada. *Mostrar prontos antigos* e *Arquivados* dependem de dado que o
 * quadro não tem (sprint do pronto, cards arquivados) e ficam só com o servidor.
 *
 * - Busca: chave (`SGM-17`, `sgm-17`) ou trecho do título, sem diferença de acento nem maiúscula.
 * - *Só os meus*: `currentUserId` é dev, QA ou participante (P58).
 * - Dev, QA, Tipo, Área, Prioridade: igualdade.
 * - Marcas: o card tem **qualquer uma** das marcas escolhidas; a pausa é estar numa etapa de papel `paused` (P15).
 */
export function applyFilters(cards: BoardCard[], filters: BoardFilters, currentUserId: number | null, stages: BoardStage[]): BoardCard[] {
    const term = fold(filters.search.trim());
    const roleOf = new Map(stages.map((stage) => [stage.id, stage.role]));

    return cards.filter((card) => {
        if (term && !fold(card.key).includes(term) && !fold(card.title).includes(term)) return false;
        if (filters.mine) {
            const people = [card.developer, card.qa, ...card.participants];
            if (currentUserId === null || !people.some((person) => person?.id === currentUserId)) return false;
        }
        if (filters.developer_id && card.developer?.id !== filters.developer_id) return false;
        if (filters.qa_id && card.qa?.id !== filters.qa_id) return false;
        if (filters.type_id && card.type?.id !== filters.type_id) return false;
        if (filters.area_id && card.area?.id !== filters.area_id) return false;
        if (filters.priority && card.priority !== filters.priority) return false;
        if (filters.marks.length > 0 && !filters.marks.some((mark) => hasMark(card, mark, roleOf.get(card.stage_id)))) return false;
        return true;
    });
}
