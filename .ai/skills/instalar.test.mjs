import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { after, describe, test } from 'node:test';
import { fileURLToPath } from 'node:url';
import { PASTAS_DAS_FERRAMENTAS, conferir, instalar, skillsDoPadrao } from './instalar.mjs';
import { motivoParaPularSymlink } from './suporte-symlink.mjs';

const pularSymlink = motivoParaPularSymlink();

const aqui = dirname(fileURLToPath(import.meta.url));
const raizDoProjeto = join(aqui, '..', '..');
const tmp = mkdtempSync(join(tmpdir(), 'wcj-skills-'));
after(() => rmSync(tmp, { recursive: true, force: true }));

let contador = 0;

function escrever(caminho, conteudo) {
    mkdirSync(dirname(caminho), { recursive: true });
    writeFileSync(caminho, conteudo);
}

function cenario() {
    const base = join(tmp, `cenario-${++contador}`);
    const origem = join(base, 'origem');
    escrever(join(origem, 'demo', 'SKILL.md'), '---\nname: demo\n---\n');
    escrever(join(origem, 'demo', 'scripts', 'a.py'), 'print("a")\n');
    escrever(join(origem, 'README.md'), 'não é skill\n');
    escrever(join(origem, 'sem-skill', 'nota.md'), 'pasta sem SKILL.md não é skill\n');
    return { origem, projeto: join(base, 'projeto') };
}

describe('a skill do padrão', () => {
    test('tlc-spec-driven está no modelo, com o LICENSE da origem', () => {
        assert.ok(skillsDoPadrao(aqui).includes('tlc-spec-driven'));
        assert.ok(existsSync(join(aqui, 'tlc-spec-driven', 'LICENSE')));
    });

    test('a cópia ligada no projeto bate com o modelo (se falhar: node .ai/skills/instalar.mjs)', () => {
        assert.deepEqual(conferir(aqui, raizDoProjeto), []);
    });
});

describe('instalar e conferir', () => {
    test('só pasta com SKILL.md é skill', () => {
        const { origem } = cenario();
        assert.deepEqual(skillsDoPadrao(origem), ['demo']);
    });

    test('instala nas três pastas e a conferência fica limpa', () => {
        const { origem, projeto } = cenario();
        assert.deepEqual(conferir(origem, projeto), PASTAS_DAS_FERRAMENTAS.map((pasta) => `${pasta}/demo: não existe`));
        assert.deepEqual(instalar(origem, projeto), PASTAS_DAS_FERRAMENTAS.map((pasta) => `${pasta}/demo`));
        for (const pasta of PASTAS_DAS_FERRAMENTAS) {
            assert.equal(readFileSync(join(projeto, pasta, 'demo', 'scripts', 'a.py'), 'utf8'), 'print("a")\n');
        }
        assert.deepEqual(conferir(origem, projeto), []);
    });

    test('acusa arquivo alterado, a mais e faltando', () => {
        const { origem, projeto } = cenario();
        instalar(origem, projeto);
        writeFileSync(join(projeto, '.claude/skills/demo/SKILL.md'), 'editado à mão\n');
        escrever(join(projeto, '.cursor/skills/demo/extra.md'), 'sobra\n');
        rmSync(join(projeto, '.codex/skills/demo/scripts/a.py'));
        assert.deepEqual(conferir(origem, projeto), [
            '.claude/skills/demo/SKILL.md: difere do modelo',
            '.cursor/skills/demo/extra.md: não existe no modelo',
            '.codex/skills/demo/scripts/a.py: falta',
        ]);
    });

    test('reinstalar corrige a cópia e não mexe em outra skill do projeto', () => {
        const { origem, projeto } = cenario();
        escrever(join(projeto, '.claude/skills/outra/SKILL.md'), 'skill do projeto\n');
        instalar(origem, projeto);
        writeFileSync(join(projeto, '.claude/skills/demo/SKILL.md'), 'editado à mão\n');
        instalar(origem, projeto);
        assert.deepEqual(conferir(origem, projeto), []);
        assert.equal(readFileSync(join(projeto, '.claude/skills/outra/SKILL.md'), 'utf8'), 'skill do projeto\n');
    });

    test('.DS_Store e __pycache__ não contam como diferença', () => {
        const { origem, projeto } = cenario();
        instalar(origem, projeto);
        escrever(join(projeto, '.claude/skills/demo/.DS_Store'), 'x');
        escrever(join(projeto, '.cursor/skills/demo/scripts/__pycache__/a.cpython-312.pyc'), 'x');
        assert.deepEqual(conferir(origem, projeto), []);
    });

    test('__pycache__ da origem não vai para a cópia', () => {
        const { origem, projeto } = cenario();
        escrever(join(origem, 'demo/scripts/__pycache__/a.cpython-312.pyc'), 'x');
        instalar(origem, projeto);
        assert.equal(existsSync(join(projeto, '.claude/skills/demo/scripts/__pycache__')), false);
    });

    test('reinstalar apaga da cópia o arquivo que saiu do modelo', () => {
        const { origem, projeto } = cenario();
        instalar(origem, projeto);
        escrever(join(projeto, '.codex/skills/demo/antigo.md'), 'saiu do modelo\n');
        instalar(origem, projeto);
        assert.equal(existsSync(join(projeto, '.codex/skills/demo/antigo.md')), false);
        assert.deepEqual(conferir(origem, projeto), []);
    });

    test('cópia com CRLF não conta como diferença (clone no Windows)', () => {
        const { origem, projeto } = cenario();
        instalar(origem, projeto);
        writeFileSync(join(projeto, '.claude/skills/demo/SKILL.md'), '---\r\nname: demo\r\n---\r\n');
        assert.deepEqual(conferir(origem, projeto), []);
    });

    test('recusa pasta de ferramenta que aponta para fora do projeto e não apaga nada lá', { skip: pularSymlink }, () => {
        const { origem, projeto } = cenario();
        const fora = join(dirname(projeto), 'fora', 'skills');
        escrever(join(fora, 'demo', 'NOTA.md'), 'de outro lugar\n');
        mkdirSync(join(projeto, '.claude'), { recursive: true });
        symlinkSync(fora, join(projeto, '.claude', 'skills'), 'dir');
        assert.throws(() => instalar(origem, projeto), /aponta para fora do projeto/);
        assert.equal(readFileSync(join(fora, 'demo', 'NOTA.md'), 'utf8'), 'de outro lugar\n');
    });

    test('linha de comando: opção desconhecida e projeto que não existe saem com 2', () => {
        const script = join(aqui, 'instalar.mjs');
        const rodar = (...args) => spawnSync(process.execPath, [script, ...args], { encoding: 'utf8' }).status;
        assert.equal(rodar('--chek'), 2);
        assert.equal(rodar('-check'), 2);
        assert.equal(rodar(join(tmp, 'nao-existe')), 2);
    });
});
