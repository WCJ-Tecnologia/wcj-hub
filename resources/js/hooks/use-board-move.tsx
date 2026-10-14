import { type ReactNode, useState } from 'react';

import { WConfirmDialogMolecule } from '@/components/molecules/w-confirm-dialog-molecule/w-confirm-dialog-molecule';
import { WNewSprintDialogOrganism } from '@/components/organisms/w-new-sprint-dialog-organism/w-new-sprint-dialog-organism';
import { WPauseReasonDialogOrganism } from '@/components/organisms/w-pause-reason-dialog-organism/w-pause-reason-dialog-organism';
import { WReworkDialogOrganism } from '@/components/organisms/w-rework-dialog-organism/w-rework-dialog-organism';
import { classifyMove, doneWarningText, type MoveStep } from '@/lib/quadro';
import type { BoardAbilities, BoardOptions, BoardProject, MoveIntent, MovePayload } from '@/types/quadro';

export interface UseBoardMoveOptions {
    project: BoardProject;
    /** Da `TaskPolicy`/`ProjectPolicy`: decide a recusa na tela; o servidor confere de novo (inegociável 9). */
    abilities: BoardAbilities;
    /** As listas do diálogo de retrabalho (P39, P40). */
    options: Pick<BoardOptions, 'rework_origins' | 'rework_reasons'>;
    /** Hoje, `AAAA-MM-DD` no fuso de São Paulo: o início proposto da *Nova sprint* (F5 §02). */
    today: string;
    /**
     * Manda o movimento ao servidor, com o payload que os diálogos juntaram. Quando houve diálogo,
     * ele continua aberto, com `processing` e `errors`, até a tela chamar `cancel()` no sucesso.
     */
    onCommit: (intent: MoveIntent, payload: MovePayload) => void;
    /** Movimento recusado na tela (permissão, P45): a tela mostra o motivo (toast). Nada foi enviado. */
    onDenied: (reason: string) => void;
    /** O request do movimento está rodando: o diálogo aberto mostra o spinner e não fecha. */
    processing?: boolean;
    /** Erros do servidor por campo, para o diálogo aberto (ex.: `name` repetido na sprint nova). */
    errors?: Record<string, string>;
}

/** O Laravel devolve o erro do campo aninhado como `new_sprint.name`; o diálogo lê `name`. */
function unprefix(errors: Record<string, string> | undefined, prefix: string): Record<string, string> | undefined {
    if (!errors) return undefined;
    return Object.fromEntries(
        Object.entries(errors).map(([key, message]) => [key.startsWith(prefix) ? key.slice(prefix.length) : key, message]),
    );
}

interface Flow {
    intent: MoveIntent;
    steps: MoveStep[];
    /** Índice do passo aberto em `steps`. */
    step: number;
    /** O que os passos já responderam. */
    payload: MovePayload;
    /** Falso fecha os diálogos e mantém o resto, para a animação de saída ainda ter o que mostrar. */
    open: boolean;
}

/**
 * Encadeia os diálogos de um movimento no quadro (F3 §01/§04, F5 §02), um jeito só para a
 * página do quadro e a ficha (*Mover para…*).
 *
 * `start(intent)` roda o `classifyMove`:
 * - recusado (dev tirando do Backlog, não alocado): chama `onDenied(reason)` e nada sai;
 * - sem passos (mesma coluna, ou coluna que não pergunta nada): chama `onCommit` na hora;
 * - com passos: abre os diálogos na ordem dos `steps` (*Nova sprint* → *Motivo da pausa* →
 *   *Devolver* → aviso do Done), juntando as respostas no `MovePayload`, e no último chama
 *   `onCommit(intent, payload)`.
 *
 * O payload sempre leva `stage_id` (destino) e, quando o `intent.index` vem preenchido
 * (ordem manual, P13), `position`. A *Nova sprint* entra como `new_sprint` (R5); o retrabalho,
 * como `mistake` ou `rework_origin_id`/`rework_reason_id`/`rework_reason_text` (P39, P40).
 *
 * Cancelar ou fechar qualquer diálogo encerra o fluxo sem chamar nada: o card fica onde
 * estava (a página não move o card antes do `onCommit`). Enquanto `processing`, nenhum
 * diálogo fecha. Depois do `onCommit` com diálogo, a página chama `cancel()` no `onSuccess`
 * da visita; no erro, deixa aberto, e o `errors` aparece no diálogo para corrigir e reenviar.
 *
 * Renderize `dialogs` uma vez na página.
 *
 * @example
 * const move = useBoardMove({
 *     project, abilities, options, today,
 *     processing: busy,
 *     errors: moveErrors,
 *     onDenied: (reason) => toast.error(reason),
 *     onCommit: (intent, payload) =>
 *         router.post(`/projetos/${project.id}/cards/${intent.card.key}/mover`, payload, {
 *             onStart: () => setBusy(true),
 *             onSuccess: () => move.cancel(),
 *             onError: (errors) => setMoveErrors(errors),
 *             onFinish: () => setBusy(false),
 *         }),
 * });
 * // no onDragEnd: move.start({ card, from, to, index });
 * // no JSX: {move.dialogs}
 */
export function useBoardMove({ project, abilities, options, today, onCommit, onDenied, processing = false, errors }: UseBoardMoveOptions) {
    const [flow, setFlow] = useState<Flow | null>(null);

    /** Começa um movimento: recusa, manda direto ou abre o primeiro diálogo. */
    const start = (intent: MoveIntent) => {
        const decision = classifyMove(intent, project, abilities);
        if (!decision.allowed) {
            onDenied(decision.reason);
            return;
        }
        const payload: MovePayload = { stage_id: intent.to.id, ...(intent.index === null ? {} : { position: intent.index }) };
        if (decision.steps.length === 0) {
            onCommit(intent, payload);
            return;
        }
        setFlow({ intent, steps: decision.steps, step: 0, payload, open: true });
    };

    /** Fecha os diálogos e encerra o fluxo. Também é o "terminou" que a página chama no sucesso. */
    const cancel = () => setFlow((current) => (current ? { ...current, open: false } : null));

    /** Junta a resposta do passo aberto e vai ao próximo; no último, chama `onCommit`. */
    const answer = (partial: Partial<MovePayload>) => {
        if (!flow || processing) return;
        const payload = { ...flow.payload, ...partial };
        if (flow.step + 1 < flow.steps.length) {
            setFlow({ ...flow, step: flow.step + 1, payload });
            return;
        }
        setFlow({ ...flow, payload });
        onCommit(flow.intent, payload);
    };

    const current: MoveStep | null = flow?.open ? flow.steps[flow.step] : null;
    const isLast = flow ? flow.step === flow.steps.length - 1 : false;
    // O spinner e os erros do servidor só fazem sentido no passo que mandou o movimento.
    const loading = isLast && processing;
    const stepErrors = isLast ? errors : undefined;
    const onOpenChange = (next: boolean) => !next && !processing && cancel();

    let dialogs: ReactNode = null;
    if (flow) {
        const { card, to } = flow.intent;
        dialogs = (
            <>
                <WNewSprintDialogOrganism
                    open={current === 'new_sprint'}
                    onOpenChange={onOpenChange}
                    card={card}
                    project={project}
                    today={today}
                    loading={current === 'new_sprint' && loading}
                    errors={current === 'new_sprint' ? unprefix(stepErrors, 'new_sprint.') : undefined}
                    onConfirm={(sprint) => answer({ new_sprint: sprint })}
                />
                <WPauseReasonDialogOrganism
                    open={current === 'pause_reason'}
                    onOpenChange={onOpenChange}
                    card={card}
                    stage={to}
                    loading={current === 'pause_reason' && loading}
                    errors={current === 'pause_reason' ? stepErrors : undefined}
                    onConfirm={(pause) => answer(pause)}
                />
                <WReworkDialogOrganism
                    open={current === 'rework'}
                    onOpenChange={onOpenChange}
                    card={card}
                    to={to}
                    origins={options.rework_origins}
                    reasons={options.rework_reasons}
                    loading={current === 'rework' && loading}
                    errors={current === 'rework' ? stepErrors : undefined}
                    onConfirm={(rework) => answer(rework)}
                />
                {/* Aviso do Done (P38): avisa e deixa seguir, nunca trava (premissa). */}
                <WConfirmDialogMolecule
                    open={current === 'done_warning'}
                    onOpenChange={onOpenChange}
                    title={`Mover ${card.key} para ${to.name}?`}
                    description={card.qa_summary ? doneWarningText(card.qa_summary) : undefined}
                    confirmLabel="Mover mesmo assim"
                    loading={current === 'done_warning' && loading}
                    onConfirm={() => answer({})}
                />
            </>
        );
    }

    return {
        /** Começa o movimento (no `onDragEnd` do quadro ou no *Mover para…* da ficha). */
        start,
        /** Fecha o diálogo aberto e encerra o fluxo; chame no `onSuccess` do movimento. */
        cancel,
        /** O passo aberto, ou null: para a página saber que há diálogo na tela. */
        step: current,
        /** O movimento em curso, ou null. */
        intent: flow?.open ? flow.intent : null,
        /** Todos os diálogos do fluxo, montados: renderize uma vez na página. */
        dialogs,
    };
}
