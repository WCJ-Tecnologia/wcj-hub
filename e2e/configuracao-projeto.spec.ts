import type { Page } from '@playwright/test';
import { randomBytes } from 'node:crypto';

import { expect, test } from './fixtures';
import { createUser } from './helpers/dados';
import { LoginPage } from './pages/login.page';

/**
 * B2 passo 2 pela tela (F2 §01 a §04): criar o projeto com o "Novo cliente" inline (N6),
 * alocar pessoas (P5), mexer nas etapas (P10–P12), nas áreas (P18), nos padrões (P15, P46) e nos
 * motivos de retrabalho (P40). Com PRINTS=1, guarda os prints do PR em test-results/prints-b2.
 */
const PREFIX = `C${Date.now().toString(36).slice(-5).toUpperCase()}`;

/** CNPJ numérico válido e novo a cada rodada: o spec pode rodar de novo no mesmo banco (N5 recusa o repetido). */
function freshCnpj(): string {
    const base = `${Date.now()}`.slice(-8).padStart(8, '1') + '0001';
    const digit = (numbers: string) => {
        const weights = numbers.length === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        const rest = [...numbers].reduce((sum, char, index) => sum + Number(char) * weights[index], 0) % 11;
        return rest < 2 ? '0' : String(11 - rest);
    };
    const first = digit(base);
    return base + first + digit(base + first);
}

async function print(page: Page, name: string) {
    if (process.env.PRINTS) await page.screenshot({ path: `test-results/prints-b2/${name}.png` });
}

async function choose(page: Page, trigger: string, option: string) {
    await page.locator(`#${trigger}`).click();
    await page.getByRole('option', { name: option, exact: true }).click();
}

test('o gestor cria o projeto com cliente novo e configura pessoas, etapas, áreas e padrões', async ({ page, axe }) => {
    test.setTimeout(90_000);
    const dev = `Dev ${PREFIX}`;
    createUser(dev, 3);

    // 1. Novo projeto, com o cliente criado sem sair do formulário (N6).
    await page.goto('/projetos');
    await page.getByRole('button', { name: 'Novo projeto' }).first().click();
    const projectDialog = page.getByRole('dialog', { name: 'Novo projeto' });
    await projectDialog.getByRole('button', { name: 'Novo cliente' }).click();
    const clientDialog = page.getByRole('dialog', { name: 'Novo cliente' });
    await clientDialog.getByLabel('Nome').fill(`Padaria ${PREFIX}`);
    await clientDialog.getByLabel('CNPJ').fill(freshCnpj());
    await print(page, '01-novo-cliente-inline');
    await clientDialog.getByRole('button', { name: 'Criar cliente' }).click();
    await expect(clientDialog).toBeHidden();
    await expect(projectDialog.locator('#client_id')).toHaveText(`Padaria ${PREFIX}`);

    await choose(page, 'manager_id', 'E2E Admin');
    await projectDialog.getByLabel('Prefixo').fill(PREFIX);
    await projectDialog.getByLabel('Nome').fill(`Configuração ${PREFIX}`);
    await choose(page, 'type', 'Produto contínuo');
    await print(page, '02-novo-projeto-com-cliente-escolhido');
    await projectDialog.getByRole('button', { name: 'Criar projeto' }).click();
    await expect(projectDialog).toBeHidden();

    // 2. Configurar → Pessoas, vazia.
    await page.getByRole('button', { name: `Configurar Configuração ${PREFIX}` }).click();
    await expect(page).toHaveURL(/\/configuracao$/);
    await expect(page.getByRole('heading', { name: 'Ninguém alocado ainda.' })).toBeVisible();
    await print(page, '03-pessoas-vazia');
    expect((await axe().analyze()).violations).toEqual([]);

    await page.getByRole('button', { name: 'Alocar pessoas' }).click();
    const allocate = page.getByRole('dialog', { name: 'Alocar pessoas' });
    await allocate.getByLabel('Buscar pessoas').fill(dev.slice(0, 6));
    await allocate.getByText(dev, { exact: true }).click();
    await print(page, '04-alocar-pessoas');
    await allocate.getByRole('button', { name: 'Alocar' }).click();
    await expect(allocate).toBeHidden();
    await expect(page.getByRole('table', { name: 'Pessoas do projeto' }).getByText(dev)).toBeVisible();
    await print(page, '05-pessoas-com-alocado');

    // 3. Etapas: o quadro padrão na ordem da P10, etapa nova no fim, remover sem cards, reordenar pelo teclado.
    await page.getByRole('tab', { name: 'Etapas' }).click();
    await expect(page).toHaveURL(/aba=etapas/);
    const stages = page.getByRole('list', { name: 'Etapas do quadro, na ordem' });
    await expect(stages.locator('li p.truncate')).toHaveText([
        'Support',
        'Backlog',
        'To do',
        'Paused',
        'Doing',
        'Code-review',
        'In test',
        'Done',
    ]);
    await expect(page.getByRole('button', { name: 'Remover Backlog' })).toHaveCount(0);
    await print(page, '06-etapas');
    expect((await axe().analyze()).violations).toEqual([]);

    await page.getByRole('button', { name: 'Nova etapa' }).click();
    const stageDialog = page.getByRole('dialog', { name: 'Nova etapa' });
    await stageDialog.getByLabel('Nome').fill('Homologação');
    await choose(page, 'stage-role', 'Teste');
    await stageDialog.getByRole('button', { name: 'Criar etapa' }).click();
    await expect(stageDialog).toBeHidden();
    await expect(stages.locator('li p.truncate').last()).toHaveText('Homologação');

    await page.getByRole('button', { name: 'Remover Paused' }).click();
    const removeDialog = page.getByRole('dialog', { name: 'Remover a etapa Paused?' });
    await print(page, '07-remover-etapa-sem-cards');
    await removeDialog.getByRole('button', { name: 'Remover etapa' }).click();
    await expect(removeDialog).toBeHidden();
    await expect(stages.getByText('Paused', { exact: true })).toHaveCount(0);

    const handle = page.getByRole('button', { name: 'Arrastar Homologação' });
    await handle.focus();
    await page.keyboard.press('Space');
    // O dnd-kit marca a alça como pressionada quando pegou a etapa; só então as setas movem.
    await expect(handle).toHaveAttribute('aria-pressed', 'true');
    await page.keyboard.press('ArrowUp');
    await page.waitForTimeout(250); // a animação do dnd-kit termina antes de soltar
    await page.keyboard.press('Space');
    await expect(handle).not.toHaveAttribute('aria-pressed', 'true');
    await expect(stages.locator('li p.truncate')).toHaveText([
        'Support',
        'Backlog',
        'To do',
        'Doing',
        'Code-review',
        'In test',
        'Homologação',
        'Done',
    ]);
    await page.reload();
    await expect(stages.locator('li p.truncate')).toHaveText([
        'Support',
        'Backlog',
        'To do',
        'Doing',
        'Code-review',
        'In test',
        'Homologação',
        'Done',
    ]);
    await print(page, '08-etapas-depois');

    // 4. Áreas: vazia, criar, repetido sem acento recusado, desativar.
    await page.getByRole('tab', { name: 'Áreas' }).click();
    await expect(page.getByRole('heading', { name: 'Nenhuma área ativa.' })).toBeVisible();
    await page.getByRole('button', { name: 'Nova área' }).first().click();
    let areaDialog = page.getByRole('dialog', { name: 'Nova área' });
    await areaDialog.getByLabel('Nome').fill('Âncora');
    await areaDialog.getByRole('button', { name: 'Criar área' }).click();
    await expect(areaDialog).toBeHidden();
    await page.getByRole('button', { name: 'Nova área' }).click();
    areaDialog = page.getByRole('dialog', { name: 'Nova área' });
    await areaDialog.getByLabel('Nome').fill('ancora');
    await areaDialog.getByRole('button', { name: 'Criar área' }).click();
    await expect(areaDialog.getByText('Já existe a área Âncora neste projeto.')).toBeVisible();
    await print(page, '09-area-repetida-recusada');
    await areaDialog.getByRole('button', { name: 'Cancelar' }).click();
    await page.getByRole('button', { name: 'Desativar Âncora' }).click();
    await page.getByRole('dialog', { name: 'Desativar área' }).getByRole('button', { name: 'Desativar' }).click();
    await expect(page.getByRole('heading', { name: 'Nenhuma área ativa.' })).toBeVisible();
    await page.getByRole('checkbox', { name: 'Mostrar desativados' }).click();
    await expect(page.getByRole('table', { name: 'Áreas do projeto' }).getByText('Âncora')).toBeVisible();
    await print(page, '10-areas-com-desativada');

    // 5. Padrões.
    await page.getByRole('tab', { name: 'Padrões' }).click();
    await page.getByLabel('Pedir motivo na pausa').click();
    await page.getByLabel(/Duração da sprint/).fill('15');
    await page.getByRole('button', { name: 'Salvar padrões' }).click();
    await expect(page.getByText('Padrões salvos. Valem dali para a frente.')).toBeVisible();
    await print(page, '11-padroes');
    await page.reload();
    await expect(page.getByLabel(/Duração da sprint/)).toHaveValue('15');
    await expect(page.getByLabel('Pedir motivo na pausa')).not.toBeChecked();
});

test('o Administrador mantém os motivos de retrabalho; o Outro é fixo', async ({ page, axe }) => {
    await page.goto('/admin/listas');
    const table = page.getByRole('table', { name: 'Motivos de retrabalho' });
    await expect(table.getByRole('row').last()).toContainText('Outro');
    await expect(page.getByRole('button', { name: 'Excluir Outro' })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Desativar Outro' })).toHaveCount(0);
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '12-listas');

    const label = `Lentidão ${PREFIX}`;
    await page.getByRole('button', { name: 'Novo motivo' }).click();
    const dialog = page.getByRole('dialog', { name: 'Novo motivo' });
    await dialog.getByLabel('Nome').fill(label);
    await dialog.getByLabel('Pede texto').click();
    await print(page, '13-novo-motivo');
    await dialog.getByRole('button', { name: 'Criar motivo' }).click();
    await expect(dialog).toBeHidden();
    await expect(table.getByText(label)).toBeVisible();

    await page.getByRole('button', { name: `Excluir ${label}` }).click();
    await page.getByRole('dialog', { name: 'Excluir motivo' }).getByRole('button', { name: 'Excluir' }).click();
    await expect(table.getByText(label)).toHaveCount(0);
});

test.describe('quem não é Administrador', () => {
    // Sem a sessão do admin: este teste entra com um dev criado aqui. O 403 da rota é de propósito.
    test.use({ storageState: { cookies: [], origins: [] }, expectedConsoleErrors: [/status of 403/] });

    test('o dev não vê Listas no menu e a rota responde 403 (LST-04)', async ({ page }) => {
        const name = `Dev Listas ${PREFIX}`;
        // Senha de teste gerada aqui, só para este usuário do banco de e2e.
        const password = `Dev${randomBytes(9).toString('hex')}7`;
        createUser(name, 3, password);

        const login = new LoginPage(page);
        await login.goto();
        await login.login(`${name.toLowerCase().replace(/[^a-z0-9]+/g, '.')}@e2e.test`, password);
        await expect(page).toHaveURL(/\/admin\/dashboard$/);

        const menu = page.getByRole('navigation', { name: 'Menu principal' });
        await expect(menu.getByRole('link', { name: 'Projetos' })).toBeVisible();
        await expect(menu.getByRole('link', { name: 'Listas' })).toHaveCount(0);

        const response = await page.goto('/admin/listas');
        expect(response?.status()).toBe(403);
    });
});
