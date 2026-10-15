import type { Page } from '@playwright/test';

import { expect, test } from './fixtures';
import { createSprintProject, createUser } from './helpers/dados';

/**
 * B3, PR 1 pela tela (F3 §01 e §04): o quadro abre com as colunas, o card rápido nasce no Backlog,
 * levar ao To do sem sprint aberta abre a *Nova sprint* (R5) e põe o card nela, e a pausa pede o
 * motivo (P15). O movimento vai pelo *Mover para…* do menu do card, o mesmo pedido do arrastar.
 * Com PRINTS=1, guarda os prints do PR em test-results/prints-b3.
 */
const PREFIX = `Q${Date.now().toString(36).slice(-5).toUpperCase()}`;

async function print(page: Page, name: string) {
    if (process.env.PRINTS) await page.screenshot({ path: `test-results/prints-b3/${name}.png`, fullPage: true });
}

function column(page: Page, name: string) {
    return page.getByRole('region', { name: new RegExp(`^${name}`) });
}

/**
 * *Mover para…* pelo teclado, como o aviso do quadro ensina: o mouse indo do submenu ao item passa
 * por cima dos outros itens e fecha o submenu.
 */
async function moveTo(page: Page, key: string, stage: string) {
    await page.getByRole('button', { name: `Ações de ${key}` }).click();
    await page.getByRole('menuitem', { name: 'Mover para…' }).click();
    await page.keyboard.press('ArrowRight');
    for (let step = 0; step < 10; step++) {
        const focused = await page.evaluate(() => document.activeElement?.textContent?.trim());
        if (focused === stage) break;
        await page.keyboard.press('ArrowDown');
    }
    await page.keyboard.press('Enter');
}

test('@smoke @critical o gestor cria card rápido, põe na sprint nova e pausa com motivo', async ({ page, axe }) => {
    test.setTimeout(90_000);
    const devId = createUser(`Dev ${PREFIX}`, 3);
    const { projectId } = createSprintProject(PREFIX, devId);

    // 1. O quadro: as colunas na ordem da P10, os dois cards no Backlog.
    await page.goto(`/projetos/${projectId}/quadro`);
    await expect(page.getByRole('heading', { name: `Sprints ${PREFIX}`, level: 1 })).toBeVisible();
    await expect(column(page, 'Backlog').getByText('Tela de recurso de multa')).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '01-quadro');

    // 2. Card rápido: título e Enter, no Backlog, com a próxima chave (P16, P17).
    await page.getByRole('button', { name: 'Novo card', exact: true }).click();
    await page.getByLabel('Título do novo card').fill('Exportar multas em CSV');
    await page.keyboard.press('Enter');
    await expect(column(page, 'Backlog').getByText('Exportar multas em CSV')).toBeVisible();
    await expect(column(page, 'Backlog').getByText(`${PREFIX}-3`)).toBeVisible();
    await print(page, '02-card-rapido');
    // O campo fica aberto para o próximo título (como no Trello); Esc fecha.
    await page.getByLabel('Título do novo card').press('Escape');
    await expect(page.getByLabel('Título do novo card')).toBeHidden();

    // 3. Para o To do sem sprint aberta: a Nova sprint já preenchida, e o card entra nela (P45, R5).
    await moveTo(page, `${PREFIX}-1`, 'To do');
    const sprint = page.getByRole('dialog', { name: 'Nova sprint' });
    await expect(sprint.getByLabel('Nome')).toHaveValue('Sprint 1');
    expect((await axe().include('[role="dialog"]').analyze()).violations).toEqual([]);
    await print(page, '03-nova-sprint');
    await sprint.getByRole('button', { name: 'Criar sprint' }).click();
    await expect(sprint).toBeHidden();
    await expect(column(page, 'To do').getByText('Tela de recurso de multa')).toBeVisible();
    await expect(page.getByText('Sprint 1', { exact: false }).first()).toBeVisible();

    // 4. Para a pausa: o motivo é pedido (P15) e aparece no card.
    await moveTo(page, `${PREFIX}-1`, 'Paused');
    const pause = page.getByRole('dialog', { name: `Pausar ${PREFIX}-1` });
    await pause.getByRole('button', { name: 'Pausar' }).click();
    await expect(pause.getByText('Informe o motivo da pausa.')).toBeVisible();
    await pause.getByLabel('Motivo da pausa').fill('Esperando o layout do cliente');
    await pause.getByRole('button', { name: 'Pausar' }).click();
    await expect(pause).toBeHidden();
    await expect(column(page, 'Paused').getByText('Tela de recurso de multa')).toBeVisible();
    await print(page, '04-pausado');

    // 5. Recarregar mostra o mesmo: o que a tela mostrou foi o que o servidor gravou.
    await page.reload();
    await expect(column(page, 'Paused').getByText('Tela de recurso de multa')).toBeVisible();
    await expect(column(page, 'Backlog').getByText('Exportar multas em CSV')).toBeVisible();
});
