import { expect, test } from './fixtures';
import { AuditLogsPage } from './pages/audit-logs.page';
import { ClientsPage } from './pages/clients.page';

/**
 * Fluxo crítico do Núcleo (B1 T24; Clientes AC 1 e 3, Contatos AC 1, Auditoria AC 4 e 6): cliente
 * com CNPJ alfanumérico, a recusa do mesmo CNPJ em minúsculas, o contato na ficha, a edição e o
 * Histórico com o antes e o depois. No fim exclui o que criou (nunca usado: G2), para o CNPJ
 * poder ser criado de novo e nenhum outro spec ver sobra.
 */
test('@critical cliente com CNPJ alfanumérico, contato e histórico com antes e depois', async ({ page }) => {
    const stamp = Date.now();
    const name = `E2E Cliente ${stamp}`;
    const renamed = `${name} Ltda`;
    const contact = `E2E Contato ${stamp}`;
    const clients = new ClientsPage(page);
    const audit = new AuditLogsPage(page);

    await clients.goto();
    await clients.openCreate();
    await clients.submitForm(name, '12.ABC.345/01DE-35');
    await expect(clients.admin.dialog).toBeHidden();
    await clients.admin.expectToast(`Cliente ${name} criado.`);

    // N5: o mesmo CNPJ, digitado em minúsculas, é recusado citando o cliente que já existe.
    await clients.openCreate();
    await clients.submitForm(`${name} Repetido`, '12.abc.345/01de-35');
    await expect(clients.admin.dialog.getByText(`Já existe o cliente ${name} com este CNPJ`)).toBeVisible();
    await clients.cancelForm();

    await clients.openSheet(name);
    await clients.createContact(contact);
    await clients.admin.expectToast(`Contato ${contact} criado.`);
    await expect(page.getByText(contact).first()).toBeVisible();

    await clients.edit(name, renamed);
    await clients.admin.expectToast('Cliente atualizado.');

    await clients.openSheet(renamed);
    await clients.openHistory();
    await expect(audit.historyOf(`Cliente ${renamed}`)).toBeVisible();
    await audit.openDetail('Atualizado', `Cliente ${renamed}`);
    await expect(audit.changeRow('Nome').getByRole('cell')).toHaveText([name, renamed]);

    await clients.openSheet(renamed);
    await clients.deleteRow(contact);
    await clients.goto();
    await clients.find(renamed);
    await clients.deleteRow(renamed);
});
