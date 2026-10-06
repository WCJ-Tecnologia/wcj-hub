import { defineConfig, devices } from '@playwright/test';

// The suite reads its settings from .env.e2e (created by `npm run e2e:prepare`).
try {
    process.loadEnvFile('.env.e2e');
} catch {
    /* CI or a fresh clone: the defaults below apply */
}

export default defineConfig({
    testDir: './e2e',
    // One shared SQLite database: parallel workers would create flaky tests.
    workers: 1,
    fullyParallel: false,
    retries: 0,
    timeout: 30_000,
    expect: { timeout: 7_000 },
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : [['list']],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000',
        locale: 'pt-BR',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'setup',
            testMatch: /auth\.setup\.ts/,
        },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'], storageState: 'e2e/.auth/admin.json' },
            dependencies: ['setup'],
            testIgnore: /auth\.setup\.ts/,
        },
    ],
});
