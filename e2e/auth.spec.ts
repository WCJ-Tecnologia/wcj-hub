import { expect, test } from './fixtures';
import { LoginPage } from './pages/login.page';

const INVALID_MESSAGE = 'As credenciais informadas não conferem.';

// Every test here starts as a guest.
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Autenticação', () => {
    test('rota protegida sem sessão redireciona para o login', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await expect(page).toHaveURL(/\/admin\/login$/);

        await page.goto('/');
        await expect(page).toHaveURL(/\/admin\/login$/);
    });

    test('login inválido mostra uma única mensagem, para conta existente ou não', async ({ page }) => {
        const login = new LoginPage(page);
        await login.goto();

        await login.login(`ninguem-${Date.now()}@example.com`, 'senha-errada-1');
        await expect(login.error).toHaveText(INVALID_MESSAGE);

        await login.login(process.env.E2E_ADMIN_EMAIL ?? 'e2e-admin@example.com', 'senha-errada-1');
        await expect(login.error).toHaveText(INVALID_MESSAGE);
    });

    test('a sexta tentativa para a mesma conta responde 429 com a mesma mensagem', async ({ page }) => {
        const login = new LoginPage(page);
        const email = `throttle-${Date.now()}@example.com`;
        await login.goto();

        for (let attempt = 1; attempt <= 5; attempt++) {
            const response = page.waitForResponse((r) => r.url().endsWith('/admin/login') && r.request().method() === 'POST');
            await login.login(email, 'senha-errada-1');
            expect((await response).status(), `tentativa ${attempt}`).toBe(302);
            await expect(login.error).toHaveText(INVALID_MESSAGE);
        }

        const throttled = page.waitForResponse((r) => r.url().endsWith('/admin/login') && r.request().method() === 'POST');
        await login.login(email, 'senha-errada-1');
        expect((await throttled).status()).toBe(429);

        await expect(login.error).toHaveText(INVALID_MESSAGE);
        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
