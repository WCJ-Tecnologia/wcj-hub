import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/**
 * Estados de carregando e de erro de uma listagem Inertia (stacks/ui.md §2). Só as visitas GET
 * (busca, filtro, página) contam: a escrita já tem o próprio botão carregando e o toast.
 */
export function useListVisit() {
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const listing = useRef(false);

    useEffect(() => {
        const offs = [
            router.on('start', (event) => {
                listing.current = event.detail.visit.method === 'get';
                if (listing.current) {
                    setLoading(true);
                    setFailed(false);
                }
            }),
            router.on('finish', () => setLoading(false)),
            // Rede caída ou resposta que não é Inertia (500 em produção): a lista avisa e oferece
            // tentar de novo, em vez do modal de erro por cima da tela.
            router.on('exception', () => {
                if (listing.current) setFailed(true);
            }),
            router.on('invalid', (event) => {
                if (!listing.current) return;
                event.preventDefault();
                setFailed(true);
            }),
        ];

        return () => offs.forEach((off) => off());
    }, []);

    return { loading, failed, retry: () => router.reload() };
}
