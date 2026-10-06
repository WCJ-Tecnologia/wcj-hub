import { spawnSync } from 'node:child_process';

/**
 * Prepara no banco do e2e o que a tela ainda não cria (projeto só ganha tela no B2), pelo
 * tinker com APP_ENV=e2e: o mesmo banco que o e2e:prepare monta, nunca o de dev.
 */
function tinker(code: string): string {
    const result = spawnSync('php', ['artisan', 'tinker', '--execute', code], {
        env: { ...process.env, APP_ENV: 'e2e' },
        encoding: 'utf8',
    });

    if (result.status !== 0) throw new Error(`tinker falhou (exit ${result.status}): ${result.stderr || result.stdout}`);

    return result.stdout.trim();
}

/**
 * Cliente de fora do Brasil (documento opcional: N5), com `openProjects` projetos abertos geridos
 * pelo administrador do e2e. Devolve o id do cliente.
 */
export function createClient(name: string, openProjects = 0): number {
    const prefix = `E${Date.now().toString(36).slice(-5).toUpperCase()}`;

    return Number(
        tinker(`
            $client = App\\Platform\\Models\\Client::query()->create(['name' => ${JSON.stringify(name)}, 'country' => 'US', 'document' => null]);
            $manager = App\\Platform\\Models\\User::query()->where('email', ${JSON.stringify(process.env.E2E_ADMIN_EMAIL ?? '')})->sole();
            for ($i = 1; $i <= ${openProjects}; $i++) {
                App\\Domains\\Projetos\\Models\\Project::query()->create([
                    'client_id' => $client->id, 'manager_id' => $manager->id, 'key_prefix' => '${prefix}'.$i,
                    'name' => 'E2E Projeto '.$i, 'starts_on' => now('America/Sao_Paulo')->toDateString(),
                ]);
            }
            echo $client->id;
        `),
    );
}

/** Apaga o que o spec criou: os projetos e os contatos do cliente e o próprio cliente. */
export function deleteClient(id: number): void {
    tinker(`
        App\\Domains\\Projetos\\Models\\Project::query()->where('client_id', ${id})->delete();
        App\\Platform\\Models\\Contact::query()->where('client_id', ${id})->delete();
        App\\Platform\\Models\\Client::query()->whereKey(${id})->delete();
    `);
}
