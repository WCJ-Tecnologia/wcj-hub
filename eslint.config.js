import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'storage/**',
            'bootstrap/cache/**',
            'playwright-report/**',
            'test-results/**',
            '.harness/**',
        ],
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        plugins: { 'react-hooks': reactHooks },
        rules: {
            ...reactHooks.configs.recommended.rules,
            '@typescript-eslint/consistent-type-imports': ['error', { fixStyle: 'inline-type-imports' }],
        },
        languageOptions: { globals: globals.browser },
    },
    {
        files: ['e2e/**/*.{ts,mjs}', 'scripts/**/*.mjs', '.ai/adapters/**/*.mjs', '.ai/skills/*.mjs', '*.config.{js,ts}'],
        languageOptions: { globals: globals.node },
    },
    prettier,
);
