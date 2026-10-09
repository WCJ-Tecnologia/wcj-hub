import type { Locator, Page } from '@playwright/test';

import { expect, test } from './fixtures';
import { AuditLogsPage } from './pages/audit-logs.page';

/**
 * O "De" é o date picker (ADR-003): o botão mostra a data em dd/mm/aaaa e o calendário abre no
 * mês dela. O "De" já vem preenchido (7 dias), então o dia 1 do mês mostrado sempre existe.
 */
async function shownMonth(trigger: Locator) {
    const shown = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec((await trigger.textContent())?.trim() ?? '');
    if (!shown) throw new Error('o filtro De deveria vir preenchido com os últimos 7 dias');
    const [, , month, year] = shown;

    return { iso: `${year}-${month}-01`, display: `01/${month}/${year}` };
}

/** Abre o calendário e escolhe o dia 1 do mês mostrado (o primeiro "1 de …" é o do mês, não o do seguinte). */
async function pickFirstDay(page: Page, trigger: Locator) {
    await trigger.click();
    await page.getByRole('button', { name: /, 1 de / }).first().click();
}

/**
 * CR-1-07 (code review do B1, ciclo 1): escolher a data dispara uma visita só e o campo guarda o
 * valor escolhido enquanto a resposta não chega (antes, com o campo nativo, uma visita por tecla
 * devolvia o valor antigo).
 */
test('auditoria: escolher a data no filtro De faz uma visita só e o campo guarda o valor', async ({ page }) => {
    const audit = new AuditLogsPage(page);
    await audit.goto();

    const visits: (string | null)[] = [];
    page.on('request', (request) => {
        const url = new URL(request.url());
        if (request.method() === 'GET' && url.pathname === '/admin/audit-logs' && request.headers()['x-inertia']) {
            visits.push(url.searchParams.get('from'));
        }
    });

    const from = page.getByLabel('De', { exact: true });
    const picked = await shownMonth(from);
    const applied = page.waitForResponse((response) => new URL(response.url()).searchParams.get('from') === picked.iso);
    await pickFirstDay(page, from);
    await expect(from).toHaveText(picked.display);
    await applied;

    await expect.poll(() => new URL(page.url()).searchParams.get('from')).toBe(picked.iso);
    await expect(from).toHaveText(picked.display);
    expect(visits).toEqual([picked.iso]);
});

/**
 * CR-2-10 (code review do B1, ciclo 2): um select trocado antes da visita da data voltar saía com
 * o período do servidor e cancelava a da data, e a lista ficava filtrada por um período diferente
 * do que o campo mostra. Os selects levam o período dos campos. As visitas da lista são seguradas
 * 600 ms para a corrida acontecer sempre.
 */
test('auditoria: trocar um select logo depois de escolher a data mantém a data nova', async ({ page }) => {
    const audit = new AuditLogsPage(page);
    await audit.goto();

    await page.route(
        (url) => url.pathname === '/admin/audit-logs',
        async (route) => {
            if (route.request().headers()['x-inertia']) await new Promise((resolve) => setTimeout(resolve, 600));
            await route.continue().catch(() => undefined);
        },
    );

    try {
        const from = page.getByLabel('De', { exact: true });
        const picked = await shownMonth(from);
        await pickFirstDay(page, from);
        await expect(from).toHaveText(picked.display);
        // O select vem logo em seguida, com a visita da data em voo (Select do Radix: gatilho + opção).
        await page.getByLabel('Resultado').click();
        await page.getByRole('option', { name: 'Negado' }).click();

        await expect.poll(() => new URL(page.url()).searchParams.get('outcome')).toBe('denied');
        await expect.poll(() => new URL(page.url()).searchParams.get('from')).toBe(picked.iso);
        await expect(from).toHaveText(picked.display);
    } finally {
        await page.unrouteAll({ behavior: 'ignoreErrors' }).catch(() => undefined);
    }
});
