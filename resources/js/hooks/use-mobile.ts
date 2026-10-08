import { useSyncExternalStore } from 'react';

/** Igual ao `lg` do Tailwind: abaixo dele o menu lateral vira gaveta. */
const MOBILE_BREAKPOINT = 1024;

const QUERY = `(max-width: ${MOBILE_BREAKPOINT - 1}px)`;

function subscribe(onChange: () => void) {
    const mql = window.matchMedia(QUERY);
    mql.addEventListener('change', onChange);
    return () => mql.removeEventListener('change', onChange);
}

/** Usado pelo Sidebar do shadcn; lê o media query sem setState em effect. */
export function useIsMobile() {
    return useSyncExternalStore(
        subscribe,
        () => window.matchMedia(QUERY).matches,
        () => false,
    );
}
