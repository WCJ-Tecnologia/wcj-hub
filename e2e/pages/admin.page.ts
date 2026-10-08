import { expect, type Locator, type Page } from '@playwright/test';

/** The backoffice shell: one object for the selectors every admin screen shares. */
export class AdminPage {
    readonly heading: Locator;
    readonly sidebar: Locator;
    readonly toast: Locator;
    readonly dialog: Locator;

    constructor(readonly page: Page) {
        this.heading = page.getByRole('heading', { level: 1 });
        this.sidebar = page.getByRole('navigation').first();
        this.toast = page.getByRole('status');
        this.dialog = page.getByRole('dialog');
    }

    async goto(path: string) {
        await this.page.goto(path);
    }

    /**
     * Digita na busca da lista aberta e espera a visita da busca voltar e a URL trazer o termo. O
     * SearchInput navega 300 ms depois da digitação (debounce); uma ação clicada antes disso é
     * cancelada por essa visita: o PUT do Editar morria com status -1 (A5 da review de 07/10).
     */
    async search(box: Locator, term: string) {
        const path = new URL(this.page.url()).pathname;
        const visit = this.page.waitForResponse((response) => {
            const url = new URL(response.url());

            return url.pathname === path && url.searchParams.get('search') === term;
        });
        await box.fill(term);
        await visit;
        await expect.poll(() => new URL(this.page.url()).searchParams.get('search')).toBe(term);
    }

    /** Waits for the server flash rendered as a toast. */
    async expectToast(text: string | RegExp) {
        await this.toast.getByText(text).waitFor();
    }
}
