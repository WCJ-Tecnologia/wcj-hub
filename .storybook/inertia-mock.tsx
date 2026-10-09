/**
 * Stand-in for @inertiajs/react inside Storybook (aliased in main.ts). There is
 * no Laravel behind the stories, so:
 * - usePage() reads the props a story declares in `parameters.inertia`
 *   (merged over SHARED_PROPS) through InertiaMockProvider;
 * - Link renders a plain <a>; router and useForm submits only log to the
 *   Actions panel (nothing leaves the browser).
 */
import { createContext, useCallback, useContext, useState, type AnchorHTMLAttributes, type ReactNode } from 'react';
import { action } from 'storybook/actions';

import type { SharedProps } from '@/types';

export const SHARED_PROPS: SharedProps = {
    app: { name: 'WCJ Hub', brand: { name: 'WCJ Hub' } },
    auth: {
        user: {
            id: 1,
            name: 'Ana Souza',
            email: 'ana@exemplo.wcj.test',
            role: 'admin',
            role_label: 'Administrador',
            must_change_password: false,
            abilities: { manage_users: true, delete_records: true, view_audit_logs: true, reset_passwords: true, view_clients: true, view_contacts: true, view_projects: true, manage_projects: true },
        },
    },
    flash: {},
    errors: {},
};

export interface MockPage {
    url: string;
    props: SharedProps;
}

const PageContext = createContext<MockPage>({ url: '/admin/dashboard', props: SHARED_PROPS });

export function InertiaMockProvider({ page, children }: { page: MockPage; children: ReactNode }) {
    return <PageContext.Provider value={page}>{children}</PageContext.Provider>;
}

export function usePage<T = SharedProps>() {
    return useContext(PageContext) as unknown as { url: string; props: T; component: string; version: null };
}

const log = (name: string) => action(`router.${name}`);

export const router = {
    get: (...args: unknown[]) => log('get')(...args),
    post: (...args: unknown[]) => log('post')(...args),
    put: (...args: unknown[]) => log('put')(...args),
    patch: (...args: unknown[]) => log('patch')(...args),
    delete: (...args: unknown[]) => log('delete')(...args),
    visit: (...args: unknown[]) => log('visit')(...args),
    reload: (...args: unknown[]) => log('reload')(...args),
    // Real visit options call onStart/onFinish; nothing happens here.
    on: () => () => undefined,
};

type LinkProps = AnchorHTMLAttributes<HTMLAnchorElement> & {
    href: string;
    method?: string;
    data?: unknown;
    preserveScroll?: boolean;
    preserveState?: boolean;
    replace?: boolean;
    only?: string[];
    as?: string;
    prefetch?: boolean;
};

export function Link({ href, method, data, preserveScroll, preserveState, replace, only, as, prefetch, onClick, ...props }: LinkProps) {
    void [method, data, preserveScroll, preserveState, replace, only, as, prefetch];
    return (
        <a
            href={href}
            onClick={(event) => {
                event.preventDefault();
                onClick?.(event);
                action('Link')(href);
            }}
            {...props}
        />
    );
}

export function Head() {
    return null;
}

type FormData = Record<string, unknown>;

/** Same surface as Inertia's useForm for what the screens use; submits are logged, never sent. */
export function useForm<T extends FormData>(initial: T) {
    const [defaults, setDefaults] = useState<T>(initial);
    const [data, setDataState] = useState<T>(initial);
    const [errors, setErrors] = useState<Partial<Record<keyof T, string>>>({});

    const setData = useCallback((keyOrData: keyof T | T, value?: unknown) => {
        setDataState((current) => (typeof keyOrData === 'object' ? keyOrData : { ...current, [keyOrData]: value }));
    }, []);

    const submit = (method: string) => (url: string, options?: { onSuccess?: () => void; onFinish?: () => void }) => {
        action(`useForm.${method}`)(url, data);
        options?.onSuccess?.();
        options?.onFinish?.();
    };

    return {
        data,
        setData,
        errors,
        hasErrors: Object.keys(errors).length > 0,
        processing: false,
        progress: null,
        wasSuccessful: false,
        recentlySuccessful: false,
        isDirty: JSON.stringify(data) !== JSON.stringify(defaults),
        setError: (field: keyof T, message: string) => setErrors((current) => ({ ...current, [field]: message })),
        clearErrors: () => setErrors({}),
        reset: (...fields: (keyof T)[]) =>
            setDataState((current) =>
                fields.length === 0 ? defaults : { ...current, ...Object.fromEntries(fields.map((field) => [field, defaults[field]])) },
            ),
        setDefaults: () => setDefaults(data),
        transform: () => undefined,
        cancel: () => undefined,
        get: submit('get'),
        post: submit('post'),
        put: submit('put'),
        patch: submit('patch'),
        delete: submit('delete'),
        submit: (method: string, url: string) => submit(method)(url),
    };
}
