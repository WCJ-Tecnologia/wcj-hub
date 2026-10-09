import { expect, test } from './fixtures';
import { AuditLogsPage } from './pages/audit-logs.page';

/**
 * CR-1-07 (code review do B1, ciclo 1): o "De" já vem preenchido (7 dias), então cada segmento
 * digitado vira uma data válida. Antes, uma visita por tecla, e o campo voltava ao valor antigo até
 * a resposta chegar. Agora o campo guarda o que se digita e a visita sai uma vez, depois da pausa.
 */
test('auditoria: digitar a data no filtro De faz uma visita só e o campo guarda o valor', async ({ page }) => {
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
    const applied = page.waitForResponse((response) => new URL(response.url()).searchParams.get('from') === '2026-01-01');
    await from.focus();
    // Segmento por segmento, como no teclado. Dia e mês iguais: a ordem dos segmentos depende do
    // idioma do navegador.
    await page.keyboard.type('01012026', { delay: 50 });
    await expect(from).toHaveValue('2026-01-01');
    await applied;

    await expect.poll(() => new URL(page.url()).searchParams.get('from')).toBe('2026-01-01');
    await expect(from).toHaveValue('2026-01-01');
    expect(visits).toEqual(['2026-01-01']);
});

/**
 * CR-2-10 (code review do B1, ciclo 2): sair do "De" aplica a data na hora; um select trocado
 * antes dessa visita voltar saía com o período do servidor e cancelava a da data, e a lista
 * ficava filtrada por um período diferente do que o campo mostra. Agora os selects levam o
 * período dos campos. As visitas da lista são seguradas 600 ms para a corrida acontecer sempre.
 */
test('auditoria: trocar um select logo depois de aplicar a data mantém a data nova', async ({ page }) => {
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
        await from.focus();
        await page.keyboard.type('01012026', { delay: 50 });
        await expect(from).toHaveValue('2026-01-01');
        // Sair do campo aplica a data; o select vem logo em seguida, com a visita da data em voo.
        await from.blur();
        await page.getByLabel('Resultado').selectOption({ label: 'Negado' });

        await expect.poll(() => new URL(page.url()).searchParams.get('outcome')).toBe('denied');
        await expect.poll(() => new URL(page.url()).searchParams.get('from')).toBe('2026-01-01');
        await expect(from).toHaveValue('2026-01-01');
    } finally {
        await page.unrouteAll({ behavior: 'ignoreErrors' }).catch(() => undefined);
    }
});
