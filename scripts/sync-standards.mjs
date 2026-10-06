#!/usr/bin/env node
/**
 * Leva o padrão WCJ (.ai/) deste repositório para outro projeto.
 *
 *   node scripts/sync-standards.mjs <caminho-do-projeto>          copia e carimba a versão
 *   node scripts/sync-standards.mjs <caminho-do-projeto> --check  só compara (sai com 1 se estiver atrás
 *                                                                 ou se a cópia de uma skill divergir)
 *
 * O que faz no projeto de destino:
 *   - substitui .ai/core, .ai/playbooks, .ai/stacks, .ai/templates, .ai/adapters, .ai/skills e .ai/AGENTS.padrao.md
 *   - instala as skills de .ai/skills/ em .claude/skills, .cursor/skills e .codex/skills, trocando só a pasta
 *     de cada skill do padrão (outra skill do projeto fica)
 *   - grava .ai/VERSION
 *   - cria ou atualiza no AGENTS.md só o bloco entre <!-- wcj-ai:inicio --> e <!-- wcj-ai:fim -->
 *   - cria CLAUDE.md apontando para AGENTS.md, se não existir
 *   - liga as travas de agente: acrescenta o hook em .claude/settings.json e .cursor/hooks.json
 *     (sem apagar o que o projeto já tem), grava .codex/rules/wcj.rules, e cria .codex/config.toml
 *     e .agent-guard.json se não existirem
 * Não toca em mais nada. Sem dependências.
 */
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { conferir as conferirSkills, instalar as instalarSkills } from '../.ai/skills/instalar.mjs';

const here = dirname(fileURLToPath(import.meta.url));
const source = resolve(here, '..', '.ai');
const [targetArg, ...flags] = process.argv.slice(2);
const check = flags.includes('--check');

if (!targetArg) {
    console.error('uso: node scripts/sync-standards.mjs <caminho-do-projeto> [--check]');
    process.exit(2);
}

const target = resolve(targetArg);
const targetAi = join(target, '.ai');
const version = readFileSync(join(source, 'VERSION'), 'utf8').trim();
const INICIO = '<!-- wcj-ai:inicio';
const FIM = '<!-- wcj-ai:fim -->';

if (!existsSync(target)) {
    console.error(`pasta não existe: ${target}`);
    process.exit(2);
}

const CLAUDE_HOOK = '.ai/adapters/claude-code/hook.mjs';
const CURSOR_HOOK = '.ai/adapters/cursor/hook.mjs';
const readJson = (path) => JSON.parse(readFileSync(path, 'utf8'));
const writeJson = (path, data) => {
    mkdirSync(dirname(path), { recursive: true });
    writeFileSync(path, `${JSON.stringify(data, null, 4)}\n`);
};

function missingWiring() {
    const missing = [];
    const has = (file, text) => existsSync(join(target, file)) && readFileSync(join(target, file), 'utf8').includes(text);
    if (!has('.claude/settings.json', CLAUDE_HOOK)) missing.push('.claude/settings.json sem o hook');
    if (!has('.cursor/hooks.json', CURSOR_HOOK)) missing.push('.cursor/hooks.json sem o hook');
    const rules = join(target, '.codex', 'rules', 'wcj.rules');
    if (!existsSync(rules) || readFileSync(rules, 'utf8') !== readFileSync(join(source, 'adapters', 'codex', 'rules', 'wcj.rules'), 'utf8')) {
        missing.push('.codex/rules/wcj.rules ausente ou diferente do padrão');
    }
    return missing;
}

if (check) {
    const current = existsSync(join(targetAi, 'VERSION')) ? readFileSync(join(targetAi, 'VERSION'), 'utf8').trim() : null;
    const missing = missingWiring();
    const skills = conferirSkills(join(source, 'skills'), target);
    if (current === version && !missing.length && !skills.length) {
        console.log(`ok: padrão ${version}, travas de agente ligadas, skills instaladas`);
        process.exit(0);
    }
    if (current !== version) console.error(`desatualizado: projeto tem ${current ?? 'nenhuma versão'}, padrão é ${version}. Rode o sync.`);
    for (const m of missing) console.error(`travas de agente: ${m}. Rode o sync.`);
    for (const s of skills) console.error(`skills: ${s}. Rode o sync.`);
    process.exit(1);
}

if (resolve(target) === resolve(here, '..')) {
    console.error('origem e destino são o mesmo repositório: o sync apagaria o próprio .ai/. Rode contra outro projeto.');
    process.exit(2);
}

for (const dir of ['core', 'playbooks', 'stacks', 'templates', 'adapters', 'skills']) {
    rmSync(join(targetAi, dir), { recursive: true, force: true });
    mkdirSync(join(targetAi, dir), { recursive: true });
    cpSync(join(source, dir), join(targetAi, dir), { recursive: true });
}
cpSync(join(source, 'AGENTS.padrao.md'), join(targetAi, 'AGENTS.padrao.md'));
writeFileSync(join(targetAi, 'VERSION'), `${version}\n`);
console.log(`skills: ${instalarSkills(join(targetAi, 'skills'), target).join(', ')}`);

const bloco = readFileSync(join(source, 'AGENTS.padrao.md'), 'utf8').trim();
const agentsPath = join(target, 'AGENTS.md');

if (existsSync(agentsPath)) {
    const atual = readFileSync(agentsPath, 'utf8');
    const i = atual.indexOf(INICIO);
    const f = atual.indexOf(FIM);
    if (i !== -1 && f !== -1) {
        writeFileSync(agentsPath, atual.slice(0, i) + bloco + atual.slice(f + FIM.length));
        console.log('AGENTS.md: bloco padrão atualizado');
    } else {
        writeFileSync(agentsPath, `${bloco}\n\n${atual}`);
        console.log('AGENTS.md: bloco padrão inserido no topo (o conteúdo existente foi mantido abaixo)');
    }
} else {
    const projeto = readFileSync(join(source, 'templates', 'AGENTS.projeto.md'), 'utf8');
    writeFileSync(agentsPath, `${bloco}\n\n${projeto}`);
    console.log('AGENTS.md: criado com o bloco padrão + modelo do projeto para preencher');
}

const claudePath = join(target, 'CLAUDE.md');
if (!existsSync(claudePath)) {
    writeFileSync(claudePath, '@AGENTS.md\n');
    console.log('CLAUDE.md: criado');
}

// Travas de agente. O arquivo de configuração é do projeto: acrescenta o hook, nunca apaga o que já existe.
const claudeSettingsPath = join(target, '.claude', 'settings.json');
const claudeModel = readJson(join(source, 'adapters', 'claude-code', 'settings.json'));
if (!existsSync(claudeSettingsPath)) {
    writeJson(claudeSettingsPath, claudeModel);
    console.log('.claude/settings.json: criado com o hook das travas');
} else if (!readFileSync(claudeSettingsPath, 'utf8').includes(CLAUDE_HOOK)) {
    const settings = readJson(claudeSettingsPath);
    settings.hooks ??= {};
    settings.hooks.PreToolUse = [...(settings.hooks.PreToolUse ?? []), ...claudeModel.hooks.PreToolUse];
    writeJson(claudeSettingsPath, settings);
    console.log('.claude/settings.json: hook das travas acrescentado');
}

const cursorPath = join(target, '.cursor', 'hooks.json');
const cursorModel = readJson(join(source, 'adapters', 'cursor', 'hooks.json'));
if (!existsSync(cursorPath)) {
    writeJson(cursorPath, cursorModel);
    console.log('.cursor/hooks.json: criado com o hook das travas');
} else if (!readFileSync(cursorPath, 'utf8').includes(CURSOR_HOOK)) {
    const hooks = readJson(cursorPath);
    hooks.version ??= 1;
    hooks.hooks ??= {};
    for (const [event, entries] of Object.entries(cursorModel.hooks)) hooks.hooks[event] = [...(hooks.hooks[event] ?? []), ...entries];
    writeJson(cursorPath, hooks);
    console.log('.cursor/hooks.json: hook das travas acrescentado');
}

mkdirSync(join(target, '.codex', 'rules'), { recursive: true });
cpSync(join(source, 'adapters', 'codex', 'rules', 'wcj.rules'), join(target, '.codex', 'rules', 'wcj.rules'));
const codexConfig = join(target, '.codex', 'config.toml');
if (!existsSync(codexConfig)) {
    cpSync(join(source, 'adapters', 'codex', 'config.toml'), codexConfig);
    console.log('.codex/config.toml: criado');
} else if (!/^\s*approval_policy\s*=\s*"untrusted"/m.test(readFileSync(codexConfig, 'utf8'))) {
    console.warn('.codex/config.toml: já existe e não tem approval_policy = "untrusted". Confira à mão (.ai/adapters/README.md).');
}

const guardConfig = join(target, '.agent-guard.json');
if (!existsSync(guardConfig)) {
    writeJson(guardConfig, { hostsProducao: [] });
    console.log('.agent-guard.json: criado; liste os hosts de produção do projeto nele');
}

console.log(`padrão ${version} aplicado em ${target}`);
