import { expect, test } from './fixtures';
import { AdminPage } from './pages/admin.page';

/**
 * B9 (SEC-04): trocar a senha em Meu perfil pede a senha atual. Só o caminho recusado: o certo
 * trocaria a senha do administrador que as outras specs usam.
 */
test('trocar a senha pelo perfil com a senha atual errada mostra o erro no campo', async ({ page }, testInfo) => {
    const admin = new AdminPage(page);
    await admin.goto('/admin/profile');

    await page.getByLabel('Senha atual').fill('nao-e-a-senha-1');
    await page.getByLabel('Nova senha').fill('senha-nova-e2e-123');
    await page.getByLabel('Confirmar').fill('senha-nova-e2e-123');
    await page.getByRole('button', { name: 'Salvar meu perfil' }).click();

    await expect(page.getByText('A senha atual não confere.')).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('perfil-senha-atual.png') });
});
