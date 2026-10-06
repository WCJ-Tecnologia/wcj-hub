#!/usr/bin/env node
/**
 * Builds the database the Playwright suite runs against.
 *
 *   npm run e2e:prepare
 *
 *   1. copies .env.e2e.example → .env.e2e (if missing) and generates APP_KEY
 *   2. recreates database/e2e.sqlite with migrate:fresh --seed
 *   3. creates the e2e admin with E2E_ADMIN_PASSWORD (generated at random into
 *      .env.e2e when empty: no admin password lives in a versioned file)
 *
 * Then start the server with `npm run e2e:serve` and run `npm run test:e2e`.
 * Never point this at the development database.
 */
import { spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { copyFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const envFile = resolve(root, '.env.e2e');

function artisan(args) {
    const result = spawnSync('php', ['artisan', ...args], {
        cwd: root,
        stdio: 'inherit',
        env: { ...process.env, APP_ENV: 'e2e' },
    });

    if (result.status !== 0) {
        console.error(`php artisan ${args[0]} falhou (exit ${result.status ?? 'signal'})`);
        process.exit(result.status ?? 1);
    }
}

function parseEnv(contents) {
    const vars = {};
    for (const line of contents.split('\n')) {
        const match = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/);
        if (match) vars[match[1]] = match[2].replace(/^"(.*)"$/, '$1');
    }
    return vars;
}

if (!existsSync(envFile)) {
    copyFileSync(resolve(root, '.env.e2e.example'), envFile);
    console.log('.env.e2e criado a partir de .env.e2e.example');
}

if (/^APP_KEY=\s*$/m.test(readFileSync(envFile, 'utf8'))) {
    artisan(['key:generate', '--force', '--no-interaction']);
}

if (/^E2E_ADMIN_PASSWORD=\s*$/m.test(readFileSync(envFile, 'utf8'))) {
    // Letters + digits, so it passes Password::defaults() (min 10, letters, numbers).
    const generated = `E2e${randomBytes(12).toString('hex')}9`;
    writeFileSync(envFile, readFileSync(envFile, 'utf8').replace(/^E2E_ADMIN_PASSWORD=\s*$/m, `E2E_ADMIN_PASSWORD=${generated}`));
    console.log('E2E_ADMIN_PASSWORD gerada em .env.e2e');
}

const env = parseEnv(readFileSync(envFile, 'utf8'));
const { E2E_ADMIN_EMAIL: email, E2E_ADMIN_PASSWORD: password } = env;

if (!email || !password) {
    console.error('E2E_ADMIN_EMAIL e E2E_ADMIN_PASSWORD precisam estar definidos em .env.e2e');
    process.exit(2);
}

const database = resolve(root, env.DB_DATABASE ?? 'database/e2e.sqlite');
mkdirSync(resolve(database, '..'), { recursive: true });
writeFileSync(database, '');

artisan(['migrate:fresh', '--seed', '--force', '--no-interaction']);
artisan(['app:create-admin', email, '--name=E2E Admin', `--password=${password}`, '--no-interaction']);

console.log(`\nBanco e2e pronto em ${database}. Suba o servidor com: npm run e2e:serve`);
