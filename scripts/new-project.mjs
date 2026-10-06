#!/usr/bin/env node
/**
 * Prepara um clone do template para virar um projeto novo.
 *
 *   node scripts/new-project.mjs "Nome do Sistema"
 *
 * Roda dentro do clone, depois de trocar o origin (ver .ai/stacks/laravel-inertia.md, seção 9).
 *   - troca o nome em .env.example, package.json e composer.json
 *   - substitui AGENTS.md pelo bloco padrão + modelo do projeto para preencher
 *   - cria docs/00-COMECE-AQUI.md, docs/DEPLOY.md, docs/necessidade.md, docs/banco.txt a partir dos templates
 *   - zera o CHANGELOG.md do template
 * Não commita.
 */
import { copyFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const nome = process.argv[2];
if (!nome) {
    console.error('uso: node scripts/new-project.mjs "Nome do Sistema"');
    process.exit(2);
}
const slug = nome
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');

function replaceIn(file, pairs) {
    const p = join(root, file);
    if (!existsSync(p)) return;
    let s = readFileSync(p, 'utf8');
    for (const [a, b] of pairs) s = s.split(a).join(b);
    writeFileSync(p, s);
}

replaceIn('.env.example', [['APP_NAME=ai-systems', `APP_NAME="${nome}"`], ['APP_BRAND_NAME=ai-systems', `APP_BRAND_NAME="${nome}"`]]);
replaceIn('package.json', [['"name": "ai-systems"', `"name": "${slug}"`]]);
replaceIn('composer.json', [['"wcj/ai-systems"', `"wcj/${slug}"`]]);

const ai = join(root, '.ai');
const bloco = readFileSync(join(ai, 'AGENTS.padrao.md'), 'utf8').trim();
const projeto = readFileSync(join(ai, 'templates', 'AGENTS.projeto.md'), 'utf8').replace('<Nome do sistema>', nome);
writeFileSync(join(root, 'AGENTS.md'), `${bloco}\n\n${projeto}`);
writeFileSync(join(root, 'CLAUDE.md'), '@AGENTS.md\n');

mkdirSync(join(root, 'docs', 'adr'), { recursive: true });
mkdirSync(join(root, 'docs', 'atas'), { recursive: true });
for (const [tpl, dest] of [
    ['00-COMECE-AQUI.md', 'docs/00-COMECE-AQUI.md'],
    ['DEPLOY.md', 'docs/DEPLOY.md'],
    ['necessidade.md', 'docs/necessidade.md'],
    ['banco.txt', 'docs/banco.txt'],
]) {
    const s = readFileSync(join(ai, 'templates', tpl), 'utf8').replace(/<Nome do sistema>/g, nome);
    writeFileSync(join(root, dest), s);
}
copyFileSync(join(ai, 'templates', 'adr.md'), join(root, 'docs', 'adr', '000-modelo.md'));

writeFileSync(join(root, 'CHANGELOG.md'), `# Changelog: ${nome}\n\nPadrão WCJ ${readFileSync(join(ai, 'VERSION'), 'utf8').trim()}.\n`);
writeFileSync(join(root, 'README.md'), `# ${nome}\n\nComece por \`docs/00-COMECE-AQUI.md\`. Regras em \`AGENTS.md\`.\n`);

console.log(`projeto "${nome}" (${slug}) preparado. Próximos passos: preencher AGENTS.md e docs/, depois git add -A && git commit.`);
