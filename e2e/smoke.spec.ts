import { expect, test } from './fixtures';
import { AdminPage } from './pages/admin.page';

const SCREENS = [
    { path: '/admin/dashboard', title: 'Dashboard' },
    { path: '/admin/users', title: 'Usuários' },
    { path: '/admin/clients', title: 'Clientes' },
    { path: '/admin/audit-logs', title: 'Logs de auditoria' },
    { path: '/admin/profile', title: 'Meu perfil' },
];

for (const screen of SCREENS) {
    test(`@smoke ${screen.title} renderiza sem erro e passa no axe`, async ({ page, axe }) => {
        const admin = new AdminPage(page);

        await admin.goto(screen.path);

        await expect(admin.heading).toHaveText(screen.title);
        await expect(admin.sidebar).toBeVisible();

        const results = await axe().analyze();
        expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
    });
}

test('@smoke o modal de usuário passa no axe', async ({ page, axe }) => {
    const admin = new AdminPage(page);
    await admin.goto('/admin/users');

    await page.getByRole('button', { name: 'Novo usuário' }).first().click();
    await expect(admin.dialog).toBeVisible();

    const results = await axe().analyze();
    expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
});

test('@smoke o modal de cliente passa no axe', async ({ page, axe }) => {
    const admin = new AdminPage(page);
    await admin.goto('/admin/clients');

    await page.getByRole('button', { name: 'Novo cliente' }).first().click();
    await expect(admin.dialog).toBeVisible();

    const results = await axe().analyze();
    expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
});

/** A ficha do cliente (ADR-004), aberta pelo nome na lista: a WCJ existe em toda instalação (N7). */
async function openWcjSheet(admin: AdminPage) {
    await admin.goto('/admin/clients');
    await admin.page.getByRole('link', { name: 'WCJ Tecnologia' }).first().click();
    await expect(admin.heading).toHaveText('WCJ Tecnologia');
}

test('@smoke a ficha do cliente renderiza sem erro e passa no axe', async ({ page, axe }) => {
    const admin = new AdminPage(page);
    await openWcjSheet(admin);

    await expect(page.getByRole('heading', { name: 'Contatos' })).toBeVisible();

    const results = await axe().analyze();
    expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
});

/**
 * Tema escuro: todo token tem valor nos dois temas e o gate de contraste vale
 * para os dois. A classe é aplicada como o anti-FOUC do app.blade.php faz.
 */
test.describe('@smoke tema escuro', () => {
    const DARK = [
        { path: '/admin/dashboard', title: 'Dashboard' },
        { path: '/admin/users', title: 'Usuários' },
        { path: '/admin/clients', title: 'Clientes' },
    ];

    for (const screen of DARK) {
        test(`@smoke ${screen.title} passa no axe em dark mode`, async ({ page, axe }) => {
            const admin = new AdminPage(page);
            await admin.goto(screen.path);
            await expect(admin.heading).toHaveText(screen.title);

            await page.evaluate(() => document.documentElement.classList.add('dark'));
            await expect(page.locator('html')).toHaveClass(/dark/);

            const results = await axe().analyze();
            expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
        });
    }

    test('@smoke o modal de usuário passa no axe em dark mode', async ({ page, axe }) => {
        const admin = new AdminPage(page);
        await admin.goto('/admin/users');
        await page.evaluate(() => document.documentElement.classList.add('dark'));

        await page.getByRole('button', { name: 'Novo usuário' }).first().click();
        await expect(admin.dialog).toBeVisible();

        const results = await axe().analyze();
        expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
    });

    test('@smoke a ficha do cliente passa no axe em dark mode', async ({ page, axe }) => {
        const admin = new AdminPage(page);
        await openWcjSheet(admin);

        await page.evaluate(() => document.documentElement.classList.add('dark'));
        await expect(page.locator('html')).toHaveClass(/dark/);

        const results = await axe().analyze();
        expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
    });

    test('@smoke o modal de cliente passa no axe em dark mode', async ({ page, axe }) => {
        const admin = new AdminPage(page);
        await admin.goto('/admin/clients');
        await page.evaluate(() => document.documentElement.classList.add('dark'));

        await page.getByRole('button', { name: 'Novo cliente' }).first().click();
        await expect(admin.dialog).toBeVisible();

        const results = await axe().analyze();
        expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
    });
});

test.describe('@smoke login', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('@smoke a tela de login renderiza sem erro e passa no axe', async ({ page, axe }) => {
        await page.goto('/admin/login');
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Entrar');

        const results = await axe().analyze();
        expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
    });
});
