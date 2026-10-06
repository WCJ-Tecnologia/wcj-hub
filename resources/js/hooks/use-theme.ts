import { useCallback, useState } from 'react';

type Theme = 'light' | 'dark';

/** Must match the key read by the anti-FOUC script in resources/views/app.blade.php. */
const KEY = 'app-theme';

function current(): Theme {
    if (typeof document === 'undefined') return 'light';
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

export function useTheme() {
    // The anti-FOUC script already applied the class before React mounted, so
    // reading the DOM in the lazy initializer is enough (no SSR here).
    const [theme, setTheme] = useState<Theme>(current);

    const apply = useCallback((next: Theme) => {
        document.documentElement.classList.toggle('dark', next === 'dark');
        try {
            localStorage.setItem(KEY, next);
        } catch {
            /* private mode or blocked storage: the choice just does not persist */
        }
        setTheme(next);
    }, []);

    const toggle = useCallback(() => {
        apply(current() === 'dark' ? 'light' : 'dark');
    }, [apply]);

    return { theme, toggle, setTheme: apply };
}
