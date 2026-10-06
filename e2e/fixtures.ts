import AxeBuilder from '@axe-core/playwright';
import { test as base, expect } from '@playwright/test';

/**
 * Known benign noise. Keep this list short and specific: anything not matched
 * fails the test.
 */
const NOISE: RegExp[] = [
    /favicon/i,
    /\[vite\]/i,
    /vite.*hmr|hmr.*vite|WebSocket connection to 'ws:\/\/(localhost|127\.0\.0\.1):5173/i,
    /React DevTools/i,
    // The throttle test expects a 429; Chrome logs it as a resource error.
    /Failed to load resource: the server responded with a status of 429/i,
];

type Fixtures = {
    /** Fails the test if any page error or console.error survived the noise filter. */
    consoleGuard: void;
    /** WCAG 2.0/2.1 A + AA. color-contrast stays on. */
    axe: () => AxeBuilder;
};

export const test = base.extend<Fixtures>({
    consoleGuard: [
        async ({ page }, use) => {
            const errors: string[] = [];

            page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
            page.on('console', (message) => {
                if (message.type() === 'error') errors.push(`console.error: ${message.text()}`);
            });

            await use();

            const real = errors.filter((entry) => !NOISE.some((pattern) => pattern.test(entry)));
            expect(real, 'erros de JavaScript ou console.error durante o teste').toEqual([]);
        },
        { auto: true },
    ],

    axe: async ({ page }, use) => {
        await use(() => {
            const builder = new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']);
            const analyze = builder.analyze.bind(builder);

            // Colours sampled mid-animation are not the colours users see.
            builder.analyze = async () => {
                await page.evaluate(() =>
                    Promise.all(document.getAnimations().map((animation) => animation.finished.catch(() => undefined))),
                );

                return analyze();
            };

            return builder;
        });
    },
});

export { expect };
