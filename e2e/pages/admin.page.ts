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
        // Sonner: the toaster is a labelled region ("Avisos alt+T"), with aria-live.
        this.toast = page.getByRole('region', { name: /^Avisos/ });
        this.dialog = page.getByRole('dialog');
    }

    async goto(path: string) {
        await this.page.goto(path);
    }

    /**
     * Digita na busca da lista aberta e espera a visita da busca voltar e a URL trazer o termo: a
     * ação seguinte acha a linha já filtrada. O SearchInput navega 300 ms depois da digitação
     * (debounce); desde o CR-1-10 a busca espera uma escrita em voo terminar, em vez de cancelá-la
     * (e2e/busca-e-escrita.spec.ts).
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
