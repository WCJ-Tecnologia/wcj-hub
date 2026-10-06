import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { after, describe, test } from 'node:test';
import { fileURLToPath } from 'node:url';
import { acharPython, formaDasTarefas, normalizarCriterios, pareceConcluida } from './conferir-specs.mjs';
import { motivoParaPularSymlink } from './suporte-symlink.mjs';

const pularSymlink = motivoParaPularSymlink();
const aqui = dirname(fileURLToPath(import.meta.url));
const script = join(aqui, 'conferir-specs.mjs');
const SCRIPTS = join(aqui, 'tlc-spec-driven', 'scripts');
const tmp = mkdtempSync(join(tmpdir(), 'wcj-specs-'));
after(() => rmSync(tmp, { recursive: true, force: true }));

const PYTHON = acharPython();
let contador = 0;

const BOM = 'WHEN o sócio clica em **Iniciar obra** THEN o sistema SHALL gravar a data real do início';
const SEM_SHALL = 'QUANDO o sócio clica em **Iniciar obra** ENTÃO o sistema DEVE gravar a data real';
const OUTRO_BOM = 'IF a obra está entregue THEN o sistema SHALL recusar tarefa comum nova';

function spec({ titulo = '**Acceptance Criteria**:', criterios = [BOM, OUTRO_BOM], aprovada = true, confirmada = 'y', storyTitulo = '### P1: Iniciar obra ⭐ MVP' } = {}) {
    let n = 0;
    const lista = criterios.map((criterio) => (criterio === '' ? '' : `${++n}. ${criterio}`));
    return [
        '# Iniciar obra Specification',
        ...(aprovada ? ['', '**Approved by**: Lucas Scholz, 2026-09-29'] : []),
        '',
        '## Problem Statement',
        '',
        'A obra nasce a iniciar e só passa a em andamento quando o sócio confirma o começo real.',
        '',
        '## Out of Scope',
        '',
        '| Feature | Reason |',
        '| ------- | ------ |',
        '| Emitir fatura | o sistema só anexa a fatura emitida por fora |',
        '',
        '## Assumptions & Open Questions',
        '',
        '| Assumption / decision | Chosen default | Rationale | Confirmed? |',
        '| --------------------- | -------------- | --------- | ---------- |',
        `| Data real | a data do clique | o sócio registra na hora, da obra | ${confirmada} |`,
        '',
        '**Open questions:** none - all resolved or logged above.',
        '',
        '## User Stories',
        '',
        storyTitulo,
        '',
        '**User Story**: Como sócio, quero iniciar a obra para registrar a data real.',
        '',
        ...(titulo === null ? [] : [titulo]),
        ...lista,
        '',
        '**Independent Test**: criar obra a iniciar, clicar em Iniciar obra e ver a fase.',
        '',
        '## Requirement Traceability',
        '',
        '| Requirement ID | Story | Phase | Status |',
        '| -------------- | ----- | ----- | ------ |',
        '| OBRA-01 | P1: Iniciar obra | Design | Pending |',
        '',
    ].join('\n');
}

const INICIO_DAS_TAREFAS = [
    '# Iniciar obra Tasks',
    '',
    '## Test Coverage Matrix',
    '',
    '| Code Layer | Required Test Type | Coverage Expectation | Location Pattern | Run Command |',
    '| ---------- | ------------------ | -------------------- | ---------------- | ----------- |',
    '| Regra | unit | 1:1 com os critérios | `lib/*.test.ts` | `npm test` |',
    '',
    '## Gate Check Commands',
    '',
    '| Gate Level | When to Use | Command |',
    '| ---------- | ----------- | ------- |',
    '| Quick | teste da regra | `npm test` |',
    '',
];

function tarefa(id, { dependeDe = 'None', caixa = ' ', gate = true } = {}) {
    return ['', `### ${id}: tarefa ${id}`, '', `**Where**: \`lib/${id}.ts\``, `**Depends on**: ${dependeDe}`, '', `- [${caixa}] Gate check passes: \`npm test\``, '', '**Tests**: unit', ...(gate ? ['**Gate**: quick'] : [])];
}

function tarefas({ concluidas = false, comGate = true } = {}) {
    const caixa = concluidas ? 'x' : ' ';
    return [
        ...INICIO_DAS_TAREFAS,
        '## Execution Plan',
        '',
        '### Phase 1: Foundation',
        '',
        '```',
        'T1 → T2',
        '```',
        '',
        '## Task Breakdown',
        ...tarefa('T1', { caixa }),
        ...tarefa('T2', { dependeDe: 'T1', caixa, gate: comGate }),
        '',
    ].join('\n');
}

function tarefasEmDuasFases({ repetirFases, t1DependeDe = 'None', t3DependeDe = 'T2' }) {
    return [
        ...INICIO_DAS_TAREFAS,
        '## Execution Plan',
        '',
        '### Phase 1: Foundation',
        '',
        '```',
        'T1 → T2',
        '```',
        '',
        '### Phase 2: Core',
        '',
        '```',
        'T3',
        '```',
        '',
        '## Task Breakdown',
        ...(repetirFases ? ['', '### Phase 1: Foundation'] : []),
        ...tarefa('T1', { dependeDe: t1DependeDe }),
        ...tarefa('T2', { dependeDe: 'T1' }),
        ...(repetirFases ? ['', '### Phase 2: Core'] : []),
        ...tarefa('T3', { dependeDe: t3DependeDe }),
        '',
    ].join('\n');
}

const validacao = (veredito) =>
    [
        '# Iniciar obra Validation',
        '',
        `## Validation: Iniciar obra - ${veredito}`,
        '',
        `| WHEN o sócio clica | data gravada | \`lib/fase-obra.test.ts:12\` - \`assert.equal(inicio, hoje)\` | ${veredito} |`,
        '',
    ].join('\n');

function projeto(arquivos) {
    const raiz = join(tmp, `projeto-${++contador}`);
    mkdirSync(raiz, { recursive: true });
    for (const [caminho, conteudo] of Object.entries(arquivos)) {
        const destino = join(raiz, '.specs', 'features', caminho);
        mkdirSync(dirname(destino), { recursive: true });
        writeFileSync(destino, conteudo);
    }
    return raiz;
}

function conferir(raiz, env = process.env, extras = []) {
    const resultado = spawnSync(process.execPath, [script, raiz, ...extras], { encoding: 'utf8', env });
    return { status: resultado.status, saida: `${resultado.stdout}${resultado.stderr}` };
}

/** Roda o script da skill direto, sem o conferidor: prova o que ele sozinho faria. */
function skillSozinha(nomeDoScript, args) {
    const [programa, ...prefixo] = PYTHON;
    return spawnSync(programa, [...prefixo, join(SCRIPTS, nomeDoScript), ...args], { encoding: 'utf8' }).status;
}

describe('conferir-specs: critérios', () => {
    test('há Python 3 para os testes', () => {
        assert.ok(PYTHON, 'instale o Python 3 (python3, python ou py -3)');
    });

    test('projeto sem .specs passa', () => {
        const { status, saida } = conferir(projeto({}));
        assert.equal(status, 0, saida);
        assert.match(saida, /nada a conferir/);
    });

    test('spec no formato do padrão passa: estrutura da skill, texto em português', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec() }));
        assert.equal(status, 0, saida);
    });

    test('o formato do modelo da skill e do Prettier passa: linha em branco depois do título e entre os itens, texto no título do P1', () => {
        const titulo = '**Acceptance Criteria** (each line is one EARS pattern):';
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ titulo, criterios: ['', BOM, '', OUTRO_BOM] }) }));
        assert.equal(status, 0, saida);
    });

    test('critério sem SHALL reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ criterios: [SEM_SHALL] }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /no SHALL/);
    });

    test('critério sem SHALL depois de linha em branco reprova, e o validate_spec.py sozinho deixaria passar', () => {
        const raiz = projeto({ 'iniciar-obra/spec.md': spec({ criterios: ['', SEM_SHALL] }) });
        assert.equal(skillSozinha('validate_spec.py', [join(raiz, '.specs/features/iniciar-obra')]), 0, 'o validador da skill passou a conferir sozinho: reveja a regra');
        const { status, saida } = conferir(raiz);
        assert.equal(status, 1, saida);
        assert.match(saida, /no SHALL/);
    });

    test('critério sem SHALL sob o título do P1 com texto reprova', () => {
        const titulo = '**Acceptance Criteria** (each line is one EARS pattern):';
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ titulo, criterios: [SEM_SHALL] }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /no SHALL/);
    });

    test('critério sem SHALL depois de linha em branco no meio da lista reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ criterios: [BOM, '', SEM_SHALL] }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /no SHALL/);
    });

    test('variante do título em inglês também é lida: minúscula, sublinhado, dentro de item', () => {
        for (const titulo of ['__acceptance criteria__:', '- **Acceptance Criteria**:', '***Acceptance Criteria***:']) {
            const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ titulo, criterios: [SEM_SHALL] }) }));
            assert.equal(status, 1, `${titulo}\n${saida}`);
            assert.match(saida, /no SHALL/);
        }
    });

    test('título traduzido reprova: a story fica sem critério lido', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ titulo: '**Critérios de aceite**:', criterios: [SEM_SHALL] }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /não tem critério lido/);
    });

    test('spec sem story e sem critério reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ titulo: null, criterios: [], storyTitulo: '### História' }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /nenhum critério lido/);
    });

    test('aviso do validador não reprova, como na skill', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ criterios: ['O sistema SHALL mostrar o valor sem abreviar'] }) }));
        assert.equal(status, 0, saida);
        assert.match(saida, /com aviso/);
        assert.match(saida, /WARN/);
    });

    test('aviso com caractere fora do cp1252 não vira erro (a saída do Windows)', () => {
        const env = { ...process.env, PYTHONIOENCODING: 'cp1252' };
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ criterios: ['O sistema SHALL aceitar valor ≥ 0 → gravar'] }) }), env);
        assert.equal(status, 0, saida);
        assert.match(saida, /≥ 0 →/);
    });
});

describe('conferir-specs: tarefas', () => {
    test('tarefa sem Gate reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec(), 'iniciar-obra/tasks.md': tarefas({ comGate: false }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /missing `Gate` field/);
    });

    test('duas fases sem "### Phase N" no Task Breakdown reprovam', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec(), 'iniciar-obra/tasks.md': tarefasEmDuasFases({ repetirFases: false }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /não repete o "### Phase N"/);
    });

    test('com a fase repetida, dependência de fase anterior passa', () => {
        const raiz = projeto({ 'iniciar-obra/spec.md': spec(), 'iniciar-obra/tasks.md': tarefasEmDuasFases({ repetirFases: true }) });
        const { status, saida } = conferir(raiz);
        assert.equal(status, 0, saida);
    });

    test('com a fase repetida, dependência para a frente reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec(), 'iniciar-obra/tasks.md': tarefasEmDuasFases({ repetirFases: true, t1DependeDe: 'T3', t3DependeDe: 'None' }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /must point backward/);
    });
});

describe('conferir-specs: feature concluída', () => {
    test('feature em andamento não é cobrada, mesmo sendo a única do projeto (sem argumento, o validate_state.py cobraria)', () => {
        const raiz = projeto({ 'iniciar-obra/spec.md': spec(), 'iniciar-obra/tasks.md': tarefas() });
        assert.equal(skillSozinha('validate_state.py', ['--root', raiz]), 1, 'o validate_state.py mudou de regra: reveja a escolha de feature concluída');
        const { status, saida } = conferir(raiz);
        assert.equal(status, 0, saida);
        assert.doesNotMatch(saida, /validate_state/);
    });

    test('feature concluída sem validation.md reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec(), 'iniciar-obra/tasks.md': tarefas({ concluidas: true }) }));
        assert.equal(status, 1, saida);
        assert.match(saida, /no validation\.md/);
    });

    test('feature concluída com PASS e evidência arquivo:linha passa', () => {
        const raiz = projeto({
            'iniciar-obra/spec.md': spec(),
            'iniciar-obra/tasks.md': tarefas({ concluidas: true }),
            'iniciar-obra/validation.md': validacao('PASS'),
        });
        const { status, saida } = conferir(raiz);
        assert.equal(status, 0, saida);
        assert.match(saida, /validate_state\.py: ok/);
    });

    test('validation.md com FAIL reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec(), 'iniciar-obra/validation.md': validacao('FAIL') }));
        assert.equal(status, 1, saida);
        assert.match(saida, /verdict is FAIL/);
    });

    test('pasta de feature que é link também é conferida', { skip: pularSymlink }, () => {
        const fora = join(tmp, `fora-${++contador}`, 'iniciar-obra');
        mkdirSync(fora, { recursive: true });
        writeFileSync(join(fora, 'spec.md'), spec());
        writeFileSync(join(fora, 'validation.md'), validacao('FAIL'));
        const raiz = projeto({});
        mkdirSync(join(raiz, '.specs', 'features'), { recursive: true });
        symlinkSync(fora, join(raiz, '.specs', 'features', 'iniciar-obra'), 'dir');
        const { status, saida } = conferir(raiz);
        assert.equal(status, 1, saida);
        assert.match(saida, /verdict is FAIL/);
    });
});

describe('conferir-specs: aprovação', () => {
    test('durante a especificação, spec sem aprovação e com premissa aberta passa', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ aprovada: false, confirmada: 'n' }) }));
        assert.equal(status, 0, saida);
    });

    test('feature que passou da especificação sem "Approved by" reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ aprovada: false }), 'iniciar-obra/tasks.md': tarefas() }));
        assert.equal(status, 1, saida);
        assert.match(saida, /Approved by/);
    });

    test('premissa sem "y" reprova depois da especificação', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ confirmada: 'n' }), 'iniciar-obra/tasks.md': tarefas() }));
        assert.equal(status, 1, saida);
        assert.match(saida, /Confirmed\?/);
    });

    test('sobra do modelo reprova depois da especificação', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec({ criterios: ['WHEN [user action] THEN o sistema SHALL [expected behavior]'] }), 'iniciar-obra/tasks.md': tarefas() }));
        assert.equal(status, 1, saida);
        assert.match(saida, /sobra do modelo/);
    });

    test('feature com tasks.md e sem spec.md reprova', () => {
        const { status, saida } = conferir(projeto({ 'iniciar-obra/tasks.md': tarefas() }));
        assert.equal(status, 1, saida);
        assert.match(saida, /spec\.md não existe/);
    });
});

describe('conferir-specs: ambiente e uso', () => {
    test('sem Python 3, falha fechado com saída 2', () => {
        const env = { ...process.env, PYTHON: 'nao-existe-wcj' };
        const { status, saida } = conferir(projeto({ 'iniciar-obra/spec.md': spec() }), env);
        assert.equal(status, 2, saida);
        assert.match(saida, /Python 3 não encontrado \(PYTHON=nao-existe-wcj\)/);
    });

    test('acha o próximo Python da lista e recusa o que não é Python 3', () => {
        assert.deepEqual(acharPython([['nao-existe-wcj'], PYTHON]), PYTHON);
        assert.equal(acharPython([['nao-existe-wcj'], [process.execPath]]), null);
    });

    test('opção desconhecida sai com 2', () => {
        const { status } = conferir(projeto({}), process.env, ['--chek']);
        assert.equal(status, 2);
    });
});

describe('regras próprias', () => {
    test('a normalização não muda o número de linhas', () => {
        const original = spec({ titulo: '**Acceptance Criteria** (x):', criterios: ['', BOM, '', OUTRO_BOM] });
        const { texto, lidos } = normalizarCriterios(original);
        assert.equal(texto.split('\n').length, original.split('\n').length);
        assert.equal(lidos, 2);
    });

    test('uma fase só não pede "### Phase N" no Task Breakdown', () => {
        assert.deepEqual(formaDasTarefas(tarefas()), []);
        assert.equal(formaDasTarefas(tarefasEmDuasFases({ repetirFases: false })).length, 1);
        assert.deepEqual(formaDasTarefas(tarefasEmDuasFases({ repetirFases: true })), []);
    });

    test('feature concluída: validation.md existe, ou toda tarefa marcada', () => {
        const raiz = projeto({ 'a/tasks.md': '# Sem tarefa\n', 'b/validation.md': validacao('PASS'), 'c/tasks.md': tarefas({ concluidas: true }) });
        const base = join(raiz, '.specs', 'features');
        assert.equal(pareceConcluida(join(base, 'a')), false);
        assert.equal(pareceConcluida(join(base, 'b')), true);
        assert.equal(pareceConcluida(join(base, 'c')), true);
    });
});
