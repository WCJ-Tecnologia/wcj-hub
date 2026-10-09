import type { Page } from '@playwright/test';

import { expect, test } from './fixtures';
import { AdminPage } from './pages/admin.page';

/**
 * CR-1-20 (code review do B1, ciclo 1): toda tela é conferida em 375 px (stacks/ui.md §3, §8). Em
 * cada tela do Núcleo, o título aparece e a página não rola na horizontal; na auditoria, o detalhe
 * da linha mais recente abre e cabe.
 */
test.use({ viewport: { width: 375, height: 812 } });

const SCREENS = [
    { path: '/admin/dashboard', title: 'Dashboard' },
    { path: '/admin/users', title: 'Usuários' },
    { path: '/admin/clients', title: 'Clientes' },
    { path: '/admin/contacts', title: 'Contatos' },
    { path: '/admin/audit-logs', title: 'Auditoria' },
];

const noHorizontalScroll = (page: Page) => page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);

for (const screen of SCREENS) {
    test(`@smoke ${screen.title} em 375 px: título visível e sem rolagem horizontal`, async ({ page }) => {
        const admin = new AdminPage(page);
        await admin.goto(screen.path);

        await expect(admin.heading).toHaveText(screen.title);
        await expect(admin.heading).toBeVisible();
        expect(await noHorizontalScroll(page)).toBe(true);
    });
}

test('@smoke a ficha do cliente em 375 px: título visível e sem rolagem horizontal', async ({ page }) => {
    const admin = new AdminPage(page);
    // A WCJ existe em toda instalação (N7); no celular, o link visível é o do card.
    await admin.goto('/admin/clients');
    await page.getByRole('link', { name: 'WCJ Tecnologia', exact: true }).filter({ visible: true }).first().click();

    await expect(admin.heading).toHaveText('WCJ Tecnologia');
    await expect(admin.heading).toBeVisible();
    expect(await noHorizontalScroll(page)).toBe(true);
});

test('@smoke o detalhe da auditoria em 375 px abre e cabe na tela', async ({ page }) => {
    const admin = new AdminPage(page);
    // O login do e2e já gravou uma linha (G3).
    await admin.goto('/admin/audit-logs');
    await expect(admin.heading).toHaveText('Auditoria');

    // No celular, o card inteiro da linha é o botão do detalhe (a lista em cards da DataTable).
    await page.getByRole('list', { name: 'Registros de auditoria' }).getByRole('button').first().click();
    await expect(admin.dialog).toBeVisible();
    await expect(admin.dialog.getByText(/IP /)).toBeVisible();

    const box = await admin.dialog.boundingBox();
    expect(box).not.toBeNull();
    expect(box!.x).toBeGreaterThanOrEqual(0);
    expect(box!.x + box!.width).toBeLessThanOrEqual(375);
    expect(await noHorizontalScroll(page)).toBe(true);
    expect(await admin.dialog.evaluate((dialog) => dialog.scrollWidth <= dialog.clientWidth)).toBe(true);
});
