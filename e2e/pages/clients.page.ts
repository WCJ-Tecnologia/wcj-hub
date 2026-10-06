import { expect, type Locator, type Page } from '@playwright/test';

import { AdminPage } from './admin.page';

/** Administração → Clientes e a ficha do cliente (F1 §01, §03): os seletores num lugar só. */
export class ClientsPage {
    readonly admin: AdminPage;
    readonly search: Locator;

    constructor(readonly page: Page) {
        this.admin = new AdminPage(page);
        this.search = page.getByRole('searchbox', { name: 'Buscar clientes' });
    }

    async goto() {
        await this.admin.goto('/admin/clients');
        await expect(this.admin.heading).toHaveText('Clientes');
    }

    /** Filtra a lista pelo nome e espera a linha aparecer. */
    async find(name: string) {
        await this.search.fill(name);
        await expect(this.page.getByRole('link', { name, exact: true })).toBeVisible();
    }

    async openCreate() {
        await this.page.getByRole('button', { name: 'Novo cliente' }).first().click();
        await expect(this.admin.dialog).toBeVisible();
    }

    /** Preenche nome e CNPJ (Brasil, o país padrão) e envia o formulário aberto. */
    async submitForm(name: string, document?: string) {
        await this.admin.dialog.getByLabel('Nome').fill(name);
        if (document !== undefined) await this.admin.dialog.getByLabel('CNPJ').fill(document);
        await this.admin.dialog.getByRole('button', { name: /^(Criar cliente|Salvar alterações)$/ }).click();
    }

    async cancelForm() {
        await this.admin.dialog.getByRole('button', { name: 'Cancelar' }).click();
        await expect(this.admin.dialog).toBeHidden();
    }

    async edit(name: string, newName: string) {
        await this.goto();
        await this.find(name);
        await this.page.getByRole('button', { name: `Editar ${name}` }).click();
        await expect(this.admin.dialog).toBeVisible();
        await this.submitForm(newName);
        await expect(this.admin.dialog).toBeHidden();
    }

    async openSheet(name: string) {
        await this.goto();
        await this.find(name);
        await this.page.getByRole('link', { name, exact: true }).click();
        await expect(this.admin.heading).toHaveText(name);
    }

    /** Na ficha aberta: Novo contato, só com o nome. */
    async createContact(name: string) {
        await this.page.getByRole('button', { name: 'Novo contato' }).first().click();
        await expect(this.admin.dialog).toBeVisible();
        await this.admin.dialog.getByLabel('Nome').fill(name);
        await this.admin.dialog.getByRole('button', { name: 'Criar contato' }).click();
        await expect(this.admin.dialog).toBeHidden();
    }

    /** O botão Histórico da ficha aberta (F1 §01). */
    async openHistory() {
        await this.page.getByRole('button', { name: 'Histórico', exact: true }).click();
        await expect(this.admin.heading).toHaveText('Auditoria');
    }

    /** Excluir pela linha (lista de clientes ou contatos da ficha), confirmando no diálogo. */
    async deleteRow(name: string) {
        await this.page.getByRole('button', { name: `Excluir ${name}` }).click();
        await this.admin.dialog.getByRole('button', { name: 'Excluir' }).click();
        await expect(this.page.getByRole('button', { name: `Excluir ${name}` })).toHaveCount(0);
    }
}
