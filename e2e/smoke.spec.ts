import { expect, test } from './fixtures';
import { deleteContactsNamed } from './helpers/dados';
import { AdminPage } from './pages/admin.page';

const SCREENS = [
    { path: '/admin/dashboard', title: 'Dashboard' },
    { path: '/admin/users', title: 'Usuários' },
    { path: '/admin/clients', title: 'Clientes' },
    { path: '/admin/contacts', title: 'Contatos' },
    { path: '/admin/audit-logs', title: 'Auditoria' },
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

test('@smoke o menu Administração do administrador tem os quatro itens', async ({ page }) => {
    const admin = new AdminPage(page);
    await admin.goto('/admin/dashboard');

    await expect(admin.sidebar.getByText('Administração')).toBeVisible();
    for (const item of ['Usuários', 'Clientes', 'Contatos', 'Auditoria']) {
        await expect(admin.sidebar.getByRole('link', { name: item })).toBeVisible();
    }
});

test('@smoke o modal de usuário passa no axe', async ({ page, axe }) => {
    const admin = new AdminPage(page);
    await admin.goto('/admin/users');

    await page.getByRole('button', { name: 'Convidar usuário' }).first().click();
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

/** Abre o modal de Novo contato na ficha da WCJ. */
async function openNewContact(admin: AdminPage) {
    await openWcjSheet(admin);
    await admin.page.getByRole('button', { name: 'Novo contato' }).first().click();
    await expect(admin.dialog).toBeVisible();
}

/**
 * Cria um contato na ficha da WCJ, abre o diálogo de anonimizar dele, roda o axe e, no fim,
 * cancela e exclui o contato pela tela. Se o teste cair no meio, o finally apaga pelo nome: o
 * banco do e2e não fica com sobra.
 */
async function withAnonymizeDialog(admin: AdminPage, check: () => Promise<void>) {
    const name = `E2E Contato ${Date.now()}`;
    try {
        await openNewContact(admin);
        await admin.dialog.getByLabel('Nome').fill(name);
        await admin.dialog.getByRole('button', { name: 'Criar contato' }).click();
        await expect(admin.dialog).toBeHidden();

        await admin.page.getByRole('button', { name: `Anonimizar ${name}` }).click();
        await expect(admin.dialog).toBeVisible();
        await expect(admin.dialog.getByText('Digite ANONIMIZAR para confirmar.')).toBeVisible();
        await check();

        await admin.dialog.getByRole('button', { name: 'Cancelar' }).click();
        await admin.page.getByRole('button', { name: `Excluir ${name}` }).click();
        await admin.dialog.getByRole('button', { name: 'Excluir' }).click();
        await expect(admin.page.getByText(name)).toHaveCount(0);
    } finally {
        deleteContactsNamed(name);
    }
}

test('@smoke o modal de contato passa no axe', async ({ page, axe }) => {
    const admin = new AdminPage(page);
    await openNewContact(admin);

    const results = await axe().analyze();
    expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
});

test('@smoke o diálogo de anonimizar contato passa no axe', async ({ page, axe }) => {
    const admin = new AdminPage(page);
    await withAnonymizeDialog(admin, async () => {
        const results = await axe().analyze();
        expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
    });
});

/** Abre o detalhe da linha mais recente: o login do e2e já gravou uma linha (G3). */
async function openAuditDetail(admin: AdminPage) {
    await admin.goto('/admin/audit-logs');
    await expect(admin.heading).toHaveText('Auditoria');
    await admin.page
        .getByRole('button', { name: /^Ver detalhes/ })
        .first()
        .click();
    await expect(admin.dialog).toBeVisible();
    await expect(admin.dialog.getByText(/IP /)).toBeVisible();
}

test('@smoke o detalhe da auditoria passa no axe', async ({ page, axe }) => {
    const admin = new AdminPage(page);
    await openAuditDetail(admin);

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
        { path: '/admin/contacts', title: 'Contatos' },
        { path: '/admin/audit-logs', title: 'Auditoria' },
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

        await page.getByRole('button', { name: 'Convidar usuário' }).first().click();
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

    test('@smoke o modal de contato passa no axe em dark mode', async ({ page, axe }) => {
        const admin = new AdminPage(page);
        await openWcjSheet(admin);
        await page.evaluate(() => document.documentElement.classList.add('dark'));

        await page.getByRole('button', { name: 'Novo contato' }).first().click();
        await expect(admin.dialog).toBeVisible();

        const results = await axe().analyze();
        expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
    });

    test('@smoke o detalhe da auditoria passa no axe em dark mode', async ({ page, axe }) => {
        const admin = new AdminPage(page);
        await openAuditDetail(admin);

        await page.evaluate(() => document.documentElement.classList.add('dark'));
        await expect(page.locator('html')).toHaveClass(/dark/);

        const results = await axe().analyze();
        expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
    });

    test('@smoke o diálogo de anonimizar contato passa no axe em dark mode', async ({ page, axe }) => {
        const admin = new AdminPage(page);
        await withAnonymizeDialog(admin, async () => {
            await page.evaluate(() => document.documentElement.classList.add('dark'));
            await expect(page.locator('html')).toHaveClass(/dark/);

            const results = await axe().analyze();
            expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([]);
        });
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
