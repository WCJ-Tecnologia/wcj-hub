/**
 * Textos e contas de tela dos indicadores e do histórico (F7 §02, §06, §07). As contas que valem
 * são as do servidor; aqui só se escreve o que a tela mostra.
 */

import type { DueMissing, HistoryMovement, IndicatorsTab, MyTaskRow } from '@/types/indicadores';

export const TAB_LABEL: Record<IndicatorsTab, string> = {
    situacao: 'Situação',
    tempo: 'Tempo por etapa',
    horas: 'Horas',
    pessoas: 'Por pessoa',
    qualidade: 'Qualidade',
    minhas: 'Minhas tarefas e horas',
};

/** Marca do tempo em desenvolvimento (R17; F7 §07). */
export const DEVELOPMENT_TIME_NOTE = 'estimado pelo quadro; não substitui o lançamento de horas';

/** Dias úteis com uma casa decimal, em português: "1,5 dia útil", "3 dias úteis". */
export function businessDays(days: number): string {
    const text = days.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 1 });
    return `${text} ${days === 1 ? 'dia útil' : 'dias úteis'}`;
}

/** "N dias úteis de atraso, descontada a pausa" ou "No prazo" (F7 §02). */
export function delayText(delayDays: number | null): string | null {
    if (delayDays === null) return null;
    if (delayDays <= 0) return 'No prazo';
    return `${businessDays(delayDays)} de atraso, descontada a pausa`;
}

/** "Sem data prevista: falta o dev", "… faltam as horas" ou "… ainda não entrou em desenvolvimento" (F7 §07). */
export function missingText(missing: DueMissing[]): string {
    const parts = missing.map((item) =>
        item === 'developer' ? 'falta o dev' : item === 'hours' ? 'faltam as horas' : 'falta a carga diária do dev',
    );
    return `Sem data prevista: ${parts.length ? parts.join(' e ') : 'ainda não entrou em desenvolvimento'}`;
}

/** Meu papel na tarefa (F7 §03 "Minhas tarefas e horas"). */
export const MY_ROLE_LABEL: Record<MyTaskRow['role'], string> = { developer: 'Dev', qa: 'QA', both: 'Dev e QA' };

/** A frase do movimento: "Criou o card em Backlog" ou "Backlog → Doing" (F7 §02). */
export function movementText(movement: HistoryMovement): string {
    if (!movement.from) return `Criou o card em ${movement.to.name}`;
    return `${movement.from.name} → ${movement.to.name}`;
}

/** "(removida)" ao lado da etapa que saiu do quadro (P12). */
export function stageName(stage: { name: string; removed: boolean }): string {
    return stage.removed ? `${stage.name} (removida)` : stage.name;
}
