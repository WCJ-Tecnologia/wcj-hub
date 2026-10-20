import type { Page } from '@playwright/test';

import { expect, test } from './fixtures';
import { createSprintProject, createUser } from './helpers/dados';

/**
 * B3, PR 2 pela tela (F3 §02 a §04): do quadro, o título do card abre a ficha; nela, critério de
 * aceite, comentário e link de PR entram e ficam depois de recarregar. Com PRINTS=1, guarda os
 * prints do PR em test-results/prints-b3-ficha.
 */
const PREFIX = `F${Date.now().toString(36).slice(-5).toUpperCase()}`;

async function print(page: Page, name: string) {
    if (process.env.PRINTS) await page.screenshot({ path: `test-results/prints-b3-ficha/${name}.png`, fullPage: true });
}

function section(page: Page, title: string) {
    return page.getByRole('button', { name: new RegExp(`^${title}`) });
}

test('@smoke @critical a ficha abre do quadro e grava critério, comentário e PR', async ({ page, axe }) => {
    test.setTimeout(90_000);
    const devId = createUser(`Dev ${PREFIX}`, 3);
    const { projectId } = createSprintProject(PREFIX, devId);

    // 1. Do quadro para a ficha, pelo título do card.
    await page.goto(`/projetos/${projectId}/quadro`);
    await page.getByRole('link', { name: 'Tela de recurso de multa' }).click();
    await expect(page).toHaveURL(new RegExp(`/projetos/${projectId}/cards/${PREFIX}-1$`));
    await expect(page.getByRole('heading', { name: 'Tela de recurso de multa' })).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '01-ficha');

    // 2. Critério de aceite: CA_1 (P17, T9).
    await section(page, 'Critérios de aceite').click();
    await page.getByRole('button', { name: 'Adicionar critério' }).first().click();
    await page.getByLabel('Novo critério').fill('O recurso aceita PDF de até 5 MB');
    await page.getByRole('button', { name: 'Adicionar', exact: true }).click();
    await expect(page.getByRole('list', { name: 'Critérios de aceite' }).getByText('O recurso aceita PDF de até 5 MB')).toBeVisible();
    await expect(page.getByText('CA_1')).toBeVisible();

    // 3. Comentário com autor (P24).
    await section(page, 'Comentários').click();
    await page.getByLabel('Novo comentário').fill('Layout aprovado pelo cliente.');
    await page.getByLabel('Novo comentário').press('Control+Enter');
    await expect(page.getByRole('list', { name: 'Comentários' }).getByText('Layout aprovado pelo cliente.')).toBeVisible();

    // 4. Link de PR (P23).
    await section(page, 'PRs').click();
    await page.getByRole('button', { name: 'Adicionar link' }).first().click();
    await page.getByLabel('Link do PR').fill(`https://github.com/WCJ-Tecnologia/${PREFIX.toLowerCase()}/pull/7`);
    await page.getByRole('button', { name: 'Adicionar', exact: true }).click();
    // O kit mostra o link formatado: "github.com/WCJ-Tecnologia/<repo> · #7", com quem colou.
    const prs = page.getByRole('list', { name: 'PRs do card' });
    await expect(prs.getByRole('link', { name: new RegExp(`${PREFIX.toLowerCase()} · #7`) })).toBeVisible();
    await expect(prs.getByText('por E2E Admin')).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '02-ficha-preenchida');

    // 5. Recarregar mostra o mesmo: o que a tela mostrou foi o que o servidor gravou.
    await page.reload();
    await section(page, 'Comentários').click();
    await expect(page.getByText('Layout aprovado pelo cliente.')).toBeVisible();
});
