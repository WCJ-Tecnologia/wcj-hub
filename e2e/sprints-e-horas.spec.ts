import type { Page } from '@playwright/test';

import { expect, test } from './fixtures';
import { createSprintProject, createUser } from './helpers/dados';

/**
 * B5 pela tela (F5 §01 a §05): criar a sprint, ver as tarefas e a capacidade, horas previstas na
 * linha, encerrar mandando cada tarefa para a próxima ou para o Backlog, e Minhas horas (lançar,
 * corrigir, excluir). Os cards entram na sprint pela rota que o quadro do B3 vai chamar. Com
 * PRINTS=1, guarda os prints do PR em test-results/prints-b5.
 */
const PREFIX = `S${Date.now().toString(36).slice(-5).toUpperCase()}`;

async function print(page: Page, name: string) {
    if (process.env.PRINTS) await page.screenshot({ path: `test-results/prints-b5/${name}.png`, fullPage: true });
}

test('@smoke o gestor cria a sprint, acompanha a capacidade e encerra decidindo o destino de cada tarefa', async ({ page, axe }) => {
    test.setTimeout(90_000);
    const devId = createUser(`Dev ${PREFIX}`, 3);
    const { projectId, taskIds } = createSprintProject(PREFIX, devId);
    const base = `/projetos/${projectId}/sprints`;

    // 1. Sem sprint: estado vazio e Nova sprint com a proposta (P46).
    await page.goto(base);
    await expect(page.getByRole('heading', { name: 'Nenhuma sprint ainda.' })).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '01-sem-sprint');

    await page.getByRole('button', { name: 'Nova sprint' }).first().click();
    const create = page.getByRole('dialog', { name: 'Nova sprint' });
    await expect(create.getByLabel('Nome')).toHaveValue('Sprint 1');
    expect((await axe().include('[role="dialog"]').analyze()).violations).toEqual([]);
    await print(page, '02-nova-sprint');
    await create.getByRole('button', { name: 'Criar sprint' }).click();
    await expect(create).toBeHidden();
    await expect(page.getByText('Sprint aberta')).toBeVisible();

    // 2. Os cards entram na sprint (a rota que o arrastar do B3 chama) e aparecem com a capacidade.
    for (const taskId of taskIds) {
        const response = await page.request.post(`/projetos/${projectId}/sprint-aberta/tarefas`, {
            form: { task_id: String(taskId) },
            headers: { 'X-XSRF-TOKEN': decodeURIComponent((await page.context().cookies()).find((c) => c.name === 'XSRF-TOKEN')!.value) },
        });
        expect(response.status()).toBeLessThan(400);
    }
    await page.goto(base);
    const tasks = page.getByRole('table', { name: 'Tarefas da Sprint 1' });
    await expect(tasks.getByText('Tela de recurso de multa')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Capacidade' })).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '03-sprint-com-tarefas-e-capacidade');

    // 3. Horas previstas na linha (P27).
    await tasks.getByRole('button', { name: `Editar horas previstas de ${PREFIX}-2` }).click();
    await tasks.getByLabel(`Horas previstas de ${PREFIX}-2`).fill('6');
    await tasks.getByRole('button', { name: 'Salvar horas previstas' }).click();
    await expect(page.getByText('Horas previstas salvas.')).toBeVisible();
    await expect(tasks.getByText('6 h', { exact: true })).toBeVisible();

    // 4. Encerrar: uma tarefa vai para a próxima, a outra volta ao Backlog (P50).
    await page.getByRole('button', { name: 'Encerrar Sprint 1' }).click();
    const close = page.getByRole('dialog', { name: 'Encerrar a Sprint 1' });
    await close.getByRole('combobox', { name: `Destino de ${PREFIX}-2` }).click();
    await page.getByRole('option', { name: 'Backlog' }).click();
    await expect(close.getByText(/1 vai para a Sprint 2; 1 volta ao Backlog/)).toBeVisible();
    expect((await axe().include('[role="dialog"]').analyze()).violations).toEqual([]);
    await print(page, '04-encerrar-sprint');
    await close.getByRole('button', { name: 'Encerrar sprint' }).click();
    await expect(close).toBeHidden();
    await expect(page.getByText('Sprint 1 encerrada. Sprint 2 aberta com 1 tarefas. 1 voltaram ao Backlog.')).toBeVisible();
    await expect(page.getByRole('table', { name: 'Tarefas da Sprint 2' }).getByText('Tela de recurso de multa')).toBeVisible();
    await print(page, '05-depois-de-encerrar');
});

test('@smoke Minhas horas: lançar, corrigir e excluir', async ({ page, axe }) => {
    test.setTimeout(60_000);
    const devId = createUser(`Dev H${PREFIX}`, 3);
    // A tarefa desta rodada pela chave: o banco do e2e guarda lançamentos de rodadas anteriores.
    const key = `${`H${PREFIX}`.slice(0, 10)}-2`;
    createSprintProject(`H${PREFIX}`.slice(0, 10), devId);

    await page.goto('/minhas-horas');
    await expect(page.getByRole('heading', { name: 'Minhas horas', level: 1 })).toBeVisible();
    expect((await axe().analyze()).violations).toEqual([]);

    await page.getByRole('button', { name: 'Lançar horas' }).first().click();
    const dialog = page.getByRole('dialog', { name: 'Lançar horas' });
    await dialog.getByRole('combobox', { name: 'Buscar tarefa por chave ou título' }).fill(key);
    await dialog.getByRole('option', { name: new RegExp(key) }).click();
    await dialog.getByLabel('Horas').fill('25');
    await dialog.getByRole('button', { name: 'Lançar' }).click();
    await expect(dialog.getByText('Informe horas maiores que 0 e até 24')).toBeVisible();
    await dialog.getByLabel('Horas').fill('2,5');
    // O mouse ficou sobre o botão depois do clique: o hover (bg-primary/90) não é o estado que o axe confere.
    await page.mouse.move(0, 0);
    expect((await axe().include('[role="dialog"]').analyze()).violations).toEqual([]);
    await print(page, '06-lancar-horas');
    await dialog.getByRole('button', { name: 'Lançar' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.getByText('Horas lançadas.')).toBeVisible();

    const table = page.getByRole('table', { name: 'Meus lançamentos de horas' });
    const row = table.getByRole('row').filter({ hasText: key });
    await expect(row.getByText('2,5 h')).toBeVisible();
    await print(page, '07-minhas-horas');

    await row.getByRole('button', { name: `Corrigir 2,5 h de ${await row.getByRole('cell').first().innerText()} em ${key}` }).click();
    const fix = page.getByRole('dialog', { name: 'Corrigir lançamento' });
    await fix.getByLabel('Horas').fill('3');
    await fix.getByRole('button', { name: 'Salvar correção' }).click();
    await expect(fix).toBeHidden();
    await expect(row.getByText('3 h', { exact: true })).toBeVisible();

    await row.getByRole('button', { name: new RegExp(`^Excluir 3 h de .* em ${key}$`) }).click();
    await page.getByRole('dialog', { name: 'Excluir lançamento' }).getByRole('button', { name: 'Excluir' }).click();
    await expect(page.getByText('Lançamento excluído.')).toBeVisible();
    await expect(row).toHaveCount(0);
});
