import type { Page } from '@playwright/test';

import { expect, test } from './fixtures';
import { createNoticeForAdmin, createSprintProject, createUser } from './helpers/dados';

/**
 * B3, PR 3 pela tela (F3 §03, §04 e §07; P26): o gestor arquiva o card pela ficha com motivo, ele sai
 * do quadro e volta pelo filtro *Arquivados*; desarquivar o devolve ao Backlog; o card nunca usado se
 * exclui de verdade e a tela volta ao quadro; o sino marca o aviso como lido. Com PRINTS=1, guarda os
 * prints do PR em test-results/prints-b3-arquivar.
 */
const PREFIX = `A${Date.now().toString(36).slice(-5).toUpperCase()}`;

async function print(page: Page, name: string) {
    // animations: 'disabled' leva a animação ao fim: o print não pega o diálogo entrando e o menu saindo.
    if (process.env.PRINTS)
        await page.screenshot({ path: `test-results/prints-b3-arquivar/${name}.png`, fullPage: true, animations: 'disabled' });
}

function column(page: Page, name: string) {
    return page.getByRole('region', { name: new RegExp(`^${name}`) });
}

async function cardAction(page: Page, key: string, action: string) {
    await page.getByRole('button', { name: `Ações de ${key}` }).click();
    await page.getByRole('menuitem', { name: action }).click();
}

async function toggleArchived(page: Page) {
    await page.getByRole('button', { name: /^Filtros/ }).click();
    await page.getByRole('switch', { name: 'Arquivados' }).click();
    await page.keyboard.press('Escape');
}

test('@smoke @critical o gestor arquiva, acha nos Arquivados, desarquiva e exclui o card nunca usado', async ({ page, axe }) => {
    test.setTimeout(90_000);
    const devId = createUser(`Dev ${PREFIX}`, 3);
    const { projectId } = createSprintProject(PREFIX, devId);
    const key = `${PREFIX}-2`;

    // 1. Arquivar pela ficha, com motivo (P26).
    await page.goto(`/projetos/${projectId}/cards/${key}`);
    await cardAction(page, key, 'Arquivar');
    const dialog = page.getByRole('dialog', { name: `Arquivar ${key}?` });
    await dialog.getByLabel('Motivo').fill('Duplicado do card de busca');
    expect((await axe().include('[role="dialog"]').analyze()).violations).toEqual([]);
    await dialog.getByRole('button', { name: 'Arquivar' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.getByText(`${key} arquivado. Ele volta pelo filtro Arquivados do quadro.`)).toBeVisible();
    await expect(page.getByText(/^Arquivado em \d{2}\/\d{2}\/\d{4}: Duplicado do card de busca$/)).toBeVisible();

    // 2. Saiu do quadro; o filtro Arquivados mostra a lista (F3 §03).
    await page.goto(`/projetos/${projectId}/quadro`);
    await expect(column(page, 'Backlog').getByText('Tela de recurso de multa')).toBeVisible();
    await expect(page.getByText('Filtro por placa')).toBeHidden();
    await toggleArchived(page);
    const lista = page.getByRole('table', { name: 'Cards arquivados' });
    await expect(lista.getByRole('row', { name: new RegExp(`${key}.*Filtro por placa.*Duplicado do card de busca`) })).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '01-arquivados');

    // 3. Da lista para a ficha; desarquivar devolve ao Backlog.
    await lista.getByRole('link', { name: 'Filtro por placa' }).click();
    await expect(page).toHaveURL(new RegExp(`/cards/${key}$`));
    await cardAction(page, key, 'Desarquivar');
    await expect(page.getByText(`${key} desarquivado: está no fim do Backlog.`)).toBeVisible();
    await page.goto(`/projetos/${projectId}/quadro`);
    await expect(column(page, 'Backlog').getByText('Filtro por placa')).toBeVisible();

    // 4. Card nunca usado: criado agora, se exclui de verdade e a tela volta ao quadro.
    await page.getByRole('button', { name: 'Novo card', exact: true }).click();
    await page.getByLabel('Título do novo card').fill('Card criado por engano');
    await page.keyboard.press('Enter');
    await expect(column(page, 'Backlog').getByText('Card criado por engano')).toBeVisible();
    await page.getByLabel('Título do novo card').press('Escape');
    await page.goto(`/projetos/${projectId}/cards/${PREFIX}-3`);
    await cardAction(page, `${PREFIX}-3`, 'Excluir');
    const confirm = page.getByRole('dialog', { name: `Excluir ${PREFIX}-3?` });
    await expect(confirm.getByText(`A chave ${PREFIX}-3 não volta a ser usada.`)).toBeVisible();
    await print(page, '02-excluir');
    await confirm.getByRole('button', { name: 'Excluir de verdade' }).click();
    await expect(page).toHaveURL(new RegExp(`/projetos/${projectId}/quadro$`));
    await expect(page.getByText(`${PREFIX}-3 excluído.`)).toBeVisible();
    await expect(page.getByText('Card criado por engano')).toBeHidden();
});

test('@smoke o sino mostra o aviso não lido e marca como lido', async ({ page, axe }) => {
    const text = `Você é o dev de ${PREFIX}-9 — Aviso do e2e, definido por Fulano.`;
    createNoticeForAdmin(text, '/projetos');

    await page.goto('/projetos');
    const bell = page.getByRole('button', { name: /^Avisos, \d+ não lidos?$/ });
    await expect(bell).toBeVisible();
    await bell.click();
    await expect(page.getByRole('link', { name: text })).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '03-sino');

    await page.getByRole('button', { name: 'Marcar todas como lidas' }).click();
    await expect(page.getByRole('button', { name: 'Avisos', exact: true })).toBeVisible();
});
