import { expect, type Locator, type Page } from '@playwright/test';

import { AdminPage } from './admin.page';

/** Administração → Auditoria (F1 §03): a lista e o detalhe com o antes e o depois. */
export class AuditLogsPage {
    readonly admin: AdminPage;

    constructor(readonly page: Page) {
        this.admin = new AdminPage(page);
    }

    /** O aviso de que a tela veio do Histórico de uma ficha. */
    historyOf(record: string): Locator {
        return this.page.getByText(`Histórico de ${record}`);
    }

    /** Abre o detalhe da linha mais recente com esta ação e este registro. */
    async openDetail(action: string, record: string) {
        await this.page
            .getByRole('button', { name: `Ver detalhes: ${action}, ${record},` })
            .first()
            .click();
        await expect(this.admin.dialog).toBeVisible();
    }

    /** A linha do campo no detalhe: rótulo, antes e depois. */
    changeRow(label: string): Locator {
        return this.admin.dialog.getByRole('row').filter({ has: this.page.getByRole('rowheader', { name: label, exact: true }) });
    }
}
