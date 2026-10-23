import type { Locator, Page } from '@playwright/test';

import { expect, test } from './fixtures';
import { createTestProject, createUser } from './helpers/dados';

/**
 * B4, PR 1 pela tela (F4 §01 a §04; spec b4-teste-e-qa): na seção Teste da ficha, o admin (que não
 * é o dev da tarefa) marca o teste cruzado, aprova e reprova itens do roteiro, anexa a evidência no
 * item reprovado e devolve para desenvolvimento pelo atalho, com a origem QA já escolhida; na volta,
 * o roteiro inteiro fica A testar e a marca do teste cruzado sai (P34, P37). Com PRINTS=1, guarda os
 * prints do PR em test-results/prints-b4-teste.
 */
const PREFIX = `Q${Date.now().toString(36).slice(-5).toUpperCase()}`;

/** Um PNG de 1×1 px, para a evidência. */
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64');

/** O print do PR: a seção Teste recortada, ou a tela quando há diálogo aberto. */
async function print(page: Page, name: string, target: Locator | null = null) {
    if (!process.env.PRINTS) return;
    const path = `test-results/prints-b4-teste/${name}.png`;
    if (!target) return void (await page.screenshot({ path }));
    // A barra do topo é fixa: a seção desce abaixo dela antes do recorte, numa janela alta que a cabe inteira.
    await page.setViewportSize({ width: 1280, height: 1200 });
    await target.evaluate((element) => window.scrollTo(0, element.getBoundingClientRect().top + window.scrollY - 96));
    const box = await target.boundingBox();
    if (box) await page.screenshot({ path, clip: { x: box.x - 8, y: box.y - 8, width: box.width + 16, height: box.height + 16 } });
    await page.setViewportSize({ width: 1280, height: 720 });
}

test('@smoke @critical a seção Teste marca o teste cruzado, roda o roteiro com evidência e devolve', async ({ page, axe }) => {
    test.setTimeout(90_000);
    const devId = createUser(`Dev ${PREFIX}`, 3);
    const projectId = createTestProject(PREFIX, devId);

    await page.goto(`/projetos/${projectId}/cards/${PREFIX}-1`);
    await expect(page.getByRole('heading', { name: 'Emitir segunda via do boleto' })).toBeVisible();
    await page.getByRole('button', { name: /^Teste/ }).click();
    const secao = page.locator('[data-slot="w-task-test-section-organism"]');
    await expect(secao.getByText('Ainda não marcado')).toBeVisible();
    await expect(secao.getByText('Roteiro: 4 itens · 0 aprovados · 0 reprovados · 4 a testar')).toBeVisible();

    // 1. Teste cruzado: quem não é o dev marca (P34).
    await secao.getByRole('button', { name: 'Marquei: testado por mim' }).click();
    await expect(secao.getByText(/^Testado por E2E Admin em \d{2}\/\d{2}\/\d{4} às \d{2}:\d{2}$/)).toBeVisible();
    await expect(secao.getByRole('button', { name: 'Desfazer a marca' })).toBeVisible();

    // 2. Roteiro: CA_1 aprovado, CA_2 reprovado, cada um com a última marcação (P36).
    await secao.getByRole('group', { name: 'Resultado de CA_1', exact: true }).getByRole('button', { name: 'Aprovado' }).click();
    await expect(
        secao.getByRole('group', { name: 'Resultado de CA_1', exact: true }).getByRole('button', { name: 'Aprovado' }),
    ).toHaveAttribute('aria-pressed', 'true');
    await secao.getByRole('group', { name: 'Resultado de CA_2', exact: true }).getByRole('button', { name: 'Reprovado' }).click();
    await expect(secao.getByText('Roteiro: 4 itens · 1 aprovado · 1 reprovado · 2 a testar')).toBeVisible();
    await expect(secao.locator('li[data-result="failed"]')).toHaveCount(1);
    await expect(secao.getByText(/E2E Admin, \d{2}\/\d{2}\/\d{4} às \d{2}:\d{2}/)).toHaveCount(2);

    // 3. Evidência no item reprovado: anexo ligado ao critério (P36).
    const chooser = page.waitForEvent('filechooser');
    await secao.getByRole('button', { name: 'Anexar evidência em CA_2', exact: true }).click();
    await (await chooser).setFiles({ name: 'juros-errados.png', mimeType: 'image/png', buffer: PNG });
    await expect(
        secao.getByRole('list', { name: 'Evidências de CA_2', exact: true }).getByRole('link', { name: 'juros-errados.png' }),
    ).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '01-roteiro-com-evidencia', secao);

    // 4. Devolver pelo roteiro: o diálogo do F3 com destino To do e a origem QA já escolhida (P39).
    await secao.getByRole('button', { name: 'Devolver para desenvolvimento' }).last().click();
    const dialog = page.getByRole('dialog', { name: `Devolver ${PREFIX}-1 para To do` });
    await expect(dialog.getByRole('combobox', { name: 'Origem' })).toHaveText('QA');
    await print(page, '02-devolver-com-origem-qa');
    await dialog.getByRole('combobox', { name: 'Motivo' }).click();
    await page.getByRole('option', { name: 'Regra de negócio' }).click();
    await dialog.getByRole('button', { name: 'Devolver', exact: true }).click();
    await expect(dialog).toBeHidden();

    // 5. Na volta: o roteiro inteiro A testar, sem a última marcação, e a marca do teste cruzado limpa (P34, P37).
    await expect(secao.getByText('Roteiro: 4 itens · 0 aprovados · 0 reprovados · 4 a testar')).toBeVisible();
    await expect(secao.getByText('Ainda não marcado')).toBeVisible();
    await expect(secao.getByText(/E2E Admin, \d{2}\/\d{2}/)).toHaveCount(0);
    // A evidência antiga fica (F4 §10.5).
    await expect(secao.getByRole('button', { name: '1 evidência' })).toBeVisible();
    await print(page, '03-depois-do-retrabalho', secao);
});
