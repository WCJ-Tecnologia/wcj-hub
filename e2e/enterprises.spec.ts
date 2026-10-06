import { expect, test } from './fixtures';
import { AdminPage } from './pages/admin.page';

const VALID_CNPJ = '11222333000181';

/**
 * The CRUD blueprint, end to end. Runs in order against the database created
 * by `npm run e2e:prepare` and cleans up what it created.
 */
test.describe.serial('Empresas', () => {
    const stamp = Date.now().toString(36);
    const name = `Empresa E2E ${stamp} Ltda`;
    const nickname = `E2E ${stamp}`;
    const renamed = `E2E ${stamp} SP`;

    test('mostra o estado vazio quando não há empresas', async ({ page }) => {
        const admin = new AdminPage(page);
        await admin.goto('/admin/enterprises');

        await expect(page.getByText('Nenhuma empresa cadastrada')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Nova empresa' }).first()).toBeVisible();
    });

    test('cria uma empresa pelo modal', async ({ page }) => {
        const admin = new AdminPage(page);
        await admin.goto('/admin/enterprises');

        await page.getByRole('button', { name: 'Nova empresa' }).first().click();
        await expect(admin.dialog).toBeVisible();

        await admin.dialog.getByLabel('Razão social').fill(name);
        await admin.dialog.getByLabel('Apelido').fill(nickname);
        await admin.dialog.getByLabel('CNPJ').fill(VALID_CNPJ);
        await admin.dialog.getByRole('button', { name: 'Cadastrar empresa' }).click();

        await admin.expectToast('Empresa cadastrada com sucesso.');
        await expect(admin.dialog).toBeHidden();
        await expect(page.getByRole('cell', { name, exact: true })).toBeVisible();
        await expect(page.getByRole('cell', { name: '11.222.333/0001-81' })).toBeVisible();
    });

    test('rejeita um CNPJ inválido mostrando o erro no campo', async ({ page }) => {
        const admin = new AdminPage(page);
        await admin.goto('/admin/enterprises');

        await page.getByRole('button', { name: 'Nova empresa' }).first().click();
        await admin.dialog.getByLabel('Razão social').fill('Inválida');
        await admin.dialog.getByLabel('Apelido').fill('Inválida');
        await admin.dialog.getByLabel('CNPJ').fill('11111111111111');
        await admin.dialog.getByRole('button', { name: 'Cadastrar empresa' }).click();

        await expect(admin.dialog.getByText('Informe um CNPJ válido.')).toBeVisible();
        await expect(admin.dialog).toBeVisible();
    });

    test('edita a empresa criada', async ({ page }) => {
        const admin = new AdminPage(page);
        await admin.goto('/admin/enterprises');

        await page.getByRole('button', { name: `Editar ${nickname}` }).click();
        await expect(admin.dialog.getByLabel('Razão social')).toHaveValue(name);

        await admin.dialog.getByLabel('Apelido').fill(renamed);
        await admin.dialog.getByRole('button', { name: 'Salvar alterações' }).click();

        await admin.expectToast('Empresa atualizada.');
        await expect(page.getByRole('cell', { name: renamed, exact: true })).toBeVisible();
    });

    test('desativa guardando quem e quando', async ({ page }) => {
        const admin = new AdminPage(page);
        await admin.goto('/admin/enterprises');

        await page.getByRole('button', { name: `Editar ${renamed}` }).click();
        await admin.dialog.getByLabel('Empresa ativa').uncheck();
        await admin.dialog.getByRole('button', { name: 'Salvar alterações' }).click();

        await admin.expectToast('Empresa atualizada.');
        await expect(page.getByRole('row', { name: new RegExp(renamed) })).toContainText('Inativa');

        await page.getByRole('button', { name: `Editar ${renamed}` }).click();
        await expect(admin.dialog.getByLabel('Empresa ativa')).not.toBeChecked();
        await expect(admin.dialog.getByText(/^Desativada em \d{2}\/\d{2}\/\d{4} \d{2}:\d{2}, por E2E Admin\.$/)).toBeVisible();
        await admin.dialog.getByRole('button', { name: 'Cancelar' }).click();
    });

    test('exclui com confirmação e volta ao estado vazio', async ({ page }) => {
        const admin = new AdminPage(page);
        await admin.goto('/admin/enterprises');

        await page.getByRole('button', { name: `Excluir ${renamed}` }).click();
        await expect(admin.dialog).toContainText(name);

        await admin.dialog.getByRole('button', { name: 'Excluir' }).click();

        await admin.expectToast('Empresa excluída.');
        await expect(page.getByRole('cell', { name, exact: true })).toHaveCount(0);
        await expect(page.getByText('Nenhuma empresa cadastrada')).toBeVisible();
    });
});
