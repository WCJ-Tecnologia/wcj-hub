import { randomBytes } from 'node:crypto';

import type { Page } from '@playwright/test';

import { expect, test } from './fixtures';
import { createIndicatorsProject, createUser } from './helpers/dados';
import { LoginPage } from './pages/login.page';

/**
 * B7 pela tela (F7 §01 a §04, §09): as seis abas dos indicadores para o gestor e o Administrador,
 * a linha do tempo do card e o que o dev vê (só Minhas tarefas e horas). Com PRINTS=1, guarda os
 * prints do PR em test-results/prints-b7.
 */
const PREFIX = `I${Date.now().toString(36).slice(-5).toUpperCase()}`;
const DEV_NAME = `Dev Ind ${PREFIX}`;

/** A barra de progresso do Inertia (`#nprogress`, role="bar") some uns ms depois da navegação: o axe espera. */
async function settled(page: Page) {
    await page
        .locator('#nprogress')
        .waitFor({ state: 'detached', timeout: 5_000 })
        .catch(() => undefined);
}

async function print(page: Page, name: string) {
    if (process.env.PRINTS) await page.screenshot({ path: `test-results/prints-b7/${name}.png`, fullPage: true });
}

test('@smoke o Administrador lê as seis abas e a linha do tempo do card', async ({ page, axe }) => {
    test.setTimeout(120_000);
    const devId = createUser(DEV_NAME, 3);
    const { projectId } = createIndicatorsProject(PREFIX, devId);
    const base = `/projetos/${projectId}/indicadores`;

    // Situação: a aba padrão, com a atrasada, a próxima data, a pausa e a sem data prevista.
    await page.goto(base);
    const tabs = page.getByRole('tablist', { name: 'Abas dos indicadores' });
    await expect(tabs.getByRole('tab', { name: 'Situação' })).toHaveAttribute('aria-selected', 'true');
    const late = page.getByRole('table', { name: 'Tarefas atrasadas' });
    await expect(late.getByText('Tela de recurso de multa')).toBeVisible();
    await expect(late.getByText(/de atraso|dias úteis|dia útil/).first()).toBeVisible();
    await expect(page.getByRole('table', { name: 'Próximas datas previstas' }).getByText('Filtro por placa')).toBeVisible();
    const paused = page.getByRole('table', { name: 'Tarefas em pausa' });
    await expect(paused.getByText('Esperando o layout')).toBeVisible();
    await expect(
        page
            .getByRole('table', { name: 'Tarefas em desenvolvimento sem data prevista' })
            .getByText('Sem data prevista: falta o dev e faltam as horas'),
    ).toBeVisible();
    await settled(page);
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '01-situacao');

    // Tempo por etapa: por papel, com a pausa e o desenvolvimento.
    await tabs.getByRole('tab', { name: 'Tempo por etapa' }).click();
    await expect(page).toHaveURL(/aba=tempo/);
    const stageTime = page.getByRole('table', { name: 'Tempo por papel de etapa' });
    await expect(stageTime.getByText('Desenvolvimento')).toBeVisible();
    await expect(stageTime.getByText('Pausa')).toBeVisible();
    await expect(page.getByRole('combobox', { name: 'Filtrar por sprint' })).toContainText('Sprint 1');
    await settled(page);
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '02-tempo-por-etapa');

    // Horas: previstas × lançadas e o tempo em desenvolvimento ao lado; totais.
    await tabs.getByRole('tab', { name: 'Horas', exact: true }).click();
    const hours = page.getByRole('table', { name: 'Horas previstas e lançadas por tarefa' });
    await expect(hours.getByText('Tela de recurso de multa')).toBeVisible();
    await expect(hours.getByText('8 h', { exact: true }).first()).toBeVisible();
    await expect(page.getByRole('definition').filter({ hasText: /sem as arquivadas/ })).toBeVisible();
    await settled(page);
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '03-horas');

    // Por pessoa: o dev e a linha Sem dev.
    await tabs.getByRole('tab', { name: 'Por pessoa' }).click();
    const people = page.getByRole('table', { name: 'Indicadores por pessoa' });
    await expect(people.getByText(DEV_NAME)).toBeVisible();
    await expect(people.getByText('Sem dev')).toBeVisible();
    await settled(page);
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '04-por-pessoa');

    // Qualidade: o bug ligado à original.
    await tabs.getByRole('tab', { name: 'Qualidade' }).click();
    const quality = page.getByRole('table', { name: 'Bugs de produção e a tarefa original' });
    await expect(quality.getByText('Boleto sai com valor zerado')).toBeVisible();
    await expect(quality.getByRole('link', { name: `${PREFIX}-4` })).toBeVisible();
    await settled(page);
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '05-qualidade');

    // Minhas tarefas e horas: o Administrador não é dev de nada.
    await tabs.getByRole('tab', { name: 'Minhas tarefas e horas' }).click();
    await expect(page.getByRole('heading', { name: 'Nenhuma tarefa sua neste projeto' })).toBeVisible();
    await print(page, '06-minhas-vazia');

    // O quadro leva aos indicadores; a ficha leva ao histórico.
    await page.goto(`/projetos/${projectId}/quadro`);
    await expect(page.getByRole('link', { name: 'Indicadores' })).toHaveAttribute('href', base);
    await page.goto(`/projetos/${projectId}/cards/${PREFIX}-1`);
    await page.getByRole('link', { name: 'Histórico' }).click();
    await expect(page).toHaveURL(new RegExp(`/cards/${PREFIX}-1/historico$`));

    // A linha do tempo: resumo, retrabalho, pausa e o canal.
    const timeline = page.getByRole('region', { name: 'Linha do tempo' });
    await expect(timeline.getByText(`Criou o card em Backlog`)).toBeVisible();
    await expect(timeline.getByText('Retrabalho')).toBeVisible();
    await expect(timeline.getByText('Faltou o teste do prazo')).toBeVisible();
    await expect(timeline.getByText('Aguardando o token do cliente')).toBeVisible();
    await expect(timeline.getByText('até agora')).toBeVisible();
    const summary = page.getByRole('region', { name: 'Resumo' });
    await expect(summary.getByText(/de atraso, descontada a pausa/)).toBeVisible();
    await expect(summary.getByText('8 h', { exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Ver na auditoria' })).toBeVisible();
    await settled(page);
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '07-historico-do-card');

    // Card pronto: concluída em, sem atraso; e o bug ligado.
    await page.goto(`/projetos/${projectId}/cards/${PREFIX}-4/historico`);
    await expect(page.getByRole('region', { name: 'Resumo' }).getByText('Concluída em')).toBeVisible();
    await expect(page.getByRole('region', { name: 'Resumo' }).getByText('Bugs de produção ligados')).toBeVisible();
    await print(page, '08-historico-card-pronto');

    // Celular.
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(base);
    await expect(page.getByRole('tab', { name: 'Situação' })).toBeVisible();
    await settled(page);
    expect((await axe().analyze()).violations).toEqual([]);
    await print(page, '09-situacao-celular');
});

test.describe('o dev', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('vê só Minhas tarefas e horas e a linha do tempo, sem Ver na auditoria (P54)', async ({ page, axe }) => {
        test.setTimeout(90_000);
        const name = `Dev Minhas ${PREFIX}`;
        // Senha de teste gerada aqui, só para este usuário do banco de e2e.
        const password = `Dev${randomBytes(9).toString('hex')}7`;
        const devId = createUser(name, 3, password);
        const prefix = `${PREFIX}D`;
        const { projectId } = createIndicatorsProject(prefix, devId);

        const login = new LoginPage(page);
        await login.goto();
        await login.login(`${name.toLowerCase().replace(/[^a-z0-9]+/g, '.')}@e2e.test`, password);
        await expect(page).toHaveURL(/\/admin\/dashboard$/);

        await page.goto(`/projetos/${projectId}/indicadores?aba=situacao`);
        const tabs = page.getByRole('tablist', { name: 'Abas dos indicadores' });
        await expect(tabs.getByRole('tab')).toHaveCount(1);
        await expect(tabs.getByRole('tab', { name: 'Minhas tarefas e horas' })).toHaveAttribute('aria-selected', 'true');
        const mine = page.getByRole('table', { name: 'Minhas tarefas abertas' });
        await expect(mine.getByText('Tela de recurso de multa')).toBeVisible();
        await expect(mine.getByText('Filtro por placa')).toBeVisible();
        // A pronta e a sem papel meu não entram.
        await expect(mine.getByText('Emissão de boleto')).toHaveCount(0);
        await expect(mine.getByText('Relatório de medição')).toHaveCount(0);
        await settled(page);
        expect((await axe().analyze()).violations).toEqual([]);
        await print(page, '10-dev-minhas-tarefas');

        // O filtro de prazo: só a atrasada.
        await page.getByRole('combobox', { name: 'Filtrar por prazo' }).click();
        await page.getByRole('option', { name: 'Atrasadas' }).click();
        await expect(page).toHaveURL(/due=late/);
        await expect(mine.getByText('Filtro por placa')).toHaveCount(0);
        await expect(mine.getByText('Tela de recurso de multa')).toBeVisible();

        await page.goto(`/projetos/${projectId}/cards/${prefix}-1/historico`);
        await expect(page.getByRole('region', { name: 'Linha do tempo' }).getByText('Retrabalho')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Ver na auditoria' })).toHaveCount(0);
        await print(page, '11-dev-historico');
    });
});
