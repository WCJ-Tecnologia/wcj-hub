import { createClient, createContact, deleteClient } from './helpers/dados';
import { expect, test } from './fixtures';
import { AdminPage } from './pages/admin.page';

/**
 * CR-1-10 (code review do B1, ciclo 1): a busca navega 300 ms depois da digitação, e visita
 * síncrona interrompe visita síncrona no Inertia 2. Um Reativar (sem confirmação) clicado antes do
 * debounce era cancelado pela busca, sem toast nem erro. Agora a busca espera a escrita terminar.
 * O POST é segurado 600 ms para a corrida acontecer sempre, como num servidor lento.
 */
test('Reativar clicado logo depois de digitar na busca não se perde, e a busca também não', async ({ page }) => {
    const stamp = Date.now();
    const contact = `E2E Contato busca ${stamp}`;
    const clientId = createClient(`E2E Cliente busca ${stamp}`);
    createContact(clientId, contact);
    const admin = new AdminPage(page);
    const search = page.getByRole('searchbox', { name: 'Buscar contatos' });

    try {
        await admin.goto(`/admin/contacts?show_inactive=1&search=${stamp}`);
        await expect(admin.heading).toHaveText('Contatos');
        await page.getByRole('button', { name: `Desativar ${contact}` }).click();
        await admin.expectToast(`Contato ${contact} desativado.`);

        await page.route(/\/admin\/contacts\/\d+\/reactivate$/, async (route) => {
            await new Promise((resolve) => setTimeout(resolve, 600));
            await route.continue().catch(() => undefined);
        });

        // Sem esperar o debounce: digitar e clicar na sequência, bem abaixo dos 300 ms.
        await search.fill(contact);
        await page.getByRole('button', { name: `Reativar ${contact}` }).click();

        // Antes do CR-1-10, a busca cancelava o POST: nem toast, nem linha reativada.
        await expect(admin.toast.getByText(`Contato ${contact} reativado.`)).toBeVisible();
        await expect(page.getByRole('button', { name: `Desativar ${contact}` })).toBeVisible();
        await expect.poll(() => new URL(page.url()).searchParams.get('search')).toBe(contact);
        await expect(search).toHaveValue(contact);
    } finally {
        deleteClient(clientId);
        await page.unrouteAll({ behavior: 'ignoreErrors' }).catch(() => undefined);
    }
});
