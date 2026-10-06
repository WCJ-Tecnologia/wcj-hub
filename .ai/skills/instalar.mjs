#!/usr/bin/env node
/**
 * Instala as skills do padrão nas pastas que cada ferramenta de IA lê, ou confere a instalação.
 *
 *   node .ai/skills/instalar.mjs [projeto]           copia (padrão: o projeto onde este arquivo está)
 *   node .ai/skills/instalar.mjs [projeto] --check   só confere; sai com 1 se alguma cópia divergir do modelo
 *
 * Skill do padrão é cada pasta ao lado deste arquivo que tem SKILL.md. Ela vai para .claude/skills (Claude Code),
 * .cursor/skills (Cursor) e .codex/skills (Codex), os mesmos caminhos do instalador do Tech Leads Club.
 * É cópia, não link simbólico: link no git não funciona no Windows sem configuração.
 * Só a pasta de cada skill do padrão é trocada; outra skill que o projeto tenha nessas pastas fica.
 * Pasta de ferramenta que é link para fora do projeto (ex.: .claude/skills -> ~/.claude/skills) é recusada:
 * trocar a skill ali apagaria algo fora do projeto.
 */
import { cpSync, existsSync, mkdirSync, readFileSync, readdirSync, realpathSync, rmSync } from 'node:fs';
import { basename, dirname, isAbsolute, join, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

export const PASTAS_DAS_FERRAMENTAS = ['.claude/skills', '.cursor/skills', '.codex/skills'];

// Lixo local (do Finder e do Python): não é copiado e não conta como diferença.
const IGNORADOS = new Set(['.DS_Store', '__pycache__']);

const aqui = dirname(fileURLToPath(import.meta.url));

export function skillsDoPadrao(origem) {
    return readdirSync(origem, { withFileTypes: true })
        .filter((entrada) => entrada.isDirectory() && existsSync(join(origem, entrada.name, 'SKILL.md')))
        .map((entrada) => entrada.name)
        .sort();
}

function dentroDoProjeto(caminho, projeto) {
    let existente = caminho;
    while (!existsSync(existente)) existente = dirname(existente);
    const caminhoRelativo = relative(realpathSync(projeto), realpathSync(existente));
    return caminhoRelativo === '' || (!caminhoRelativo.startsWith('..') && !isAbsolute(caminhoRelativo));
}

export function instalar(origem, projeto) {
    mkdirSync(projeto, { recursive: true });
    const instaladas = [];
    for (const skill of skillsDoPadrao(origem)) {
        for (const pasta of PASTAS_DAS_FERRAMENTAS) {
            const destino = join(projeto, pasta, skill);
            if (!dentroDoProjeto(dirname(destino), projeto)) {
                throw new Error(`${pasta} aponta para fora do projeto. A skill do padrão é instalada por projeto: troque o link por uma pasta de verdade`);
            }
            rmSync(destino, { recursive: true, force: true });
            mkdirSync(dirname(destino), { recursive: true });
            cpSync(join(origem, skill), destino, { recursive: true, filter: (caminho) => !IGNORADOS.has(basename(caminho)) });
            instaladas.push(`${pasta}/${skill}`);
        }
    }
    return instaladas;
}

function arquivos(pasta) {
    const lista = [];
    const andar = (atual) => {
        for (const entrada of readdirSync(atual, { withFileTypes: true })) {
            if (IGNORADOS.has(entrada.name)) continue;
            const caminho = join(atual, entrada.name);
            if (entrada.isDirectory()) andar(caminho);
            else lista.push(relative(pasta, caminho).split(sep).join('/'));
        }
    };
    andar(pasta);
    return lista.sort();
}

// Clone no Windows com core.autocrlf troca o fim de linha: não é diferença de conteúdo.
const semCR = (conteudo) => conteudo.toString('utf8').replace(/\r\n/g, '\n');
const iguais = (a, b) => a.equals(b) || semCR(a) === semCR(b);

/** O que diverge entre o modelo e as cópias do projeto. Lista vazia: tudo igual. */
export function conferir(origem, projeto) {
    const divergencias = [];
    for (const skill of skillsDoPadrao(origem)) {
        const modelo = arquivos(join(origem, skill));
        for (const pasta of PASTAS_DAS_FERRAMENTAS) {
            const copia = join(projeto, pasta, skill);
            const nome = `${pasta}/${skill}`;
            if (!existsSync(copia)) {
                divergencias.push(`${nome}: não existe`);
                continue;
            }
            const sobra = new Set(arquivos(copia));
            for (const arquivo of modelo) {
                if (!sobra.delete(arquivo)) divergencias.push(`${nome}/${arquivo}: falta`);
                else if (!iguais(readFileSync(join(origem, skill, arquivo)), readFileSync(join(copia, arquivo)))) {
                    divergencias.push(`${nome}/${arquivo}: difere do modelo`);
                }
            }
            for (const arquivo of sobra) divergencias.push(`${nome}/${arquivo}: não existe no modelo`);
        }
    }
    return divergencias;
}

const chamadoDireto = (() => {
    try {
        return realpathSync(process.argv[1] ?? '') === fileURLToPath(import.meta.url);
    } catch {
        return false;
    }
})();

if (chamadoDireto) {
    const args = process.argv.slice(2);
    const desconhecidas = args.filter((arg) => arg.startsWith('-') && arg !== '--check');
    const alvos = args.filter((arg) => !arg.startsWith('-'));
    if (desconhecidas.length || alvos.length > 1) {
        console.error('uso: node .ai/skills/instalar.mjs [projeto] [--check]');
        process.exit(2);
    }
    const projeto = resolve(alvos[0] ?? join(aqui, '..', '..'));
    if (!existsSync(projeto)) {
        console.error(`pasta não existe: ${projeto}`);
        process.exit(2);
    }
    if (args.includes('--check')) {
        const divergencias = conferir(aqui, projeto);
        for (const divergencia of divergencias) console.error(`skills: ${divergencia}. Rode node .ai/skills/instalar.mjs`);
        if (divergencias.length) process.exit(1);
        console.log(`ok: ${skillsDoPadrao(aqui).join(', ')} em ${PASTAS_DAS_FERRAMENTAS.join(', ')}`);
    } else {
        try {
            for (const pasta of instalar(aqui, projeto)) console.log(`${pasta}: instalada`);
        } catch (erro) {
            console.error(`skills: ${erro.message}`);
            process.exit(1);
        }
    }
}
