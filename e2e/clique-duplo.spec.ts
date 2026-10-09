import { createClient, createContact, deactivateContact, deleteClient } from './helpers/dados';
import { expect, test } from './fixtures';
import { AdminPage } from './pages/admin.page';

/**
 * CR-2-03 e CR-2-07 (code review do B1, ciclo 2): a trava de clique duplo das ações de linha é a
 * do useRowAction. Dois cliques em Reativar no mesmo quadro saem como um POST só, e enquanto ele
 * roda a outra linha aparece desabilitada, em vez de descartar o clique em silêncio. O POST é
 * segurado 600 ms para a janela existir sempre, como num servidor lento.
 */
test('clique duplo em Reativar sai uma vez só, e a outra linha fica travada enquanto ele roda', async ({ page }) => {
    const stamp = Date.now();
    const first = `E2E Contato duplo A ${stamp}`;
    const second = `E2E Contato duplo B ${stamp}`;
    const clientId = createClient(`E2E Cliente duplo ${stamp}`);
    const firstId = createContact(clientId, first);
    deactivateContact(firstId);
    deactivateContact(createContact(clientId, second));
    const admin = new AdminPage(page);

    const posts: string[] = [];
    page.on('request', (request) => {
        if (request.method() === 'POST' && /\/admin\/contacts\/\d+\/reactivate$/.test(new URL(request.url()).pathname)) {
            posts.push(request.url());
        }
    });

    try {
        await admin.goto(`/admin/contacts?show_inactive=1&search=${stamp}`);
        await expect(admin.heading).toHaveText('Contatos');

        await page.route(/\/admin\/contacts\/\d+\/reactivate$/, async (route) => {
            await new Promise((resolve) => setTimeout(resolve, 600));
            await route.continue().catch(() => undefined);
        });

        // Os dois cliques no mesmo quadro, antes de o React redesenhar o botão desabilitado.
        await page.getByRole('button', { name: `Reativar ${first}` }).evaluate((button: HTMLButtonElement) => {
            button.click();
            button.click();
        });

        const other = page.getByRole('button', { name: `Reativar ${second}` });
        await expect(other).toBeDisabled();
        await expect(admin.toast.getByText(`Contato ${first} reativado.`)).toHaveCount(1);
        await expect(page.getByRole('button', { name: `Desativar ${first}` })).toBeVisible();
        await expect(other).toBeEnabled();
        expect(posts).toHaveLength(1);

        await admin.goto(`/admin/audit-logs?type=contact&id=${firstId}`);
        await expect(admin.heading).toHaveText('Auditoria');
        await expect(page.getByRole('button', { name: `Ver detalhes: Reativado, Contato ${first},` })).toHaveCount(1);
    } finally {
        deleteClient(clientId);
        await page.unrouteAll({ behavior: 'ignoreErrors' }).catch(() => undefined);
    }
});
