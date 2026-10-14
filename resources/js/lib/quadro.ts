import type {
    BoardAbilities,
    BoardCard,
    BoardOrder,
    BoardProject,
    ChildProgress,
    MoveIntent,
    PrioritySlug,
    StageRoleSlug,
} from '@/types/quadro';

/** Ordem por prioridade: Urgente primeiro (F3 §03). */
export const PRIORITY_RANK: Record<PrioritySlug, number> = { urgent: 0, high: 1, normal: 2, low: 3 };

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
            : PRIORITY_RANK[a.priority] - PRIORITY_RANK[b.priority] || byNumber(a) - byNumber(b),
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
