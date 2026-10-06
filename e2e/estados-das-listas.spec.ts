import { expect, test } from './fixtures';
import { LIST_SCREENS, ListPage } from './pages/list.page';

/**
 * Os estados vazio, carregando e erro de cada lista do Núcleo (spec B1, P3 AC 1; stacks/ui.md §2),
 * pela visita Inertia que a busca dispara (hooks/use-list-visit.ts).
 */
for (const screen of LIST_SCREENS) {
    test.describe(screen.title, () => {
        test(`${screen.title}: filtro sem resultado mostra o estado vazio com a ação`, async ({ page }) => {
            const list = new ListPage(page, screen);
            await list.goto();

            await list.searchNothing();

            const empty = list.state(screen.empty);
            await expect(empty).toBeVisible();
            await expect(empty.getByRole(screen.emptyAction.role, { name: screen.emptyAction.name })).toBeVisible();
            await expect(page.getByRole('table')).toHaveCount(0);
        });

        test(`${screen.title}: enquanto a visita roda, a lista avisa que está carregando`, async ({ page }) => {
            const list = new ListPage(page, screen);
            await list.goto();
            let release!: () => void;
            const held = new Promise<void>((resolve) => (release = resolve));
            await list.interceptSearchVisit(async (route) => {
                await held;
                await route.continue();
            });

            await list.searchNothing();

            await expect(list.loadingStatus()).toHaveCount(1);
            await expect(list.busy).toHaveCount(1);
            release();
            await expect(list.state(screen.empty)).toBeVisible();
            await expect(list.loadingStatus()).toHaveCount(0);
            await expect(list.busy).toHaveCount(0);
        });

        test.describe('erro', () => {
            // O 500 abaixo é de propósito; o Chrome o registra como erro de recurso.
            test.use({ expectedConsoleErrors: [/Failed to load resource: the server responded with a status of 500/] });

            test(`${screen.title}: visita que falha mostra o erro com Tentar de novo`, async ({ page }) => {
                const list = new ListPage(page, screen);
                await list.goto();
                await list.interceptSearchVisit((route) =>
                    route.fulfill({ status: 500, contentType: 'text/html', body: '<h1>Erro interno</h1>' }),
                );

                await list.searchNothing();

                const error = list.state(screen.error);
                await expect(error).toBeVisible();
                await expect(error.getByRole('button', { name: 'Tentar de novo' })).toBeVisible();
                await expect(page.getByRole('table')).toHaveCount(0);
            });
        });
    });
}
