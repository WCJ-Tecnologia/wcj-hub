import { expect, test as setup } from '@playwright/test';

import { LoginPage } from './pages/login.page';

const AUTH_FILE = 'e2e/.auth/admin.json';

/** Signs in once and saves the session for every other spec. */
setup('autentica o administrador de e2e', async ({ page }) => {
    const email = process.env.E2E_ADMIN_EMAIL;
    const password = process.env.E2E_ADMIN_PASSWORD;

    if (!email || !password) {
        throw new Error('E2E_ADMIN_EMAIL e E2E_ADMIN_PASSWORD precisam estar em .env.e2e (rode npm run e2e:prepare).');
    }

    const login = new LoginPage(page);
    await login.goto();
    await login.login(email, password);

    await expect(page).toHaveURL(/\/admin\/dashboard$/);
    await page.context().storageState({ path: AUTH_FILE });
});
