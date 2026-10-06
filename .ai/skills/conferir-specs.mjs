#!/usr/bin/env node
/**
 * Portão do .specs/: roda os validadores da skill tlc-spec-driven em cada feature do projeto. Roda no CI e antes de
 * apresentar spec ou tarefas para aprovação (.ai/core/especificacao.md, seção 5).
 *
 *   node .ai/skills/conferir-specs.mjs [projeto]      (padrão: o projeto onde este arquivo está)
 *
 * Por feature, em .specs/features/<feature>/:
 *   spec.md     validate_spec.py: seções, critério com SHALL, premissas preenchidas, IDs de requisito
 *   tasks.md    validate_tasks.py: Tests e Gate em toda tarefa, dependência só para trás, diagrama igual ao Depends on
 *   concluída   validate_state.py: validation.md com PASS e evidência arquivo:linha
 *
 * O julgamento é dos scripts da skill, sem alteração. O que é nosso:
 *   - Critérios. O validate_spec.py 3.3.0 lê a lista numerada que vem logo depois de uma linha "Acceptance Criteria"
 *     (com ** opcional e dois-pontos opcional) e para na primeira linha em branco. O modelo da própria skill tem uma
 *     linha em branco depois do título e, no P1, texto depois dele; o Prettier também põe a linha em branco. Nesses
 *     formatos nenhum critério é conferido, e critério sem SHALL passa. Aqui a spec vai ao validador numa cópia
 *     normalizada, com o mesmo número de linhas: variante do título em inglês vira "**Acceptance Criteria**:", e
 *     linha em branco antes de item da lista vira comentário. Story (### P1…) sem nenhum critério lido reprova, e
 *     assim também o título traduzido.
 *   - Fases. O validate_tasks.py dá a cada tarefa a fase do último "### Phase N" que veio antes dela. Com o título
 *     da fase só no Execution Plan, como no modelo da skill, toda tarefa cai na última fase: dependência para a
 *     frente passa e dependência legítima entre fases reprova. Aqui reprova tasks.md com mais de uma fase e nenhum
 *     "### Phase N" no Task Breakdown.
 *   - Aprovação. Feature que passou da especificação (tem design.md, tasks.md ou validation.md) precisa de spec.md
 *     com "**Approved by**: <nome>, <AAAA-MM-DD>", "y" em "Confirmed?" em toda premissa e nenhuma sobra do modelo.
 *   - Quem está concluída. Sem feature no argumento, o validate_state.py cobra até a feature em andamento quando o
 *     projeto tem uma só. A escolha é feita aqui, com a regra da skill (_appears_complete), feature por feature.
 * Aviso (WARN) dos validadores não reprova, como na skill.
 * Python: o que estiver em PYTHON, ou python3, python e py -3, nessa ordem; só vale se "--version" disser Python 3.
 * Saída: 0 tudo certo, 1 algum problema, 2 uso errado ou Python 3 não encontrado.
 */
import { spawnSync } from 'node:child_process';
import { existsSync, mkdtempSync, readFileSync, readdirSync, realpathSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const aqui = dirname(fileURLToPath(import.meta.url));
const SCRIPTS = join(aqui, 'tlc-spec-driven', 'scripts');

// No Windows o Python grava em cp1252 quando a saída é um pipe: "≥" ou "→" num aviso viraria erro.
const AMBIENTE = { ...process.env, PYTHONUTF8: '1', PYTHONIOENCODING: 'utf-8' };

const TITULO_NORMALIZADO = '**Acceptance Criteria**:'; // casa com ^\*{0,2}Acceptance Criteria\*{0,2}\s*:?\s*$ do validate_spec.py
const VARIANTE_DE_TITULO = /^(?:[-*+]\s+)?(?:#{1,6}\s+)?[*_]{0,3}\s*acceptance criteria(?![a-z0-9])/i; // "_" conta como letra para o \b
const ITEM_NUMERADO = /^\s*\d+\.\s+/;
const FIM_DA_LISTA = /^#{1,3}\s/; // no validate_spec.py, cabeçalho ou linha que começa com ** encerra a lista
const STORY = /^###\s+P\d+\b/;
const FASE = /^#{2,4}\s+Phase\s+\d+/i; // a mesma do validate_tasks.py
const APROVACAO = /^\*\*Approved by\*\*:\s*[^<\s].*,\s*\d{4}-\d{2}-\d{2}\s*$/m;
const CONFIRMADA = /^(y|yes|sim)$/i;
const TRECHO_DO_MODELO = /\[[^\]\n]{2,}\](?!\()/; // [texto do modelo]; não [texto](link), nem a caixa [ ] ou [x]

/**
 * Normaliza os critérios sem mudar o número de linhas e conta o que o validate_spec.py vai ler.
 * Devolve { texto, lidos, porStory: [{ linha, titulo, lidos }], itens: [{ linha, texto }] }.
 */
export function normalizarCriterios(original) {
    const linhas = original.split(/\r?\n/);
    const itens = [];
    for (let i = 0; i < linhas.length; i++) {
        if (!VARIANTE_DE_TITULO.test(linhas[i].trim())) continue;
        linhas[i] = TITULO_NORMALIZADO;
        for (let j = i + 1; j < linhas.length; j++) {
            const linha = linhas[j];
            const limpa = linha.trim();
            if (ITEM_NUMERADO.test(linha)) {
                itens.push({ linha: j + 1, texto: linha.replace(ITEM_NUMERADO, '').trim() });
                continue;
            }
            if (limpa === '') {
                let k = j;
                while (k < linhas.length && linhas[k].trim() === '') k++;
                if (k >= linhas.length || !ITEM_NUMERADO.test(linhas[k])) break;
                for (let b = j; b < k; b++) linhas[b] = '<!-- -->';
                j = k - 1;
                continue;
            }
            if (FIM_DA_LISTA.test(linha) || limpa.startsWith('**')) break;
        }
    }
    const porStory = [];
    linhas.forEach((linha, i) => {
        if (!STORY.test(linha.trim())) return;
        let fim = linhas.findIndex((outra, j) => j > i && FIM_DA_LISTA.test(outra));
        if (fim === -1) fim = linhas.length;
        porStory.push({ linha: i + 1, titulo: linha.trim(), lidos: itens.filter((item) => item.linha > i + 1 && item.linha <= fim).length });
    });
    return { texto: linhas.join('\n'), lidos: itens.length, porStory, itens };
}

/** Problemas de forma da spec que o validate_spec.py não pega. */
export function formaDaSpec(original, { passouDaSpec = false } = {}) {
    const { lidos, porStory } = normalizarCriterios(original);
    const problemas = [];
    for (const story of porStory) {
        if (!story.lidos) {
            problemas.push(`L${story.linha}: "${story.titulo}" não tem critério lido. A lista vai sob o título em inglês "**Acceptance Criteria**:", numerada`);
        }
    }
    if (!lidos) problemas.push('nenhum critério lido: a spec passaria sem conferir SHALL');
    if (!passouDaSpec) return problemas;

    const linhas = original.split(/\r?\n/);
    if (!APROVACAO.test(original)) {
        problemas.push('falta "**Approved by**: <nome>, <AAAA-MM-DD>". A feature já passou da especificação, e a spec tem de estar aprovada');
    }
    let emCodigo = false;
    linhas.forEach((linha, i) => {
        if (linha.trim().startsWith('```')) emCodigo = !emCodigo;
        const trecho = emCodigo ? null : linha.replace(/`[^`]*`/g, '').match(TRECHO_DO_MODELO);
        if (trecho) problemas.push(`L${i + 1}: sobra do modelo sem preencher: ${trecho[0]}`);
    });
    for (const [numero, celulas] of premissas(linhas)) {
        if (/^\[.+\]$/.test(celulas[0] ?? '')) continue;
        if (!CONFIRMADA.test(celulas[3] ?? '')) {
            problemas.push(`L${numero}: a premissa "${celulas[0]}" não tem "y" em Confirmed?. Premissa sem confirmação segura a aprovação`);
        }
    }
    return problemas;
}

function celulasDaTabela(linha) {
    const limpa = linha.trim();
    if (!limpa.startsWith('|') || (/^\|?[\s:|-]+\|?$/.test(limpa) && limpa.includes('-'))) return null;
    return limpa.replace(/^\||\|$/g, '').split('|').map((celula) => celula.trim());
}

function premissas(linhas) {
    const inicio = linhas.findIndex((linha) => /^#{1,3}\s+Assumptions & Open Questions\s*$/.test(linha.trim()));
    if (inicio === -1) return [];
    const resultado = [];
    let cabecalho = true;
    for (let i = inicio + 1; i < linhas.length && !/^#{1,3}\s+\S/.test(linhas[i]); i++) {
        const celulas = celulasDaTabela(linhas[i]);
        if (!celulas) continue;
        if (cabecalho) {
            cabecalho = false;
            continue;
        }
        resultado.push([i + 1, celulas]);
    }
    return resultado;
}

/** Tarefas com mais de uma fase precisam do "### Phase N" também no Task Breakdown. */
export function formaDasTarefas(texto) {
    const linhas = texto.split(/\r?\n/);
    const secao = (nome) => {
        const inicio = linhas.findIndex((linha) => new RegExp(`^##\\s+${nome}\\b`).test(linha.trim()));
        if (inicio === -1) return [];
        let fim = linhas.findIndex((linha, j) => j > inicio && /^##\s/.test(linha.trim()));
        if (fim === -1) fim = linhas.length;
        return linhas.slice(inicio + 1, fim);
    };
    const fasesNoPlano = secao('Execution Plan').filter((linha) => FASE.test(linha.trim())).length;
    const fasesNasTarefas = secao('Task Breakdown').filter((linha) => FASE.test(linha.trim())).length;
    if (fasesNoPlano > 1 && fasesNasTarefas === 0) {
        return [
            'o Task Breakdown não repete o "### Phase N" antes das tarefas de cada fase. Sem isso o validate_tasks.py põe toda tarefa na última fase: dependência para a frente passa e dependência legítima entre fases reprova',
        ];
    }
    return [];
}

/** A regra do _appears_complete do validate_state.py 3.3.0. */
export function pareceConcluida(pasta) {
    if (existsSync(join(pasta, 'validation.md'))) return true;
    const tarefas = join(pasta, 'tasks.md');
    if (!existsSync(tarefas)) return false;
    const corpo = readFileSync(tarefas, 'utf8');
    if (!/^#{2,4}\s+T\d+\s*:/m.test(corpo)) return false;
    return !/^\s*-\s*\[\s\]/m.test(corpo);
}

/** Devolve o comando que roda o Python 3 ([programa, ...argumentos]) ou null. */
export function acharPython(candidatos = process.env.PYTHON ? [process.env.PYTHON.trim().split(/\s+/)] : [['python3'], ['python'], ['py', '-3']]) {
    for (const [programa, ...prefixo] of candidatos) {
        const resultado = spawnSync(programa, [...prefixo, '--version'], { encoding: 'utf8', env: AMBIENTE });
        if (resultado.error || resultado.status !== 0) continue;
        if (/^Python 3\./.test(`${resultado.stdout ?? ''}${resultado.stderr ?? ''}`.trim())) return [programa, ...prefixo];
    }
    return null;
}

function rodar(python, script, args) {
    const [programa, ...prefixo] = python;
    const resultado = spawnSync(programa, [...prefixo, join(SCRIPTS, script), ...args], { encoding: 'utf8', env: AMBIENTE });
    return { status: resultado.status, saida: `${resultado.stdout ?? ''}${resultado.stderr ?? ''}`.trim() };
}

function validarSpec(python, arquivo) {
    const { texto } = normalizarCriterios(readFileSync(arquivo, 'utf8'));
    const pasta = mkdtempSync(join(tmpdir(), 'wcj-spec-'));
    try {
        const copia = join(pasta, 'spec.md');
        writeFileSync(copia, texto);
        const resultado = rodar(python, 'validate_spec.py', [copia]);
        return { ...resultado, saida: resultado.saida.split(copia).join(arquivo) };
    } finally {
        rmSync(pasta, { recursive: true, force: true });
    }
}

function ehPasta(caminho) {
    try {
        return statSync(caminho).isDirectory();
    } catch {
        return false;
    }
}

/** Devolve o código de saída: 0 tudo certo, 1 algum problema, 2 sem Python 3. */
export function conferirSpecs(projeto) {
    const base = join(projeto, '.specs', 'features');
    if (!existsSync(base)) {
        console.log('conferir-specs: sem .specs/features/, nada a conferir');
        return 0;
    }
    const features = readdirSync(base)
        .filter((nome) => ehPasta(join(base, nome)))
        .sort();
    if (!features.length) {
        console.log('conferir-specs: .specs/features/ está vazia, nada a conferir');
        return 0;
    }
    const python = acharPython();
    if (!python) {
        const onde = process.env.PYTHON ? `PYTHON=${process.env.PYTHON}` : 'python3, python ou py -3';
        console.error(`conferir-specs: Python 3 não encontrado (${onde}). A skill tlc-spec-driven precisa dele: .ai/core/especificacao.md, seção 7`);
        return 2;
    }
    let problemas = 0;
    const acusar = (feature, arquivo, mensagens) => {
        for (const mensagem of mensagens) console.error(`[${feature}] ${arquivo} ${mensagem}`);
        problemas += mensagens.length;
    };
    for (const feature of features) {
        const pasta = join(base, feature);
        const tem = (arquivo) => existsSync(join(pasta, arquivo));
        const passouDaSpec = ['design.md', 'tasks.md', 'validation.md'].some(tem);
        const resultados = [];
        if (tem('spec.md')) {
            acusar(feature, 'spec.md', formaDaSpec(readFileSync(join(pasta, 'spec.md'), 'utf8'), { passouDaSpec }));
            resultados.push(['validate_spec.py', validarSpec(python, join(pasta, 'spec.md'))]);
        } else if (passouDaSpec) {
            acusar(feature, 'spec.md', ['não existe, e a feature já passou da especificação']);
        }
        if (tem('tasks.md')) {
            acusar(feature, 'tasks.md', formaDasTarefas(readFileSync(join(pasta, 'tasks.md'), 'utf8')));
            resultados.push(['validate_tasks.py', rodar(python, 'validate_tasks.py', [pasta])]);
        }
        if (pareceConcluida(pasta)) resultados.push(['validate_state.py', rodar(python, 'validate_state.py', [pasta, '--root', projeto])]);
        for (const [script, { status, saida }] of resultados) {
            if (status !== 0) {
                console.error(`[${feature}] ${script} reprovou\n${saida}`);
                problemas++;
            } else {
                console.log(`[${feature}] ${script}: ok${/\bWARN\b/.test(saida) ? `, com aviso\n${saida}` : ''}`);
            }
        }
    }
    console.log(`conferir-specs: ${features.length} feature(s), ${problemas} problema(s)`);
    return problemas ? 1 : 0;
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
    const opcoes = args.filter((arg) => arg.startsWith('-'));
    const alvos = args.filter((arg) => !arg.startsWith('-'));
    if (opcoes.length || alvos.length > 1) {
        console.error('uso: node .ai/skills/conferir-specs.mjs [projeto]');
        process.exit(2);
    }
    const projeto = resolve(alvos[0] ?? join(aqui, '..', '..'));
    if (!existsSync(projeto)) {
        console.error(`pasta não existe: ${projeto}`);
        process.exit(2);
    }
    process.exit(conferirSpecs(projeto));
}
