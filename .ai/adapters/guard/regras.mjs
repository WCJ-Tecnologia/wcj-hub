/**
 * Travas do padrão WCJ para agentes de IA. Um motor só, usado por todos os adaptadores
 * (Claude Code, Cursor), para as ferramentas não divergirem.
 *
 * Decisões: 'allow' (segue o fluxo normal da ferramenta), 'ask' (humano confirma), 'deny'.
 * É trava contra acidente, não contra agente decidido a contornar: o que ela não vê
 * (script dentro de script, alias, programa que apaga por conta própria) continua
 * coberto só pela regra escrita.
 */
import { statSync } from 'node:fs';
import { basename } from 'node:path';
import { composeService, expandPath, LOOPBACK_HOSTS, findPackageScripts, globToRegExp, isGuardFile, isSecretPath, makeContext, SECRET_SAMPLES } from './caminhos.mjs';
import { BLOCK_CLOSE, BLOCK_OPEN, leadingKeywords, lex, RESERVED, segments } from './shell.mjs';
import {
    checkAlembic,
    checkArtisan,
    checkFindSecret,
    checkGit,
    checkGuardWrites,
    checkPrisma,
    checkProductionHints,
    checkRecursiveGrep,
    checkRm,
    checkSecretAccess,
    checkSecretCli,
    checkVercel,
    commandName,
    finding,
    PROD_NAME,
    PYTHON,
    pythonModule,
    VERCEL_GLOBAL_FLAGS,
} from './travas.mjs';

export { makeContext };

const SHELLS = new Set(['sh', 'bash', 'zsh', 'dash', 'ksh', 'fish']);
const REMOTE_SHELLS = new Set(['ssh', 'mosh']);
const REMOTE_COPY = new Set(['scp', 'sftp', 'rsync']);

const RULES_DOC = {
    'rm-recursivo': '.ai/core/git.md, seção 3a',
    'git-force': '.ai/core/git.md, seção 3a',
    'git-reset-hard': '.ai/core/git.md, seção 3a',
    'git-clean': '.ai/core/git.md, seção 3a',
    segredo: '.ai/core/seguranca.md, seções 9 e 12',
    'banco-destrutivo': '.ai/core/banco.md, seção 7',
    producao: '.ai/core/principios.md, seção 5',
    'trava-protegida': '.ai/adapters/README.md',
    'analise-incompleta': '.ai/adapters/README.md',
};

const RANK = { allow: 0, ask: 1, deny: 2 };

/**
 * Junta as constatações na decisão mais restritiva e monta a mensagem para o agente.
 */
export function combine(findings) {
    if (!findings.length) return { decision: 'allow', rule: null, reason: '', message: '' };
    const worst = Math.max(...findings.map((f) => RANK[f.decision]));
    const decision = Object.keys(RANK).find((k) => RANK[k] === worst);
    const top = findings.filter((f) => f.decision === decision);
    const unique = [...new Map(top.map((f) => [`${f.rule}|${f.reason}`, f])).values()];
    const reason = unique.map((f) => f.reason).join('; ');
    const rules = [...new Set(unique.map((f) => f.rule))];
    const docs = [...new Set(rules.map((r) => RULES_DOC[r]).filter(Boolean))].join('; ');
    const prefix = decision === 'deny' ? 'Bloqueado pela trava WCJ' : 'Trava WCJ pede confirmação humana';
    const tail =
        decision === 'deny'
            ? 'Não tente contornar com outro comando, outro caminho ou script. Explique ao humano o que você queria fazer e por quê, e deixe que ele execute ou decida.'
            : 'Só siga se o humano confirmar.';
    return { decision, rule: rules.join(','), reason, message: `${prefix} (${rules.join(', ')}): ${reason}. ${tail} Regra: ${docs}.` };
}

// ---------------------------------------------------------------------------
// Arquivos (ferramentas de leitura e escrita do editor)
// ---------------------------------------------------------------------------

export function checkFile(path, { write = false } = {}, ctx) {
    if (!path) return combine([]);
    const findings = [];
    if (isSecretPath(path, ctx)) {
        findings.push(
            finding('deny', 'segredo', write
                ? `${path} guarda segredo; o agente não escreve nele. O humano edita o arquivo`
                : `${path} guarda segredo; o agente não lê. Para saber se uma variável existe, procure o NOME dela no código ou no .env.example`),
        );
    }
    if (write && isGuardFile(path, ctx)) {
        findings.push(finding('ask', 'trava-protegida', `${path} configura as travas do agente; mudar exige um humano`));
    }
    return combine(findings);
}

export function checkSearch({ path, glob }, ctx) {
    const findings = [];
    if (path && isSecretPath(path, ctx)) findings.push(finding('deny', 'segredo', `busca dentro de ${path}, que guarda segredo`));
    if (glob) {
        const regex = globToRegExp(glob.replace(/^\*\*\//, ''));
        const hit = SECRET_SAMPLES.find((s) => regex.test(s));
        if (hit) findings.push(finding('deny', 'segredo', `o filtro ${glob} inclui arquivos de segredo (${hit})`));
    }
    return combine(findings);
}

// ---------------------------------------------------------------------------
// Shell
// ---------------------------------------------------------------------------

export function checkCommand(command, ctx) {
    const findings = [];
    const state = { cwd: ctx.cwd, env: {}, remote: false };
    analyze(command ?? '', state, findings, ctx, 0);
    return combine(findings);
}

function analyze(src, state, findings, ctx, depth) {
    if (depth > 6) {
        findings.push(finding('ask', 'analise-incompleta', 'comando aninhado demais para a trava analisar'));
        return;
    }
    const { tokens, nested } = lex(src);
    for (const inner of nested) analyze(inner, fork(state), findings, ctx, depth + 1);
    // `( ... )` abre um escopo; comando do meio de pipeline ou em segundo plano roda em subshell: o `cd` e o `export`
    // dali não chegam ao comando seguinte. cd e export que podem não acontecer (dentro de if, while ou for, depois de
    // && ou ||, no fim de pipeline, que o zsh roda no shell atual e o bash não, em sequência que vai para segundo
    // plano) e cd que falha (pasta que não existe) abrem o mundo sem eles: cada comando seguinte passa pelas travas em
    // todos os mundos, e vale o pior. O que depende deles com && só roda no mundo em que aconteceram.
    // O estado que chega é o de fora: o que ficou pendente lá não é deste comando (script do package.json, eval).
    const scopes = [[{ ...state, pending: [] }]];
    let block = 0;
    const segs = segments(tokens);
    // Sequência com && ou || que termina em &: o bash a põe inteira em segundo plano, o zsh só o último comando.
    const background = segs.map(() => false);
    for (let i = 0, start = 0; i < segs.length; i++) {
        if (['&&', '||'].includes(segs[i].next)) continue;
        if (segs[i].next === '&') background.fill(true, start, i + 1);
        start = i + 1;
    }
    for (const [i, seg] of segs.entries()) {
        // Fora da sequência com && e do pipeline, o que ficou pendente abre o mundo sem ele.
        if (!['&&', '|', '|&'].includes(seg.link)) for (const level of scopes) branch(level, findings);
        for (let n = 0; n < seg.open; n++) scopes.push(scopes.at(-1).map(fork));
        const worlds = scopes.at(-1);
        const opens = blockDelta(seg.words);
        if (opens > 0) block += opens;
        const subshell = ['|', '|&', '&'].includes(seg.next);
        const tail = ['|', '|&'].includes(seg.link) && !subshell;
        const uncertain = tail || block > 0 || background[i] || ['&&', '||'].includes(seg.link);
        const flags = { ...seg, uncertain };
        // O motivo que só o mundo sem o cd ou o export aparece com a explicação.
        const start = findings.length;
        for (const world of worlds.filter((w) => !w.alt)) analyzeSegment(flags, subshell ? fork(world) : world, findings, ctx, depth);
        const seen = new Set(findings.slice(start).map((f) => f.reason));
        for (const world of worlds.filter((w) => w.alt)) {
            const from = findings.length;
            analyzeSegment(flags, subshell ? fork(world) : world, findings, ctx, depth);
            for (const f of findings.slice(from)) if (!seen.has(f.reason)) f.reason += ALT_NOTE;
        }
        if (opens < 0) block = Math.max(0, block + opens);
        for (let n = 0; n < seg.close && scopes.length > 1; n++) scopes.pop();
        // No fim de pipeline, os dois mundos já valem para o comando seguinte, mesmo com &&.
        if (tail) branch(worlds, findings);
    }
}

const ALT_NOTE = ' (no caso em que um cd ou export anterior não aconteceu: encadeie com && até o comando)';
const MAX_WORLDS = 16;

// Blocos do shell: o que roda dentro deles pode não acontecer.
function blockDelta(words) {
    return leadingKeywords(words).reduce((d, w) => d + BLOCK_OPEN.has(w) - BLOCK_CLOSE.has(w), 0);
}

// Estado para outro processo (subshell, $( ), bash -c): o que ele muda não volta.
function fork(state) {
    return { ...state, env: { ...state.env }, pending: [] };
}

/**
 * Abre, para cada cd ou export pendente, o mundo em que ele não aconteceu. Mundos iguais viram um; acima de
 * MAX_WORLDS, a trava pergunta.
 */
function branch(level, findings) {
    const out = [];
    for (const world of level) {
        out.push(world);
        for (const before of world.pending ?? []) out.push({ ...world, cwd: before.cwd, env: { ...before.env }, pending: [], alt: true });
        world.pending = [];
    }
    const seen = new Set();
    const unique = out.filter((w) => {
        const key = `${w.cwd}\n${JSON.stringify(w.env)}`;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
    });
    if (unique.length > MAX_WORLDS) findings.push(finding('ask', 'analise-incompleta', 'cd e export incertos demais para a trava analisar'));
    level.splice(0, level.length, ...unique.slice(0, MAX_WORLDS));
}

function isDirectory(path) {
    try {
        return statSync(path).isDirectory();
    } catch {
        return false;
    }
}

function takeAssignments(words, into) {
    while (words.length && /^[A-Za-z_][A-Za-z0-9_]*=/.test(words[0].value)) {
        const [key, ...rest] = words.shift().value.split('=');
        into[key] = rest.join('=');
    }
}

const WRAPPER_ARG_FLAGS = {
    sudo: ['-u', '-g', '-C', '-p', '-h', '-U', '-r', '-t', '-D', '-T', '-R', '-a', '-c', '--user', '--group', '--chdir', '--prompt', '--host', '--other-user', '--role', '--type', '--close-from', '--command-timeout', '--chroot', '--auth-type', '--login-class'],
    doas: ['-u', '-C'],
    env: ['-u', '--unset', '-C', '--chdir', '-S', '--split-string'],
    nice: ['-n', '--adjustment'],
    timeout: ['-s', '--signal', '-k', '--kill-after'],
    // --max-lines, --eof e --replace (GNU) só recebem valor colado com =.
    xargs: ['-I', '-n', '-P', '-L', '-d', '-E', '-s', '-a', '--max-args', '--max-procs', '--delimiter', '--max-chars', '--arg-file', '--process-slot-var'],
    stdbuf: ['-i', '-o', '-e', '--input', '--output', '--error'],
    npx: ['-p', '--package'],
    bunx: ['-p', '--package'],
    pnpx: [],
    time: [],
    command: [],
    builtin: [],
    exec: ['-a'],
    nohup: [],
    caffeinate: ['-t', '-w'],
};

// uv, poetry, pipenv, pdm, rye, hatch, pixi, pipx e conda: `<ferramenta> [opções] run [opções] comando` roda o
// comando no ambiente Python do projeto; uvx (uv tool run) roda direto. As opções que recebem valor saem do --help.
const UV_VALUE_FLAGS = [
    '--extra', '--no-extra', '--group', '--no-group', '--only-group', '--no-editable-package', '--env-file', '-w', '--with', '--with-editable',
    '--with-requirements', '--package', '--python-platform', '--index', '--default-index', '-i', '--index-url', '--extra-index-url', '-f',
    '--find-links', '--index-strategy', '--keyring-provider', '-P', '--upgrade-package', '--upgrade-group', '--resolution', '--prerelease',
    '--fork-strategy', '--exclude-newer', '--exclude-newer-package', '--no-sources-package', '--reinstall-package', '--link-mode', '-C',
    '--config-setting', '--config-settings-package', '--no-build-isolation-package', '--no-build-package', '--no-binary-package', '--cache-dir',
    '--refresh-package', '-p', '--python', '--color', '--allow-insecure-host', '--directory', '--project', '--config-file',
    '--from', '-c', '--constraints', '-b', '--build-constraints', '--overrides', '--torch-backend', '--python-preference',
];
const CONDA = { values: new Set(['-n', '--name', '-p', '--prefix', '--cwd']), dirs: ['--cwd'], envs: ['-n', '--name'] };
const PYTHON_RUNNERS = {
    uv: { values: new Set(UV_VALUE_FLAGS), dirs: ['--directory'], envs: [] },
    poetry: { values: new Set(['-C', '--directory', '-P', '--project']), dirs: ['-C', '--directory'], envs: [] },
    pipenv: { values: new Set(['--python', '--pypi-mirror']), dirs: [], envs: [] },
    pdm: { values: new Set(['-p', '--project', '--venv', '--skip', '-c', '--config']), dirs: [], envs: ['--venv'] },
    rye: { values: new Set(['--pyproject']), dirs: [], envs: [] },
    hatch: { values: new Set(['-e', '--env', '-p', '--project', '--data-dir', '--cache-dir', '--config']), dirs: [], envs: ['-e', '--env'] },
    pixi: { values: new Set(['-e', '--environment', '--manifest-path', '--color']), dirs: [], envs: ['-e', '--environment'] },
    pipx: { values: new Set(['--spec', '--python', '--pip-args', '--index-url']), dirs: [], envs: [] },
    conda: CONDA,
    mamba: CONDA,
    micromamba: CONDA,
};
// Opções do uv e afins que recebem endereço de rede: host com cara de produção nelas pergunta.
const NETWORK_OPTIONS = new Set(['--index-url', '-i', '--extra-index-url', '--index', '--default-index', '-f', '--find-links', '--allow-insecure-host', '--trusted-host', '--pypi-mirror']);
// Ferramentas que também rodam como módulo: `python -m uv run ...`, `python -m dotenv run ...`.
const RUNNER_MODULES = new Set([...Object.keys(PYTHON_RUNNERS), 'dotenv']);

/**
 * Tira `uv run`, `uvx`, `poetry run` e afins da frente do comando. As opções da ferramenta (e os valores delas) vão
 * para `meta.hidden`: somem do comando, mas as travas de segredo, de host de produção e de arquivo de trava ainda os
 * veem. Guarda o --env-file (a trava de banco lê o arquivo), a pasta do --directory e o nome do ambiente (hatch -e,
 * pixi -e, conda -n). Devolve false se a ferramenta não roda comando.
 */
function unwrapPythonRunner(words, name, meta) {
    const spec = PYTHON_RUNNERS[name === 'uvx' ? 'uv' : name];
    let k = 1;
    let module = false;
    const skipOptions = () => {
        for (; k < words.length && words[k].value.startsWith('-') && words[k].value !== '--'; k++) {
            const start = k;
            const [flag, inline] = words[k].value.split(/=(.*)/s);
            const value = inline ?? (spec.values.has(flag) ? words[++k]?.value : undefined);
            meta.hidden.push(...words.slice(start, k + 1));
            if (NETWORK_OPTIONS.has(flag)) meta.runnerOptions.push(...words.slice(start, k + 1));
            if (flag === '--env-file' && value) meta.envFiles.push(value);
            if (spec.dirs.includes(flag) && value) meta.dir = value;
            if (spec.envs.includes(flag) && value) meta.runnerEnv = value;
            if (spec === PYTHON_RUNNERS.uv && (flag === '-m' || flag === '--module')) module = true;
        }
    };
    skipOptions();
    if (name !== 'uvx') {
        if (name === 'uv' && words[k]?.value === 'tool') k++;
        if (name === 'hatch' && words[k]?.value === 'env') k++;
        if (words[k]?.value !== 'run' && !(name === 'pixi' && words[k]?.value === 'exec')) return false;
        k++;
        skipOptions();
    }
    if (words[k]?.value === '--') k++;
    meta.runner = name;
    words.splice(0, k);
    // hatch run ambiente:comando
    if (name === 'hatch' && /^[\w.-]+:/.test(words[0]?.value ?? '')) {
        meta.runnerEnv = words[0].value.slice(0, words[0].value.indexOf(':'));
        words[0] = { ...words[0], value: words[0].value.slice(words[0].value.indexOf(':') + 1) };
    }
    // `uv run -m alembic` é `python -m alembic`.
    if (module) words.unshift({ type: 'word', value: 'python' }, { type: 'word', value: '-m' });
    return true;
}

/**
 * dotenv-cli (Node) e python-dotenv: o que eles carregam é o ambiente do comando, e a trava de banco precisa saber
 * de onde. `list`, `get`, `set` e `unset` (python-dotenv) e `-p` (dotenv-cli) leem ou gravam o próprio arquivo:
 * isso vai para `meta.hidden`, e a trava de segredo nega.
 */
function unwrapDotenv(words, assigns, meta) {
    words.shift();
    const files = [];
    let prints = false;
    while (words.length && words[0].value.startsWith('-') && words[0].value !== '--') {
        const flag = words.shift().value;
        if (['-e', '-f', '--file'].includes(flag) && words.length) files.push(words.shift().value);
        else if (flag.startsWith('--file=')) files.push(flag.slice('--file='.length));
        else if (['-q', '--quote'].includes(flag) && words.length) words.shift();
        else if (flag === '-p' && words.length) {
            words.shift();
            prints = true;
        } else if (flag === '-v' && words.length) {
            const pair = words.shift().value;
            const eq = pair.indexOf('=');
            if (eq > 0) assigns[pair.slice(0, eq)] = pair.slice(eq + 1);
        } else if (flag === '-c') {
            // O dotenv-cli (minimist) toma a palavra seguinte como o ambiente, com ou sem `--` depois.
            const environment = words.length && !words[0].value.startsWith('-') ? words.shift().value : null;
            files.push(...(environment ? [`.env.${environment}.local`, `.env.${environment}`] : []), '.env.local');
        }
    }
    meta.envFiles.push(...files);
    meta.runner = 'dotenv';
    if (words[0]?.value === '--') words.shift();
    // python-dotenv: `dotenv -f arquivo run [--override] comando`.
    const subcommand = words[0]?.value;
    if (subcommand === 'run') {
        words.shift();
        while (words[0]?.value.startsWith('--')) words.shift();
    }
    // O que ele carrega: imprimir o ambiente depois (printenv, env sem comando) é ler o arquivo (analyzeSegment).
    meta.dotenvFiles = files.length ? files : ['.env'];
    const reads = ['list', 'get', 'set', 'unset'].includes(subcommand);
    if (prints || reads) meta.hidden.push(...meta.dotenvFiles.map((value) => ({ type: 'word', value })));
    if (reads) words.splice(0, words.length);
}

// Ferramentas que injetam ambiente (cofre de segredo, CLI de plataforma) e rodam o comando nesta máquina: a trava
// não vê o que elas injetam. `sub` é o subcomando que roda outro comando (lista dentro da lista: alternativas),
// `values` as opções que recebem valor, `choose` as que escolhem o ambiente (o nome pode dizer produção; token e
// senha nunca entram aí, para não voltar na mensagem), `args` quantas palavras depois das opções ainda são delas
// (perfil do aws-vault, pasta do direnv) e `text` a opção que recebe o comando como texto.
const INJECTORS = {
    doppler: {
        sub: ['run'],
        values: ['-p', '--project', '-c', '--config', '--mount', '--mount-format', '--mount-template', '--mount-max-reads', '--name-transformer', '--fallback', '--passphrase', '-t', '--token', '--scope', '--config-dir', '--api-host', '--dashboard-host', '--dynamic-ttl', '--only-secrets', '--command'],
        choose: ['-c', '--config'],
        chooseEnv: ['DOPPLER_CONFIG', 'DOPPLER_ENVIRONMENT'],
        text: ['--command'],
    },
    infisical: { sub: ['run'], values: ['--env', '--path', '--projectId', '--token', '--domain', '--tags', '--command'], choose: ['--env'], text: ['--command'] },
    op: { sub: ['run'], values: ['--env-file', '--account', '--config', '--session', '--encoding'], choose: ['--env-file'] },
    dotenvx: { sub: ['run'], values: ['-e', '--env', '-f', '--env-file', '-fk', '--env-keys-file', '-fv', '--env-vault-file', '--convention', '--ignore', '-l', '--log-level'], choose: ['-f', '--env-file'] },
    'aws-vault': { sub: ['exec'], values: ['-d', '--duration', '--region', '-t', '--mfa-token', '--prompt', '--backend'], args: 1 },
    railway: { sub: ['run'], values: ['-s', '--service', '-e', '--environment', '-p', '--project'], choose: ['-e', '--environment'] },
    direnv: { sub: ['exec'], values: [], args: 1 },
    vercel: { sub: ['env', 'run'], values: [...VERCEL_GLOBAL_FLAGS, '-e', '--environment', '--git-branch'], choose: ['-e', '--environment'] },
};
INJECTORS.vc = INJECTORS.vercel;
// Rodam o comando no servidor da plataforma: pergunta como o ssh, e a trava de banco nega. No dyno do Heroku o
// comando roda num shell (`shell`): entre aspas ou não, vale o texto inteiro.
const REMOTE_RUNNERS = {
    heroku: { sub: [['run', 'run:detached', 'run:inside']], values: ['-a', '--app', '-r', '--remote', '-s', '--size', '-e', '--env', '--type'], shell: true },
    fly: { sub: ['ssh', 'console'], values: ['-a', '--app', '-u', '--user', '-r', '--region', '--machine', '--process-group', '-A', '--address', '-c', '--config', '-C', '--command'], text: ['-C', '--command'] },
    railway: { sub: ['ssh'], values: ['-s', '--service', '-e', '--environment', '-p', '--project'] },
};
REMOTE_RUNNERS.flyctl = REMOTE_RUNNERS.fly;

/**
 * Tira `doppler run --`, `heroku run` e afins da frente do comando. As opções e os argumentos da ferramenta vão para
 * `meta.hidden` (as travas de segredo, de host e de arquivo de trava continuam vendo) e o ambiente escolhido pelo
 * nome, para `meta.choices`. Comando passado como texto (--command, -C) vira `sh -c`. Opção que a trava não conhece
 * pode ter valor: antes do subcomando, a palavra seguinte é tomada como valor; depois dele, o que vem depois de um
 * `--` também é analisado (`meta.alternatives`), para a opção não esconder o comando. Devolve false se a ferramenta
 * não está rodando comando (`doppler secrets`, `vercel env ls`).
 */
function unwrapRunner(words, spec, meta) {
    const values = new Set(spec.values);
    const hidden = [];
    const choices = [];
    let text = null;
    let k = 1;
    let matched = 0;
    // Devolve true se a última opção lida é desconhecida e sem valor colado: a palavra seguinte pode ser o valor dela.
    const options = () => {
        let unknown = false;
        while (k < words.length && words[k].value.startsWith('-') && words[k].value !== '-' && words[k].value !== '--') {
            const start = k;
            let [flag, inline] = words[k].value.split(/=(.*)/s);
            // Opção curta com o valor colado: -cprd.
            if (inline === undefined && !values.has(flag) && /^-[A-Za-z]./.test(flag) && values.has(flag.slice(0, 2))) [flag, inline] = [flag.slice(0, 2), flag.slice(2)];
            const value = inline ?? (values.has(flag) ? words[++k]?.value : undefined);
            unknown = !values.has(flag) && inline === undefined;
            if (value !== undefined && spec.choose?.includes(flag)) choices.push(value);
            if (spec.text?.includes(flag)) text = value ?? '';
            hidden.push(...words.slice(start, k + 1));
            k++;
        }
        return unknown;
    };
    for (;;) {
        const unknown = options();
        if (matched === spec.sub.length) break;
        if ([spec.sub[matched]].flat().includes(words[k]?.value)) {
            matched++;
            k++;
        } else if (unknown && k < words.length && words[k].value !== '--') hidden.push(words[k++]);
        else break;
    }
    if (matched < spec.sub.length) return false;
    for (let n = 0; n < (spec.args ?? 0) && k < words.length && words[k].value !== '--'; n++) {
        choices.push(words[k].value);
        hidden.push(words[k++]);
        options();
    }
    if (words[k]?.value === '--') k++;
    else {
        const dash = words.findIndex((w, j) => j >= k && w.value === '--');
        if (dash !== -1) meta.alternatives.push(words.slice(dash + 1));
    }
    meta.hidden.push(...hidden);
    meta.choices.push(...choices);
    words.splice(0, k);
    if (spec.shell && words.length) text = words.map((w) => w.value).join(' ');
    if (text !== null) words.splice(0, words.length, { type: 'word', value: 'sh' }, { type: 'word', value: '-c' }, { type: 'word', value: text });
    return true;
}

// O nome do programa é o do commandName (`RM` acha o rm). Os comandos do shell que a trava acompanha (cd, pushd,
// popd, export) diferenciam maiúscula: `CD pasta` roda /usr/bin/cd num processo à parte e não muda a pasta.
const SHELL_STATE = new Set(['cd', 'pushd', 'popd', 'export']);
function programName(word) {
    const name = commandName(word);
    return SHELL_STATE.has(name) && !SHELL_STATE.has(basename(word)) ? basename(word) : name;
}
// Prefixos do próprio shell: o comando depois deles roda no mesmo processo (`command cd` muda a pasta).
const SAME_PROCESS = new Set(['command', 'builtin', 'time']);

/**
 * Tira os prefixos que só embrulham o comando real (sudo, env, xargs, nohup...). `meta.wrapped` diz se algum deles
 * roda o comando em outro processo: aí `cd` e `export` não mudam o shell.
 */
function unwrap(words, assigns, meta) {
    for (;;) {
        // Palavra reservada e function NOME na frente, também depois de time (time { rm -rf app; }).
        while (words.length && RESERVED.has(words[0].value)) words.shift();
        if (words[0]?.value === 'function' && words.length > 1) {
            words.splice(0, 2);
            continue;
        }
        takeAssignments(words, assigns);
        if (!words.length) return words;
        const name = programName(words[0].value);
        if (Object.hasOwn(INJECTORS, name) && unwrapRunner(words, INJECTORS[name], meta)) {
            meta.choices.push(...(INJECTORS[name].chooseEnv ?? []).map((v) => assigns[v]).filter(Boolean));
            meta.injected = name === 'vc' || name === 'vercel' ? 'vercel env run' : name;
            meta.runner = name;
            meta.wrapped = true;
            continue;
        }
        if (Object.hasOwn(REMOTE_RUNNERS, name) && unwrapRunner(words, REMOTE_RUNNERS[name], meta)) {
            meta.remoteVia = name === 'heroku' ? 'heroku run' : name === 'railway' ? 'railway ssh' : `${name} ssh console`;
            meta.runner = name;
            meta.wrapped = true;
            continue;
        }
        // npm exec, pnpm exec, pnpm dlx, yarn exec, yarn dlx, bun x: duas palavras que só embrulham o binário.
        if (PACKAGE_MANAGERS.has(name) && ['exec', 'x', 'dlx'].includes(words[1]?.value)) {
            words.splice(0, 2);
            while (words.length && words[0].value.startsWith('-') && words[0].value !== '--') {
                if (['-p', '--package'].includes(words.shift().value)) words.shift();
            }
            if (words[0]?.value === '--') words.shift();
            meta.wrapped = true;
            continue;
        }
        // python -m uv run ..., python -I -m dotenv run ...: o módulo é a ferramenta.
        const module = PYTHON.test(name) ? pythonModule(words.map((w) => w.value)) : null;
        if (module && RUNNER_MODULES.has(module.module)) {
            words.splice(0, module.at, { type: 'word', value: module.module });
            continue;
        }
        if (name === 'dotenv' || name === 'dotenv-cli') {
            unwrapDotenv(words, assigns, meta);
            meta.wrapped = true;
            continue;
        }
        if (Object.hasOwn(PYTHON_RUNNERS, name) || name === 'uvx') {
            if (!unwrapPythonRunner(words, name, meta)) return words;
            meta.wrapped = true;
            continue;
        }
        if (!Object.hasOwn(WRAPPER_ARG_FLAGS, name)) return words;
        if (name === 'command' && words[1] && /^-[vV]$/.test(words[1].value)) return [];
        if (name === 'xargs') meta.targetsFromInput = true;
        // `env` sem comando imprime o ambiente.
        if (name === 'env') meta.envWrapped = true;
        if (!SAME_PROCESS.has(name)) meta.wrapped = true;
        words.shift();
        const takes = WRAPPER_ARG_FLAGS[name];
        // `-` sozinho é opção do env (o -i), e para os outros não é opção.
        while (words.length && words[0].value.startsWith('-') && (words[0].value !== '-' || name === 'env')) {
            const word = words.shift().value;
            if (word === '--') break;
            // Opções curtas juntas (-iS, -uroot, -C..): como o getopt lê, a que recebe valor leva o resto da palavra ou
            // a palavra seguinte.
            const options = [];
            if (!word.startsWith('--')) {
                for (let i = 1; i < word.length; i++) {
                    const flag = `-${word[i]}`;
                    if (!takes.includes(flag)) {
                        options.push([flag, undefined]);
                        continue;
                    }
                    options.push([flag, i + 1 < word.length ? word.slice(i + 1) : words.shift()?.value]);
                    break;
                }
            } else {
                const [flag, inline] = word.split(/=(.*)/s);
                options.push([flag, inline ?? (takes.includes(flag) ? words.shift()?.value : undefined)]);
            }
            let text = null;
            for (const [flag, value] of options) {
                // env -C/--chdir e sudo -D/--chdir: o comando roda na pasta dada (no sudo, -C é --close-from).
                const chdir = flag === '--chdir' || (name === 'env' && flag === '-C') || (name === 'sudo' && flag === '-D');
                if ((name === 'env' || name === 'sudo') && chdir && value) meta.dir = value;
                // env -S 'comando': a string vira o comando.
                if (name === 'env' && ['-S', '--split-string'].includes(flag) && value) text = value;
            }
            if (text !== null) {
                words.unshift(...lex(text).tokens.filter((t) => t.type === 'word'));
                break;
            }
        }
        if (name === 'timeout' && words.length) words.shift();
        if (name === 'npx' && words[0]?.value === '--') words.shift();
    }
}

/**
 * Comando que imprime o ambiente inteiro: `printenv`, `env` sem comando, `export` e `declare -x` sem atribuição, `set`.
 */
function printsEnvironment(cmd, args, meta) {
    if (cmd === 'printenv' || (meta.envWrapped && !cmd)) return true;
    if (cmd === 'set') return !args.length;
    if (cmd === 'export' || cmd === 'declare' || cmd === 'typeset') return !args.some((a) => a.includes('=')) && (!args.length || args.some((a) => /^-[a-z]*[px]/.test(a)));
    return false;
}

const PACKAGE_MANAGERS = new Set(['npm', 'pnpm', 'yarn', 'bun']);
const NPM_LIFECYCLE = { test: 'test', t: 'test', tst: 'test', start: 'start', stop: 'stop', restart: 'restart' };
// Subcomandos próprios de cada gerenciador; o que não está aqui, no pnpm/yarn/bun, é nome de script ou de binário.
const PM_BUILTINS = {
    pnpm: new Set(['install', 'i', 'add', 'remove', 'rm', 'uninstall', 'un', 'update', 'up', 'upgrade', 'link', 'ln', 'unlink', 'import', 'rebuild', 'rb', 'prune', 'fetch', 'patch', 'patch-commit', 'audit', 'list', 'ls', 'll', 'outdated', 'why', 'licenses', 'publish', 'pack', 'root', 'bin', 'store', 'config', 'c', 'get', 'set', 'env', 'setup', 'init', 'create', 'deploy', 'doctor', 'server', 'help', 'dedupe', 'install-test', 'it', 'self-update', 'cat-file', 'cat-index', 'find-hash']),
    yarn: new Set(['install', 'add', 'remove', 'up', 'upgrade', 'upgrade-interactive', 'dedupe', 'init', 'info', 'why', 'workspace', 'workspaces', 'config', 'cache', 'set', 'plugin', 'pack', 'publish', 'npm', 'constraints', 'version', 'link', 'unlink', 'login', 'logout', 'global', 'bin', 'import', 'list', 'outdated', 'audit', 'check', 'autoclean', 'licenses', 'owner', 'tag', 'team', 'policies', 'patch', 'patch-commit', 'rebuild', 'search', 'stage', 'unplug', 'explain', 'help']),
    bun: new Set(['install', 'i', 'add', 'a', 'remove', 'rm', 'update', 'upgrade', 'outdated', 'link', 'unlink', 'pm', 'init', 'create', 'c', 'build', 'test', 'publish', 'patch', 'audit', 'info', 'repl', 'help']),
};
const PM_DIR_FLAGS = new Set(['--prefix', '-C', '--dir', '--cwd']);

/**
 * `npm run x`, `pnpm x`, `yarn x`, `bun run x`: qual script do package.json o comando roda, e de que pasta.
 * No pnpm e no yarn, nome que não é script é binário (`pnpm prisma ...`): volta em `exec`.
 */
function packageScript(values) {
    const pm = values.length ? programName(values[0]) : '';
    if (!PACKAGE_MANAGERS.has(pm)) return null;
    let k = 1;
    let dir = null;
    const skipFlags = () => {
        while (k < values.length && values[k].startsWith('-') && values[k] !== '--') {
            const inline = values[k].match(/^--(?:prefix|dir|cwd)=(.+)$/);
            if (inline) dir = inline[1];
            else if (PM_DIR_FLAGS.has(values[k])) dir = values[++k] ?? null;
            k++;
        }
    };
    skipFlags();
    const sub = values[k];
    if (!sub) return null;
    let implicit = false;
    if (['run', 'run-script', 'rum', 'urn'].includes(sub)) {
        k++;
        skipFlags();
    } else if (pm === 'npm') {
        if (!Object.hasOwn(NPM_LIFECYCLE, sub)) return null;
    } else if (PM_BUILTINS[pm].has(sub)) return null;
    else implicit = true;
    const name = pm === 'npm' && Object.hasOwn(NPM_LIFECYCLE, sub) ? NPM_LIFECYCLE[sub] : values[k];
    if (!name) return null;
    let extra = values.slice(k + 1);
    if (extra[0] === '--') extra = extra.slice(1);
    return { name, extra, dir, exec: implicit && pm !== 'bun' ? values.slice(k) : null };
}

const SCRIPT_RULES = new Set(['banco-destrutivo', 'producao']);

/**
 * Lê o script no package.json e passa o corpo (com pre e post) pela trava de banco e pela de produção (que só
 * pergunta). As outras não olham dentro de script, porque script é código do humano: carregar o .env no processo e
 * limpar pasta é o normal ali. Pela mesma razão, a pista de APP_ENV e --env de produção fica de fora (`webpack --env
 * production` é build).
 */
function analyzeScript(script, state, envHere, findings, ctx, depth) {
    if (state.remote || state.cwd === null) return;
    const pkg = findPackageScripts(script.dir ? expandPath(script.dir, state.cwd, ctx.home) : state.cwd);
    const body = pkg?.scripts?.[script.name];
    if (typeof body !== 'string') {
        if (script.exec) analyzeWords(script.exec, { ...state, env: { ...envHere } }, findings, ctx, depth);
        return;
    }
    const quote = (v) => (/[\s'"$`;&|<>()\\]/.test(v) ? `'${v.replace(/'/g, `'\\''`)}'` : v);
    const runs = [
        [`pre${script.name}`, pkg.scripts[`pre${script.name}`]],
        [script.name, [body, ...script.extra.map(quote)].join(' ')],
        [`post${script.name}`, pkg.scripts[`post${script.name}`]],
    ];
    for (const [name, text] of runs) {
        if (typeof text !== 'string') continue;
        const found = [];
        analyze(text, { ...state, cwd: pkg.dir, env: { ...envHere } }, found, ctx, depth + 1);
        for (const f of found) {
            if (SCRIPT_RULES.has(f.rule) && !f.envHint) findings.push({ ...f, reason: `o script "${name}" do package.json roda o que a trava pega: ${f.reason}` });
        }
    }
}

/**
 * Pasta de destino de `cd` e `pushd`, ou null quando a trava não tem como saber (variável, `cd -`, `~outro`, pilha,
 * e `cd velho novo`, que no zsh troca um trecho do caminho atual).
 */
function changeDir(args, state, ctx) {
    const operands = args.filter((a) => !/^-[LPe@]+$/.test(a));
    if (operands[0] === '--') operands.shift();
    if (operands.length > 1) return null;
    const target = operands[0];
    if (!target || target === '~') return ctx.home;
    if (/[$`*?]/.test(target) || target === '-' || /^~[^/]/.test(target) || /^[+-]\d+$/.test(target)) return null;
    // Relativo a uma pasta que a trava não sabe continua desconhecido.
    if (state.cwd === null && !/^[/~]/.test(target)) return null;
    return expandPath(target, state.cwd ?? ctx.projectDir, ctx.home);
}

function analyzeSegment(seg, state, findings, ctx, depth) {
    const assigns = {};
    const meta = {
        targetsFromInput: false,
        envFiles: [...(state.envFiles ?? [])],
        hidden: [],
        runnerOptions: [],
        choices: [],
        runner: state.loose ? 'contêiner' : null,
        dir: null,
        injected: null,
        remoteVia: null,
        envWrapped: false,
        wrapped: false,
        alternatives: [],
        dotenvFiles: null,
    };
    const words = unwrap([...seg.words], assigns, meta);
    const values = words.map((w) => w.value);
    const cmd = values.length ? programName(values[0]) : '';
    const args = values.slice(1);
    // O que o dotenv carregou vale também para o shell de dentro (dotenv -- sh -c printenv).
    const loaded = meta.dotenvFiles ?? state.dotenvFiles;
    if (loaded && printsEnvironment(cmd, args, meta)) {
        meta.hidden.push(...loaded.map((value) => ({ type: 'word', value })));
        meta.runner ??= 'dotenv';
    }
    const envHere = { ...state.env, ...assigns };
    // `uv run --directory x`, `poetry -C x run` e `env -C x` rodam o comando em outra pasta; `doppler run` e afins,
    // com um ambiente que a trava não vê; `heroku run` e `fly ssh console`, no servidor.
    const moved = meta.dir !== null;
    const here = moved || meta.injected || meta.remoteVia || meta.dotenvFiles ? fork(state) : state;
    if (moved) here.cwd = /[$`*?]/.test(meta.dir) ? null : expandPath(meta.dir, state.cwd ?? ctx.projectDir, ctx.home);
    if (meta.injected) here.injected = meta.injected;
    if (meta.dotenvFiles) here.dotenvFiles = meta.dotenvFiles;
    if (meta.remoteVia) Object.assign(here, { remote: true, cwd: null });
    // O que vem depois de um `--` que o desembrulho não usou também pode ser o comando (opção desconhecida com valor).
    for (const alternative of meta.alternatives) analyzeWords(alternative.map((w) => w.value), fork(here), findings, ctx, depth);

    checkProductionHints(values, assigns, seg, ctx, findings);
    if (!moved) {
        checkSecretAccess(cmd, words, seg.redirs, state, ctx, findings);
        checkGuardWrites(cmd, words, seg.redirs, state, ctx, findings);
    } else {
        // As palavras valem na pasta nova; o redirecionamento, o shell faz na pasta de antes.
        checkSecretAccess(cmd, words, [], here, ctx, findings);
        checkSecretAccess(cmd, words.slice(0, 1), seg.redirs, state, ctx, findings);
        checkGuardWrites(cmd, words, [], here, ctx, findings);
        checkGuardWrites(cmd, words.slice(0, 1), seg.redirs, state, ctx, findings);
    }
    // O que o desembrulho tirou do comando (uv --directory, --with-requirements, --index-url, o arquivo que o dotenv
    // lista) continua passando pelas travas de segredo, de host de produção e de arquivo de trava, lido na pasta de
    // partida e na do --directory (o uv resolve na segunda).
    if (meta.hidden.length) {
        const hidden = [{ type: 'word', value: meta.runner }, ...meta.hidden];
        // Opção do uv e afins pode ser endereço de rede (--index-url): host com cara de produção pergunta. A do
        // doppler, do op e afins não é (op --account prod.1password.com); o ambiente escolhido nelas vai por meta.choices.
        const network = new Set(meta.runnerOptions);
        checkProductionHints(hidden.filter((w) => !network.has(w)).map((w) => w.value), {}, { redirs: [] }, ctx, findings);
        if (network.size) checkProductionHints([meta.runner, ...meta.runnerOptions.map((w) => w.value)], {}, { redirs: [] }, ctx, findings, true);
        for (const where of here === state ? [state] : [state, here]) {
            checkSecretAccess(meta.runner, hidden, [], where, ctx, findings);
            checkGuardWrites(meta.runner, hidden, [], where, ctx, findings);
        }
    }
    if (meta.remoteVia) findings.push(finding('ask', 'producao', `\`${meta.remoteVia}\` roda o comando no servidor da plataforma, que pode ser produção`));
    const production = meta.injected && meta.choices.find((c) => PROD_NAME.test(c));
    if (production) findings.push(finding('ask', 'producao', `\`${meta.injected}\` carrega o ambiente de produção (${production})`));
    if (here.injected && printsEnvironment(cmd, args, meta)) {
        findings.push(finding('deny', 'segredo', `imprime as variáveis que o ${here.injected} injeta, e com elas os segredos do cofre`));
    }

    for (const redir of seg.redirs) {
        if (redir.body && (SHELLS.has(cmd) || cmd === 'eval')) analyze(redir.body, cmd === 'eval' ? { ...here } : fork(here), findings, ctx, depth + 1);
    }
    if (!cmd) return;

    // Atrás de sudo, env ou doppler run, cd e export rodam em outro processo e não mudam o shell.
    if (cmd === 'cd' || cmd === 'pushd') {
        if (meta.wrapped) return;
        const before = { cwd: state.cwd, env: { ...state.env } };
        state.cwd = changeDir(args, state, ctx);
        // cd que pode não acontecer, ou que falha (pasta que não existe nem foi criada antes no comando), abre o mundo
        // sem ele (analyze).
        const exists = state.cwd === null || isDirectory(state.cwd) || state.created?.has(state.cwd);
        if (seg.uncertain || !exists) (state.pending ??= []).push(before);
        return;
    }
    // mkdir antes do cd: a pasta passa a existir.
    if (cmd === 'mkdir' && !meta.wrapped && state.cwd !== null) {
        state.created = new Set([...(state.created ?? []), ...args.filter((a) => !a.startsWith('-') && !/[$`*?]/.test(a)).map((a) => expandPath(a, state.cwd, ctx.home))]);
    }
    if (cmd === 'popd') {
        if (!meta.wrapped) state.cwd = null;
        return;
    }
    if (cmd === 'export') {
        if (meta.wrapped) return;
        const before = { cwd: state.cwd, env: { ...state.env } };
        const pairs = args.map((a) => [a, a.indexOf('=')]).filter(([, eq]) => eq > 0);
        for (const [a, eq] of pairs) state.env[a.slice(0, eq)] = a.slice(eq + 1);
        if (pairs.length && seg.uncertain) (state.pending ??= []).push(before);
        return;
    }
    if (SHELLS.has(cmd)) {
        const flagIndex = args.findIndex((a) => /^-[a-zA-Z]*c[a-zA-Z]*$/.test(a));
        if (flagIndex !== -1 && args[flagIndex + 1] !== undefined) analyze(args[flagIndex + 1], fork(here), findings, ctx, depth + 1);
        return;
    }
    if (cmd === 'eval') {
        // O eval roda no shell atual: a cópia rasa deixa o export dele chegar aqui.
        analyze(args.join(' '), { ...here }, findings, ctx, depth + 1);
        return;
    }
    if (REMOTE_SHELLS.has(cmd)) {
        const { host, rest } = parseSsh(args);
        findings.push(finding('ask', 'producao', `abre shell em servidor remoto (${host ?? 'host desconhecido'}); servidor pode ser produção`));
        if (rest.length) analyze(rest.join(' '), { ...fork(state), remote: true, cwd: null }, findings, ctx, depth + 1);
        return;
    }
    if (REMOTE_COPY.has(cmd)) {
        const remote = args.find((a) => /^([^/\s]+@)?[A-Za-z0-9.-]+:/.test(a) && !/^[A-Za-z]:[\\/]/.test(a));
        if (remote || cmd === 'sftp') findings.push(finding('ask', 'producao', `copia de ou para servidor remoto (${remote ?? args.at(-1) ?? ''}); servidor pode ser produção`));
    }
    if (['docker', 'podman', 'docker-compose', 'kubectl', 'oc'].includes(cmd)) {
        // O ambiente do docker é o herdado pelo hook (DOCKER_HOST exportado no terminal), o do shell e o do comando.
        const dockerEnv = { ...ctx.processEnv, ...envHere };
        const inner = containerCommand(cmd, args, dockerEnv);
        if (inner) {
            const remote = here.remote || inner.remote;
            // Serviço do docker compose: a pasta de dentro e o ambiente (environment, env_file) saem do compose.yaml.
            // O ambiente de quem roda o compose (atribuição na frente incluída) só serve para o ${VAR} e o COMPOSE_FILE:
            // o contêiner não o herda, e o que vale lá dentro é o do compose.yaml.
            const service = inner.compose && !remote && here.cwd !== null
                ? composeService(here.cwd, inner.compose, { env: envHere, processEnv: ctx.processEnv ?? {}, projectDir: ctx.projectDir, home: ctx.home })
                : null;
            const innerState = {
                ...here,
                remote,
                env: service ? { ...service.env, ...inner.env } : { ...here.env, ...inner.env },
                envFiles: [...inner.envFiles, ...(service?.envFiles ?? [])],
                containerDirs: service?.dirs ?? [],
                container: Boolean(service),
                loose: true,
            };
            analyzeWords(inner.words, innerState, findings, ctx, depth);
        }
        return;
    }
    if (cmd === 'find') {
        const at = args.findIndex((a) => ['-exec', '-execdir', '-ok', '-okdir'].includes(a));
        if (at !== -1) {
            const end = args.findIndex((a, k) => k > at && (a === ';' || a === '+'));
            const exec = args.slice(at + 1, end === -1 ? undefined : end);
            checkFindSecret(args.slice(0, at), exec, findings);
            analyzeWords(exec, fork(here), findings, ctx, depth);
        }
        return;
    }

    const script = packageScript(values);
    if (script) analyzeScript(script, here, envHere, findings, ctx, depth);

    checkRm(cmd, words.slice(1), here, ctx, findings, meta);
    checkGit(cmd, args, findings);
    checkArtisan(values, envHere, here, ctx, findings, meta);
    checkPrisma(values, envHere, here, ctx, findings, meta);
    checkAlembic(values, envHere, here, ctx, findings, meta);
    checkVercel(values, here, ctx, findings);
    checkSecretCli(values, findings);
    checkRecursiveGrep(cmd, args, here, ctx, findings);
}

function analyzeWords(values, state, findings, ctx, depth) {
    const quoted = values.map((v) => (/[\s'"$`;&|<>()\\]/.test(v) ? `'${v.replace(/'/g, `'\\''`)}'` : v)).join(' ');
    analyze(quoted, state, findings, ctx, depth + 1);
}

function parseSsh(args) {
    const withValue = new Set(['-p', '-i', '-l', '-o', '-F', '-J', '-L', '-R', '-D', '-b', '-c', '-e', '-m', '-O', '-Q', '-S', '-W', '-w', '-E', '-B']);
    let k = 0;
    while (k < args.length && args[k].startsWith('-')) {
        if (withValue.has(args[k])) k++;
        k++;
    }
    return { host: args[k], rest: args.slice(k + 1) };
}

// Opções do docker e do docker compose antes do subcomando, e as do exec/run, que recebem valor.
const DOCKER_GLOBAL_VALUE = new Set(['--context', '-c', '-H', '--host', '--config', '-l', '--log-level', '--tlscacert', '--tlscert', '--tlskey']);
const COMPOSE_VALUE = new Set(['-f', '--file', '-p', '--project-name', '--env-file', '--profile', '--project-directory', '--ansi', '--progress', '--parallel']);
// Contextos e endereços do Docker que ficam nesta máquina (Docker Desktop, Colima, OrbStack, Rancher).
const LOCAL_DOCKER_CONTEXTS = new Set(['default', 'desktop-linux', 'colima', 'orbstack', 'rancher-desktop']);
// Contexto local (Docker Desktop, Colima e os perfis dele, OrbStack, Rancher Desktop), socket da máquina ou tcp em loopback.
const isRemoteDocker = (value) => {
    if (!value || LOCAL_DOCKER_CONTEXTS.has(value) || value.startsWith('colima-') || /^(unix|npipe):\/\//.test(value)) return false;
    const tcp = value.match(/^tcp:\/\/(\[[^\]]+\]|[^:/]+)(:\d+)?\/?$/);
    return !(tcp && LOOPBACK_HOSTS.has(tcp[1].replace(/^\[|\]$/g, '').toLowerCase()));
};
const CONTAINER_VALUE = new Set([
    '-u', '--user', '-w', '--workdir', '-e', '--env', '--env-file', '-c', '--container', '-n', '--namespace', '--index',
    '--name', '--entrypoint', '-v', '--volume', '-p', '--publish', '-l', '--label', '--network', '--platform',
    '--pull', '--cap-add', '--cap-drop', '--env-from-file', '--label-file', '--add-host', '--device', '--mount', '--ulimit',
    '--dns', '--network-alias', '--link', '--log-driver', '-m', '--memory', '--cpus', '--gpus', '--stop-signal', '--hostname',
    '--restart', '--tmpfs', '--security-opt', '--ipc', '--pid',
]);

/**
 * O comando que roda dentro do contêiner ou do pod. kubectl e oc (exec, run, debug), docker com contexto ou host
 * remoto (--context, -H, DOCKER_HOST, DOCKER_CONTEXT) levam o comando para outra máquina; -e e --env-file do
 * exec/run entram no ambiente dele, e `-e VAR` sem valor leva o valor do shell.
 */
function containerCommand(cmd, args, env) {
    if (cmd === 'kubectl' || cmd === 'oc') {
        const at = args.findIndex((a) => ['exec', 'run', 'debug'].includes(a));
        const dash = args.indexOf('--');
        return at !== -1 && dash > at ? { words: args.slice(dash + 1), remote: true, env: {}, envFiles: [] } : null;
    }
    let remote = isRemoteDocker(env.DOCKER_HOST) || isRemoteDocker(env.DOCKER_CONTEXT);
    let k = 0;
    while (k < args.length && args[k].startsWith('-')) {
        // -cNOME e -Hssh://host: o docker lê o valor colado.
        const [flag, inline] = /^-[cH]./.test(args[k]) ? [args[k].slice(0, 2), args[k].slice(2)] : args[k].split(/=(.*)/s);
        const value = inline ?? args[k + 1];
        if (['--context', '-c', '-H', '--host'].includes(flag) && isRemoteDocker(value)) remote = true;
        k += DOCKER_GLOBAL_VALUE.has(flag) && inline === undefined ? 2 : 1;
    }
    if (args[k] === 'compose') k++;
    let compose = null;
    if (cmd === 'docker-compose' || args[k - 1] === 'compose') {
        compose = { files: [], projectDirectory: null, envFiles: [] };
        for (; k < args.length && args[k].startsWith('-'); k++) {
            // -f com o arquivo colado: -fdocker-compose.prod.yml.
            const [flag, inline] = /^-f./.test(args[k]) ? ['-f', args[k].slice(2)] : args[k].split(/=(.*)/s);
            const value = inline ?? (COMPOSE_VALUE.has(flag) ? args[++k] : undefined);
            if ((flag === '-f' || flag === '--file') && value) compose.files.push(value);
            if (flag === '--project-directory' && value) compose.projectDirectory = value;
            if (flag === '--env-file' && value) compose.envFiles.push(value);
        }
    }
    if (!['exec', 'run'].includes(args[k])) return null;
    k++;
    const innerEnv = {};
    const envFiles = [];
    let workdir = null;
    for (; k < args.length && args[k].startsWith('-'); k++) {
        const attached = args[k].match(/^-e(.+)$/);
        const [flag, inline] = attached ? ['-e', attached[1]] : args[k].split(/=(.*)/s);
        const value = inline ?? (CONTAINER_VALUE.has(flag) ? args[++k] : undefined);
        if ((flag === '-e' || flag === '--env') && value) {
            const eq = value.indexOf('=');
            if (eq > 0) innerEnv[value.slice(0, eq)] = value.slice(eq + 1);
            // -e VAR sem valor: o docker lê o valor do próprio ambiente, o de quem roda o comando.
            else if (env[value] !== undefined) innerEnv[value] = env[value];
        }
        if ((flag === '--env-file' || flag === '--env-from-file') && value) envFiles.push(value);
        if ((flag === '-w' || flag === '--workdir') && value) workdir = value;
    }
    // No docker compose a palavra depois das opções é o serviço; no docker, o contêiner.
    const target = args[k];
    k++;
    return { words: args.slice(k), remote, env: innerEnv, envFiles, compose: compose && target ? { ...compose, service: target, workdir } : null };
}
