import { createClient, deleteClient } from './helpers/dados';
import { expect, test } from './fixtures';
import { AuditLogsPage } from './pages/audit-logs.page';
import { ClientsPage } from './pages/clients.page';
import { UsersPage } from './pages/users.page';

/**
 * Os textos e estados de tela que a spec do B1 define e que o front monta (B1 T28): rótulo do
 * documento (Clientes AC 4), confirmação de desativar (AC 9: com a implementação vazia do
 * ClientManagement, a variante sem projetos; a contagem N fica no ClientControllerTest), estado vazio
 * (AC 13), situação Anonimizado (Contatos AC 12), desativado em cinza (Usuários AC 13) e, na
 * auditoria, a linha negada em destaque e a lista sem resultado (Auditoria AC 3, 10). Cada teste
 * apaga o que criou.
 */

test('o documento do cliente se chama CNPJ no Brasil e Identificador fiscal fora dele', async ({ page }) => {
    const clients = new ClientsPage(page);
    const dialog = clients.admin.dialog;
    await clients.goto();
    await clients.openCreate();

    await expect(dialog.getByLabel('CNPJ')).toBeVisible();
    await expect(dialog.getByLabel('Identificador fiscal')).toHaveCount(0);

    await dialog.getByLabel('País').selectOption('US');
    await expect(dialog.getByLabel('Identificador fiscal')).toBeVisible();
    await expect(dialog.getByLabel('CNPJ')).toHaveCount(0);

    await dialog.getByLabel('País').selectOption('BR');
    await expect(dialog.getByLabel('CNPJ')).toBeVisible();
    await clients.cancelForm();
});

test('desativar cliente pede a confirmação; sem projetos abertos, a variante sem contagem', async ({ page }) => {
    const name = `E2E Cliente a desativar ${Date.now()}`;
    const id = createClient(name);
    const clients = new ClientsPage(page);

    try {
        await clients.goto();
        await clients.find(name);
        await page.getByRole('button', { name: `Desativar ${name}` }).click();

        await expect(clients.admin.dialog).toBeVisible();
        await expect(
            clients.admin.dialog.getByText(`O cliente ${name} sai das listas de escolha. Desativar?`, { exact: true }),
        ).toBeVisible();
        await clients.admin.dialog.getByRole('button', { name: 'Cancelar' }).click();
        await expect(clients.admin.dialog).toBeHidden();
    } finally {
        deleteClient(id);
    }
});

test('desativar cliente com projetos abertos mostra o texto da spec com a contagem', async ({ page }) => {
    const name = `E2E Cliente com projetos ${Date.now()}`;
    const id = createClient(name);
    const clients = new ClientsPage(page);

    // Com a implementação vazia do ClientManagement, o servidor sempre manda 0 projetos abertos
    // até o B2. A resposta da busca é reescrita com 2, para provar o texto do Clientes AC 9.
    await page.route(
        (url) => url.pathname === '/admin/clients' && url.searchParams.get('search') === name,
        async (route) => {
            const response = await route.fetch();
            const body = await response.json();
            body.props.clients.data = body.props.clients.data.map((row: { name: string }) =>
                row.name === name ? { ...row, open_projects: 2 } : row,
            );
            await route.fulfill({ response, json: body });
        },
    );

    try {
        await clients.goto();
        // O cliente já pode aparecer na primeira página; só a resposta da busca traz a contagem.
        const busca = page.waitForResponse((r) => new URL(r.url()).searchParams.get('search') === name);
        await clients.find(name);
        await busca;
        await page.getByRole('button', { name: `Desativar ${name}` }).click();

        await expect(
            clients.admin.dialog.getByText(`O cliente ${name} tem 2 projetos abertos; eles continuam. Desativar?`, {
                exact: true,
            }),
        ).toBeVisible();
        await clients.admin.dialog.getByRole('button', { name: 'Cancelar' }).click();
    } finally {
        deleteClient(id);
    }
});

test('lista de clientes vazia mostra "Nenhum cliente ainda" com o botão Novo cliente', async ({ page }) => {
    const clients = new ClientsPage(page);
    await clients.goto();

    // A WCJ existe em toda instalação (N7): a lista sem filtro de busca ou país nunca volta vazia
    // do servidor. A resposta da visita é esvaziada para o teste ver o estado vazio da tela.
    await page.route(
        (url) => url.pathname === '/admin/clients' && url.searchParams.get('show_inactive') === '1',
        async (route) => {
            const response = await route.fetch();
            const body = await response.json();
            body.props.clients = { ...body.props.clients, data: [], total: 0, from: null, to: null };
            await route.fulfill({ response, json: body });
        },
    );
    await page.getByLabel('Mostrar desativados').click();

    const empty = clients.emptyState('Nenhum cliente ainda');
    await expect(empty).toBeVisible();
    await expect(empty.getByRole('button', { name: 'Novo cliente' })).toBeVisible();
});

test('contato anonimizado aparece como Anonimizado, sem editar, reativar nem anonimizar', async ({ page }) => {
    const client = `E2E Cliente LGPD ${Date.now()}`;
    const contact = `E2E Contato LGPD ${Date.now()}`;
    const removed = 'removido a pedido do titular';
    const id = createClient(client);
    const clients = new ClientsPage(page);

    try {
        await clients.openSheet(client);
        await clients.createContact(contact);
        await clients.anonymizeContact(contact);

        const row = page.getByRole('row').filter({ hasText: removed });
        await expect(row).toHaveCount(1);
        await expect(row.getByText('Anonimizado', { exact: true })).toBeVisible();
        for (const action of ['Editar', 'Reativar', 'Anonimizar']) {
            await expect(row.getByRole('button', { name: `${action} ${removed}` })).toHaveCount(0);
        }
    } finally {
        deleteClient(id);
    }
});

test('usuário desativado some da lista e volta em cinza com Mostrar desativados', async ({ page }) => {
    const stamp = Date.now();
    const name = `E2E Dev ${stamp}`;
    const email = `e2e-dev-${stamp}@example.com`;
    const users = new UsersPage(page);

    await users.goto();
    await users.invite(name, email, '8');
    await expect(users.row(email).getByText(name, { exact: true })).not.toHaveClass(/\btext-muted-foreground\b/);
    await users.deactivate(name);
    await expect(users.row(email)).toHaveCount(0);

    await users.showInactive.click();
    const row = users.row(email);
    await expect(row.getByText(name, { exact: true })).toHaveClass(/\btext-muted-foreground\b/);
    await expect(row.getByText('Desativado', { exact: true })).toBeVisible();

    await users.deleteRow(name);
});

test('auditoria: a linha negada fica em destaque, com ícone e Negado; filtro sem resultado avisa', async ({ page }) => {
    const audit = new AuditLogsPage(page);
    const clients = new ClientsPage(page);

    // Uma negação de verdade (N7): o administrador tenta desativar a WCJ fora da tela.
    await clients.goto();
    const href = await page.getByRole('link', { name: 'WCJ Tecnologia', exact: true }).first().getAttribute('href');
    const xsrf = (await page.context().cookies()).find((cookie) => cookie.name === 'XSRF-TOKEN');
    const denied = await page.request.post(`${href}/deactivate`, {
        headers: { 'X-XSRF-TOKEN': decodeURIComponent(xsrf?.value ?? '') },
    });
    expect(denied.status()).toBe(403);

    await audit.goto();
    const deniedRow = audit.rowsWithOutcome('Negado').filter({ hasText: 'Cliente WCJ Tecnologia' }).first();
    await expect(deniedRow).toHaveClass(/\bbg-status-rejected-bg\b/);
    await expect(deniedRow.getByText('Negado', { exact: true }).locator('svg')).toHaveCount(1);
    await expect(audit.rowsWithOutcome('Ok').first()).not.toHaveClass(/\bbg-status-rejected-bg\b/);

    await audit.goto(`?outcome=denied&search=${encodeURIComponent(`nada-${Date.now()}`)}`);
    await expect(page.getByRole('heading', { name: 'Nenhum registro com esses filtros', exact: true })).toBeVisible();
    await expect(page.getByRole('table')).toHaveCount(0);
});
