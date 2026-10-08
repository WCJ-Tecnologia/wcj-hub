import '../resources/css/fonts.css';
import '../resources/css/app.css';

import { withThemeByClassName } from '@storybook/addon-themes';
import type { Decorator, Preview } from '@storybook/react-vite';

import { TooltipProvider } from '@/components/shadcn/tooltip';
import type { SharedProps } from '@/types';

import { InertiaMockProvider, SHARED_PROPS } from './inertia-mock';

/**
 * Props do Inertia por story:
 *   parameters: { inertia: { url: '/admin/users', props: { auth: { user: null } } } }
 * `props` é mesclado por cima de SHARED_PROPS (usuário admin, marca, flash vazio).
 */
export interface InertiaParameters {
    url?: string;
    props?: Partial<SharedProps>;
}

const withInertia: Decorator = (Story, context) => {
    const inertia = (context.parameters.inertia ?? {}) as InertiaParameters;
    const page = { url: inertia.url ?? '/admin/dashboard', props: { ...SHARED_PROPS, ...inertia.props } as SharedProps };

    return (
        <InertiaMockProvider page={page}>
            <TooltipProvider>
                <Story />
            </TooltipProvider>
        </InertiaMockProvider>
    );
};

const preview: Preview = {
    parameters: {
        controls: { matchers: { color: /(background|color)$/i, date: /Date$/i } },
        // Mesma régua do e2e (e2e/fixtures.ts): WCAG 2.0/2.1 A + AA, color-contrast ligado.
        a11y: {
            test: 'error',
            options: { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] } },
        },
        layout: 'centered',
    },
    decorators: [
        withInertia,
        // A classe .dark no <html> é o que o app.blade.php aplica; os tokens trocam sozinhos.
        withThemeByClassName({ themes: { claro: '', escuro: 'dark' }, defaultTheme: 'claro', parentSelector: 'html' }),
    ],
};

export default preview;
