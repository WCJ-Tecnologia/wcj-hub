import { expect, type Locator, type Page } from '@playwright/test';

export class LoginPage {
    readonly path = '/admin/login';
    readonly email: Locator;
    readonly password: Locator;
    readonly submit: Locator;
    readonly error: Locator;

    constructor(private readonly page: Page) {
        this.email = page.getByLabel('E-mail');
        this.password = page.getByLabel('Senha', { exact: true });
        this.submit = page.getByRole('button', { name: 'Entrar' });
        this.error = page.getByRole('alert');
    }

    async goto() {
        await this.page.goto(this.path);
    }

    async login(email: string, password: string) {
        // The form clears the password in onFinish, after the previous error is already on screen.
        // Filling before that finishes loses the password and submits it empty.
        await expect(this.submit).toBeEnabled();
        await this.email.fill(email);
        await this.password.fill(password);
        await expect(this.password).toHaveValue(password);
        await this.submit.click();
    }
}
