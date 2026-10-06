import type { Locator, Page } from '@playwright/test';

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

    /** Waits for the server flash rendered as a toast. */
    async expectToast(text: string | RegExp) {
        await this.toast.getByText(text).waitFor();
    }
}
