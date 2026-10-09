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
