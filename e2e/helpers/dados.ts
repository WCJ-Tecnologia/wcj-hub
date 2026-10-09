import { spawnSync } from 'node:child_process';

/**
 * Prepara no banco do e2e o que o spec precisa sem passar pela tela, pelo tinker com
 * APP_ENV=e2e: o mesmo banco que o e2e:prepare monta, nunca o de dev.
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
 * Cliente de fora do Brasil (documento opcional: N5). Sem projeto: até o B2, quem conta os
 * projetos abertos é a implementação vazia do ClientManagement, que responde zero. Devolve o id.
 */
export function createClient(name: string): number {
    return Number(
        tinker(`
            $client = App\\Platform\\Models\\Client::query()->create(['name' => ${JSON.stringify(name)}, 'country' => 'US', 'document' => null]);
            echo $client->id;
        `),
    );
}

/** Contato ativo no cliente, sem e-mail nem telefone (N8: os dois são opcionais). Devolve o id. */
export function createContact(clientId: number, name: string): number {
    return Number(
        tinker(`
            $contact = App\\Platform\\Models\\Contact::query()->create(['client_id' => ${clientId}, 'name' => ${JSON.stringify(name)}]);
            echo $contact->id;
        `),
    );
}

/** Apaga o que o spec criou: os contatos do cliente e o próprio cliente. */
export function deleteClient(id: number): void {
    tinker(`
        App\\Platform\\Models\\Contact::query()->where('client_id', ${id})->delete();
        App\\Platform\\Models\\Client::query()->whereKey(${id})->delete();
    `);
}

/** Desativa o cliente fora da tela, como outro administrador faria com o formulário já aberto. */
export function deactivateClient(id: number): void {
    tinker(`App\\Platform\\Models\\Client::query()->whereKey(${id})->update(['deactivated_at' => now()]);`);
}
