import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';

/** O que a ação precisa da linha: só o id. */
interface Row {
    id: number;
}

/** O que a tela faz quando a ação termina, com sucesso, erro ou cancelada (fechar o diálogo). */
interface Extra {
    onFinish?: () => void;
}

/**
 * Ações de linha de uma listagem (Reativar, Desativar, Excluir…) com a trava de clique duplo,
 * um jeito só para as telas (CR-1-17, CR-2-03).
 *
 * Visita síncrona cancela visita síncrona no Inertia 2: um segundo POST cancelaria o primeiro no
 * navegador, mas os dois chegariam ao servidor. Por isso, enquanto qualquer ação de linha roda,
 * nenhuma outra sai. A linha que roda sinaliza (`isBusy`: esmaece e trava) e as outras ficam
 * desabilitadas (`isLocked`), para um clique nelas não ser descartado em silêncio (CR-2-07).
 * A liberação é no `onFinish`, que o Inertia chama no sucesso, no erro e no cancelamento.
 */
export function useRowAction() {
    const [busyRow, setBusyRow] = useState<number | null>(null);
    // A guarda lê a ref, não o estado: dois cliques no mesmo quadro chegam antes de o React redesenhar.
    const running = useRef(false);

    /** Opções da visita da linha, para `router.post`, `router.delete` ou um `useForm`. */
    const request = (row: Row, extra: Extra = {}) => ({
        preserveScroll: true,
        onStart: () => {
            running.current = true;
            setBusyRow(row.id);
        },
        onFinish: () => {
            running.current = false;
            setBusyRow(null);
            extra.onFinish?.();
        },
    });

    /** Roda a visita, a não ser que outra ação de linha ainda esteja rodando (aí o clique é ignorado). */
    const guarded = (visit: () => void) => {
        if (running.current) return;
        running.current = true;
        visit();
    };

    const post = (url: string, row: Row, extra?: Extra) => guarded(() => router.post(url, {}, request(row, extra)));
    const del = (url: string, row: Row, extra?: Extra) => guarded(() => router.delete(url, request(row, extra)));

    return {
        /** O id da linha cuja ação está rodando; nulo quando nenhuma roda. */
        busyRow,
        /** Alguma ação de linha está rodando (o `loading` dos diálogos de confirmação). */
        busy: busyRow !== null,
        request,
        post,
        del,
        /** A ação desta linha está rodando: `RowActions busy`. */
        isBusy: (row: Row) => busyRow === row.id,
        /** A ação de outra linha está rodando: `RowActions disabled`. */
        isLocked: (row: Row) => busyRow !== null && busyRow !== row.id,
    };
}
