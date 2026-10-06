import { expect, type Locator, type Page } from '@playwright/test';

import { AdminPage } from './admin.page';

/** Administração → Usuários (F1 §03, §04): os seletores num lugar só. */
export class UsersPage {
    readonly admin: AdminPage;
    readonly showInactive: Locator;

    constructor(readonly page: Page) {
        this.admin = new AdminPage(page);
        this.showInactive = page.getByLabel('Mostrar desativados');
    }

    async goto() {
        await this.admin.goto('/admin/users');
        await expect(this.admin.heading).toHaveText('Usuários');
    }

    /** Convida um Desenvolvedor (o papel padrão do formulário) com a carga informada. */
    async invite(name: string, email: string, dailyHours: string) {
        await this.page.getByRole('button', { name: 'Convidar usuário' }).first().click();
        await expect(this.admin.dialog).toBeVisible();
        await this.admin.dialog.getByLabel('Nome').fill(name);
        await this.admin.dialog.getByLabel('E-mail').fill(email);
        await this.admin.dialog.getByLabel('Carga diária (horas)').fill(dailyHours);
        await this.admin.dialog.getByRole('button', { name: 'Enviar convite' }).click();
        await expect(this.admin.dialog).toBeHidden();
    }

    /** A linha da tabela pelo e-mail, que é único. */
    row(email: string): Locator {
        return this.page.getByRole('row').filter({ hasText: email });
    }

    /** Desativar pela linha, confirmando no diálogo. */
    async deactivate(name: string) {
        await this.page.getByRole('button', { name: `Desativar ${name}` }).click();
        await this.admin.dialog.getByRole('button', { name: 'Desativar' }).click();
        await expect(this.admin.dialog).toBeHidden();
    }

    /** Excluir pela linha, confirmando no diálogo. */
    async deleteRow(name: string) {
        await this.page.getByRole('button', { name: `Excluir ${name}` }).click();
        await this.admin.dialog.getByRole('button', { name: 'Excluir' }).click();
        await expect(this.page.getByRole('button', { name: `Excluir ${name}` })).toHaveCount(0);
    }
}
