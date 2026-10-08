#!/usr/bin/env node
/**
 * Roda o CLI do shadcn e deixa o resultado no jeito do Hub (ADR-003).
 *
 *   npm run shadcn -- add accordion
 *
 * O components.json já manda o arquivo para resources/js/components/shadcn/.
 * Depois do CLI, este script:
 *   1. troca `from "cn"` (o registro new-york-v4 passou a importar o pacote
 *      `cn`) pelo `@/lib/utils`, que é o cn único do projeto;
 *   2. desinstala o pacote `cn` se o CLI o adicionou;
 *   3. formata os arquivos tocados com o prettier.
 *
 * Responda "não" quando o CLI perguntar se pode sobrescrever um primitivo que
 * já existe: dialog, sheet, badge, tooltip e outros são customizados. E não
 * aceite a mudança dele em resources/css/app.css (gerado por `npm run theme`).
 */
import { spawnSync } from 'node:child_process';
import { readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const dirs = ['resources/js/components/shadcn', 'resources/js/hooks'].map((dir) => resolve(root, dir));

function run(command, args) {
    const result = spawnSync(command, args, { cwd: root, stdio: 'inherit' });
    if (result.status !== 0) process.exit(result.status ?? 1);
}

run('npx', ['shadcn@latest', ...process.argv.slice(2)]);

const touched = [];
for (const dir of dirs) {
    for (const name of readdirSync(dir)) {
        if (!/\.(ts|tsx)$/.test(name)) continue;
        const file = join(dir, name);
        const source = readFileSync(file, 'utf8');
        const fixed = source.replace(/from (["'])cn\1/g, "from '@/lib/utils'");
        if (fixed !== source) {
            writeFileSync(file, fixed);
            touched.push(file);
        }
    }
}

const pkg = JSON.parse(readFileSync(resolve(root, 'package.json'), 'utf8'));
if (pkg.dependencies?.cn || pkg.devDependencies?.cn) run('npm', ['uninstall', 'cn']);

run('npx', ['prettier', '--write', ...dirs]);

console.log(touched.length ? `\ncn → @/lib/utils em ${touched.length} arquivo(s).` : '\nNenhum import de "cn" para trocar.');
console.log('Confira o git diff: primitivo customizado sobrescrito ou mudança em resources/css/app.css se desfaz.');
