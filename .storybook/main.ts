import type { StorybookConfig } from '@storybook/react-vite';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import type { PluginOption } from 'vite';

const here = dirname(fileURLToPath(import.meta.url));

function getAbsolutePath(value: string) {
    return dirname(fileURLToPath(import.meta.resolve(`${value}/package.json`)));
}

/** laravel-vite-plugin só funciona servido pelo Laravel (public/hot, manifest): fora do Storybook. */
function withoutLaravel(plugins: PluginOption[] = []): PluginOption[] {
    return plugins.flat(Infinity as 1).filter((plugin) => {
        const name = plugin && typeof plugin === 'object' && 'name' in plugin ? String(plugin.name) : '';
        return !name.startsWith('laravel');
    });
}

const config: StorybookConfig = {
    stories: [
        // Primitivos do shadcn (só os que têm o que ver; ver resources/js/components/README.md).
        '../resources/js/components/shadcn/**/*.stories.@(ts|tsx)',
        // Atomic Design do Hub: átomos → moléculas → organismos → templates → telas.
        '../resources/js/components/atoms/**/*.stories.@(ts|tsx)',
        '../resources/js/components/molecules/**/*.stories.@(ts|tsx)',
        '../resources/js/components/organisms/**/*.stories.@(ts|tsx)',
        '../resources/js/components/templates/**/*.stories.@(ts|tsx)',
        '../resources/js/components/pages/**/*.stories.@(ts|tsx)',
        // Hooks que montam interface (ex.: use-board-move, o fluxo de diálogos do quadro).
        '../resources/js/hooks/**/*.stories.@(ts|tsx)',
    ],
    addons: [
        getAbsolutePath('@storybook/addon-docs'),
        getAbsolutePath('@storybook/addon-a11y'),
        getAbsolutePath('@storybook/addon-themes'),
    ],
    framework: getAbsolutePath('@storybook/react-vite'),
    // Nada sai da máquina sem decisão do time (AGENTS.md, inegociável 5).
    core: { disableTelemetry: true },
    // Fontes (public/fonts) e o logo (public/brand) servidos como no app.
    staticDirs: ['../public'],
    viteFinal: async (viteConfig) => {
        viteConfig.plugins = withoutLaravel(viteConfig.plugins);
        viteConfig.resolve ??= {};
        viteConfig.resolve.alias = [
            // Sem servidor Laravel: usePage, Link e router vêm do mock (ver .storybook/inertia-mock.tsx).
            { find: /^@inertiajs\/react$/, replacement: resolve(here, 'inertia-mock.tsx') },
            { find: '@', replacement: resolve(here, '../resources/js') },
        ];
        return viteConfig;
    },
};

export default config;
