import { expect, test } from './fixtures';
import { AdminPage } from './pages/admin.page';
import { UsersPage } from './pages/users.page';

/**
 * Ajustes de tela pedidos pelo Cauã no teste manual do B1 (07/10): só a caixa liga "Mostrar
 * desativados" (o texto não), e o aviso do servidor aparece no topo da tela, não embaixo.
 */

test('"Mostrar desativados" só liga pela caixa; o texto não troca a lista', async ({ page }) => {
    const users = new UsersPage(page);
    await users.goto();

    await page.getByText('Mostrar desativados', { exact: true }).click();
    await expect(users.showInactive).not.toBeChecked();
    expect(new URL(page.url()).searchParams.get('show_inactive')).toBeNull();

    await users.showInactive.click();
    await expect(users.showInactive).toBeChecked();
    await expect(page).toHaveURL(/show_inactive=1/);
});

test('o aviso do servidor aparece no topo da tela', async ({ page }) => {
    const admin = new AdminPage(page);
    await admin.goto('/admin/profile');

    await page.getByRole('button', { name: 'Salvar meu perfil' }).click();
    const aviso = admin.toast.getByText('Seu perfil está atualizado.', { exact: false });
    await expect(aviso).toBeVisible();

    const caixa = await aviso.boundingBox();
    const altura = page.viewportSize()?.height ?? 720;
    expect(caixa).not.toBeNull();
    expect(caixa!.y).toBeLessThan(altura / 4);
});
