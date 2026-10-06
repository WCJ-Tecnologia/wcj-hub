/**
 * Uma função por trava. Cada uma olha um comando simples, já separado e sem os prefixos
 * que só o embrulham, e acrescenta o que encontrou em `findings`.
 */
import { existsSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join, relative, resolve, sep } from 'node:path';
import {
    alembicDatabase,
    databaseHost,
    expandGlob,
    expandPath,
    globToRegExp,
    interpolate,
    isAlembicDir,
    isGuardFile,
    isInside,
    isPrismaDir,
    isSecretPath,
    prismaDatabase,
    readDotEnv,
    readEnvFiles,
    SECRET_SAMPLES,
    treeHasSecret,
} from './caminhos.mjs';

const LOCAL_ENVS = new Set(['local', 'testing', 'test', 'e2e', 'dev', 'development']);
const DESTRUCTIVE_ARTISAN = new Set(['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe']);
const REGENERABLE_DIRS = new Set([
    'node_modules',
    'vendor',
    'dist',
    'build',
    'coverage',
    'test-results',
    'playwright-report',
    '.phpunit.cache',
    '.next',
    '.nuxt',
    '.turbo',
    '.vite',
    '.cache',
    '__pycache__',
]);
const REGENERABLE_PATHS = ['public/build', 'storage/framework/cache', 'storage/framework/views', 'storage/framework/sessions', 'storage/logs'];
const NETWORK_COMMANDS = new Set([
    'ssh', 'mosh', 'scp', 'sftp', 'rsync', 'curl', 'wget', 'http', 'https', 'ping', 'nc', 'telnet', 'ftp',
    'mysql', 'mysqldump', 'psql', 'pg_dump', 'pg_restore', 'redis-cli', 'mongosh', 'mongo', 'dig', 'nslookup', 'host',
]);
const PROD_LABEL = /(^|[._-])(prod|production|producao|prd)([._-]|$)/i;
// Comandos que só olham metadado do arquivo (existe? tamanho? permissão?), sem trazer conteúdo para o modelo.
const METADATA_COMMANDS = new Set(['ls', 'stat', 'test', '[', '[[', 'touch', 'chmod', 'chown', 'realpath', 'readlink', 'basename', 'dirname', 'mkdir', 'file', 'du', 'wc']);
// Em ssh, scp e sftp, -i recebe a chave de identidade. No rsync -i é outra opção e fica de fora.
const IDENTITY_FILE_COMMANDS = new Set(['ssh', 'scp', 'sftp']);
const GIT_METADATA_SUBCOMMANDS =new Set(['check-ignore', 'status', 'ls-files']);
const READ_ONLY_COMMANDS = new Set(['cat', 'less', 'more', 'head', 'tail', 'grep', 'rg', 'jq', 'diff', 'node', 'git', 'eslint']);
const NAVIGATION_COMMANDS = new Set(['cd', 'pushd', 'find', 'tree', 'echo', 'printf']);

export function finding(decision, rule, reason) {
    return { decision, rule, reason };
}

// Seção, banco, perfil ou ambiente de produção escolhido pelo nome (alembic -n producao, doppler run -c prd).
export const PROD_NAME = /(^|[^a-z0-9])(prod|production|producao|prd)([^a-z0-9]|$)/i;

/**
 * Pastas de onde a ferramenta de banco lê a configuração. Dentro de serviço do docker compose, todas as pastas do
 * compose.yaml em que ela acha a configuração (volume do working_dir, contexto do build, volumes): sem saber qual o
 * contêiner usa, todas contam. Fora dele, a do comando.
 */
function workDirs(state, isToolDir) {
    const found = (state.containerDirs ?? []).filter((dir) => isToolDir(dir));
    return found.length ? found : [state.cwd];
}

// A ferramenta que injeta o ambiente (doppler run, op run...) esconde a URL do banco: a trava não tem como confirmar que é local.
const injectedWhere = (state) => `o ambiente vem do \`${state.injected}\`, e a trava não vê o que ele injeta`;



export function checkRm(cmd, argWords, state, ctx, findings, meta) {
    if (cmd !== 'rm' && cmd !== 'rimraf') return;
    let recursive = cmd === 'rimraf';
    const targets = [];
    let endOfFlags = false;
    for (const w of argWords) {
        if (!endOfFlags && w.value === '--') {
            endOfFlags = true;
            continue;
        }
        if (!endOfFlags && w.value.startsWith('-') && w.value !== '-') {
            if (w.value === '--recursive' || (!w.value.startsWith('--') && /[rR]/.test(w.value))) recursive = true;
            continue;
        }
        targets.push(w);
    }
    if (!recursive) return;
    if (!targets.length) {
        if (meta.targetsFromInput) findings.push(finding('deny', 'rm-recursivo', 'apagar recursivamente o que chega pela entrada (xargs) não tem volta e a trava não vê os alvos'));
        return;
    }
    const blocked = targets.filter((t) => !isRegenerable(t, state, ctx)).map((t) => t.value);
    if (blocked.length) {
        findings.push(
            finding('deny', 'rm-recursivo', `apagar recursivamente ${blocked.join(' ')} não tem volta. Só pasta regenerável (node_modules, vendor, build, cache, temporário) passa sem humano`),
        );
    }
}

/**
 * Primeiro par de chaves com vírgula e sem chave dentro (`{a,b}`): [abre, fecha] ou null. Varre uma vez só:
 * expressão regular com dois `[^{}]*` volta atrás em quadrado, e `{,,,…` hostil estourava o tempo do hook.
 */
function braceGroup(value) {
    let open = -1;
    let comma = false;
    for (let i = 0; i < value.length; i++) {
        const c = value[i];
        if (c === '{') [open, comma] = [i, false];
        else if (c === ',' && open !== -1) comma = true;
        else if (c === '}' && open !== -1) {
            if (comma) return [open, i];
            open = -1;
        }
    }
    return null;
}

/**
 * Expansão de chaves do shell (`{a,b}`, uma de cada vez, até 64 alternativas): `node_modules/{x,../app}` apaga `app`.
 */
function expandBraces(value) {
    const out = [];
    const pending = [value];
    while (pending.length && out.length + pending.length <= 64) {
        const current = pending.shift();
        const group = braceGroup(current);
        if (!group) {
            out.push(current);
            continue;
        }
        // O limite do split segura `{,,,…}` com milhares de opções: acima de 64, o laço para e devolve null.
        for (const option of current.slice(group[0] + 1, group[1]).split(',', 65)) pending.push(current.slice(0, group[0]) + option + current.slice(group[1] + 1));
    }
    return pending.length ? null : out;
}

function isRegenerable(word, state, ctx) {
    if (word.dynamic || state.remote || state.cwd === null) return false;
    if (word.brace && braceGroup(word.value)) {
        const options = expandBraces(word.value);
        return options !== null && options.every((value) => isRegenerable({ ...word, value, glob: /[*?[]/.test(value) }, state, ctx));
    }
    if (word.glob) {
        const expanded = expandGlob(word.value, state.cwd, ctx.home);
        if (expanded === null) return false;
        // Glob que não casa nada (pasta vazia ou que a trava não enxerga) vale pela pasta onde ele seria expandido.
        if (!expanded.length) return isRegenerablePath(dirname(expandPath(word.value, state.cwd, ctx.home)), ctx);
        return expanded.every((path) => isRegenerablePath(path, ctx));
    }
    return isRegenerablePath(expandPath(word.value, state.cwd, ctx.home), ctx);
}

function isRegenerablePath(abs, ctx) {
    if (abs.split(sep).some((part) => REGENERABLE_DIRS.has(part))) return true;
    if (REGENERABLE_PATHS.some((p) => abs === join(ctx.projectDir, p) || isInside(abs, join(ctx.projectDir, p)))) return true;
    // Projeto clonado dentro de /tmp (CI, worktree) continua protegido: a exceção vale só para o que está fora dele.
    if ([ctx.projectDir, ctx.home].some((guarded) => abs === guarded || isInside(abs, guarded) || isInside(guarded, abs))) return false;
    const temps = [...new Set([tmpdir(), '/tmp', '/private/tmp', '/var/tmp'].map((t) => resolve(t)))];
    return temps.some((t) => isInside(abs, t));
}

export function checkGit(cmd, args, findings) {
    if (cmd !== 'git') return;
    let k = 0;
    while (k < args.length && args[k].startsWith('-')) k += ['-C', '-c', '--git-dir', '--work-tree', '--namespace'].includes(args[k]) ? 2 : 1;
    const sub = args[k];
    const rest = args.slice(k + 1);
    const shortHas = (a, letter) => /^-[^-]/.test(a) && a.includes(letter);

    if (sub === 'push') {
        const force = rest.some((a) => a === '--force' || a === '--mirror' || shortHas(a, 'f') || (/^\+/.test(a) && !a.startsWith('-')));
        const lease = rest.some((a) => a.startsWith('--force-with-lease') || a === '--force-if-includes');
        if (force) findings.push(finding('deny', 'git-force', '`git push --force` reescreve o histórico do remoto e apaga trabalho de outra pessoa'));
        else if (lease) findings.push(finding('ask', 'git-force', '`--force-with-lease` ainda reescreve histórico publicado; só com um humano'));
    }
    if (sub === 'reset' && rest.includes('--hard')) {
        findings.push(finding('deny', 'git-reset-hard', '`git reset --hard` apaga o que não está em commit, sem volta. Para guardar, `git stash`'));
    }
    if (sub === 'clean' && rest.some((a) => a === '--force' || shortHas(a, 'f'))) {
        findings.push(finding('deny', 'git-clean', '`git clean -f` apaga arquivo não versionado, sem volta. Liste antes com `git clean -n`'));
    }
}

function artisanArgs(values) {
    if (!values.length) return null;
    const first = commandName(values[0]);
    if (first === 'artisan') return values.slice(1);
    if (first === 'sail') {
        if (values[1] === 'artisan' || values[1] === 'art') return values.slice(2);
        if (values[1] === 'php' && values[2] && commandName(values[2]) === 'artisan') return values.slice(3);
        return null;
    }
    if (/^php[0-9.]*$/.test(first)) {
        let k = 1;
        while (k < values.length && values[k].startsWith('-')) k += ['-d', '-c'].includes(values[k]) ? 2 : 1;
        if (values[k] && commandName(values[k]) === 'artisan') return values.slice(k + 1);
    }
    return null;
}

export function checkArtisan(values, env, state, ctx, findings, meta = {}) {
    const args = artisanArgs(values);
    if (!args) return;
    const sub = args.find((a) => !a.startsWith('-'));
    if (!sub) return;
    const force = args.includes('--force');
    const isMigrate = sub === 'migrate' || sub.startsWith('migrate:');
    if (!DESTRUCTIVE_ARTISAN.has(sub) && !(isMigrate && force)) return;

    const envFlagIndex = args.findIndex((a) => a === '--env' || a.startsWith('--env='));
    const envFlag = envFlagIndex === -1 ? null : args[envFlagIndex].includes('=') ? args[envFlagIndex].split('=')[1] : args[envFlagIndex + 1];
    const what = DESTRUCTIVE_ARTISAN.has(sub) ? `\`artisan ${sub}\` apaga o banco` : `\`artisan ${sub} --force\` pula a confirmação de produção`;
    const deny = (where) => findings.push(finding('deny', 'banco-destrutivo', `${what}, e aqui roda fora do ambiente local (${where})`));
    if (state.injected && !state.remote) return deny(injectedWhere(state));
    // Variável que chega pelo env_file do compose ou pelo --env-file vale mais que o .env: o Laravel não sobrescreve.
    const fromFiles = readEnvFiles((meta.envFiles ?? []).map((file) => expandPath(file, state.cwd ?? ctx.projectDir, ctx.home)), ['APP_ENV', 'DB_HOST']);
    if (fromFiles.unreadable) {
        return findings.push(finding('deny', 'banco-destrutivo', `${what}, e a trava não consegue ler o ${fromFiles.unreadable} para confirmar que o ambiente é local`));
    }
    const vars = { ...fromFiles.vars };
    for (const key of ['APP_ENV', 'DB_HOST']) if (env[key] !== undefined) vars[key] = env[key];
    const requested = envFlag ?? vars.APP_ENV ?? null;
    const isLocal = (value) => Boolean(value) && LOCAL_ENVS.has(value.toLowerCase());
    for (const dir of state.cwd === null ? [null] : workDirs(state, (d) => existsSync(join(d, 'artisan')))) {
        // O Laravel carrega .env.<ambiente> se existir, senão o .env. Pedir --env=local não adianta
        // se o arquivo que ele vai carregar é o de produção, com o banco de produção.
        const dotEnv = state.remote || dir === null ? null : readDotEnv(dir, requested);
        if (dotEnv?.unreadable) {
            findings.push(finding('deny', 'banco-destrutivo', `${what}, e a trava não consegue ler o ${dotEnv.unreadable} para confirmar que o ambiente é local`));
            continue;
        }
        const appEnv = requested ?? dotEnv?.APP_ENV ?? null;
        const fileAgrees = !dotEnv?.APP_ENV || isLocal(dotEnv.APP_ENV);
        const dbHost = (vars.DB_HOST ?? dotEnv?.DB_HOST ?? '').toLowerCase();
        const dbIsProduction = dbHost && (ctx.config.hostsProducao.includes(dbHost) || PROD_LABEL.test(dbHost));
        if (!state.remote && isLocal(appEnv) && fileAgrees && !dbIsProduction) continue;
        deny(
            state.remote
                ? 'em servidor remoto'
                : dbIsProduction
                  ? `com DB_HOST de produção (${dbHost})`
                  : !fileAgrees
                    ? `o arquivo de ambiente carregado tem APP_ENV=${dotEnv.APP_ENV}`
                    : `com APP_ENV=${appEnv ?? 'desconhecido'}`,
        );
    }
}

// Nome do programa sem a pasta, sem a versão fixada (npx vercel@latest, uvx alembic@1.13, npx prisma@6), sem a
// extensão do Windows (alembic.exe, prisma.cmd) e sem o `=` do zsh (`=rm` vira /bin/rm), em minúscula: no APFS do
// macOS `RM`, `Alembic` e `VERCEL` acham o mesmo programa.
export function commandName(word) {
    return basename(word.replace(/\\/g, '/').replace(/^=(?=.)/, '')).replace(/(.)@[^@/]*$/, '$1').replace(/\.(exe|cmd|bat|ps1)$/i, '').toLowerCase();
}

function denyOutsideLocal(findings, what, where) {
    findings.push(finding('deny', 'banco-destrutivo', `${what}, e aqui roda fora do ambiente local (${where})`));
}

/**
 * Decide pelo banco para onde o comando aponta. Local comprovado passa; host de produção (.agent-guard.json ou
 * prod no nome), host ilegível ou URL não achada nega; host remoto sem sinal de produção pergunta.
 */
function judgeDatabase(what, targets, notFound, ctx, findings) {
    if (!targets.length) return denyOutsideLocal(findings, what, notFound);
    const listed = ctx.config.hostsProducao;
    const isProduction = (host) => host.split(',').some((h) => listed.some((l) => h === l || h.endsWith(`.${l}`)) || PROD_LABEL.test(h));
    const production = targets.find((t) => t.host && isProduction(t.host));
    if (production) return denyOutsideLocal(findings, what, `${production.name} aponta para ${production.host}, host de produção`);
    const unreadable = targets.find((t) => !t.host);
    if (unreadable) return denyOutsideLocal(findings, what, `a trava não conseguiu ler ${unreadable.why ?? `o host de ${unreadable.name}`}`);
    const remote = targets.find((t) => !t.local);
    if (remote) {
        findings.push(finding('ask', 'banco-destrutivo', `${what}, e ${remote.name} aponta para ${remote.host}, fora desta máquina; a trava não sabe se é banco de dev`));
    }
}

const PRISMA_VALUE_FLAGS = new Set(['--schema', '--config', '--name', '-n', '--url', '--file', '--script']);

/**
 * Prisma não tem APP_ENV: o que diz se o banco é local é a URL. Local comprovado passa; sinal de
 * produção ou URL que a trava não acha nega; host remoto sem sinal de produção pergunta.
 */
export function checkPrisma(values, env, state, ctx, findings, meta) {
    if (!values.length || commandName(values[0]) !== 'prisma') return;
    const args = values.slice(1);
    const positional = [];
    let schema = null;
    let url = null;
    for (let k = 0; k < args.length; k++) {
        const [flag, inline] = args[k].split(/=(.*)/s);
        if (PRISMA_VALUE_FLAGS.has(flag)) {
            const value = inline ?? args[++k] ?? null;
            if (flag === '--schema') schema = value;
            if (flag === '--url') url = value;
        } else if (!args[k].startsWith('-')) positional.push(args[k]);
    }
    const [group, action] = positional;
    const dataLoss = args.find((a) => a === '--force-reset' || a === '--accept-data-loss');
    let what;
    if (group === 'migrate' && action === 'reset') what = '`prisma migrate reset` apaga o banco';
    else if (group === 'db' && action === 'push' && dataLoss) what = `\`prisma db push ${dataLoss}\` apaga dado`;
    else if (group === 'migrate' && action === 'dev') what = '`prisma migrate dev` recria o banco quando acha divergência';
    else if (group === 'db' && action === 'push') what = '`prisma db push` muda o schema sem migração';
    else if (group === 'migrate' && action === 'deploy') what = '`prisma migrate deploy` aplica migração';
    else if (group === 'migrate' && action === 'resolve') what = '`prisma migrate resolve` marca migração como aplicada ou desfeita sem rodar, e o banco deixa de bater com o histórico';
    else if (group === 'db' && action === 'execute') what = '`prisma db execute` roda SQL direto no banco';
    else return;

    const deny = (where) => denyOutsideLocal(findings, what, where);
    if (state.remote) return deny('em servidor remoto');
    // --url vale mais que o schema e o .env. O $VAR dele o shell troca antes de o comando rodar: só vale o que já
    // estava exportado, não a atribuição na frente do comando.
    if (url !== null) {
        const value = interpolate(url, (name) => state.env?.[name] ?? ctx.processEnv?.[name]);
        return judgeDatabase(what, [{ name: '--url', ...databaseHost(value), why: /[$`]/.test(value) ? 'a URL do --url, que vem de variável que a trava não conhece' : undefined }], '', ctx, findings);
    }
    if (state.injected) return deny(injectedWhere(state));
    if (state.cwd === null) return deny('a trava não sabe em que pasta ele roda');
    // Dentro do contêiner do compose, o ambiente da máquina não chega: vale o do compose.yaml.
    const processEnv = state.container ? {} : (ctx.processEnv ?? {});
    for (const dir of workDirs(state, isPrismaDir)) {
        // No mundo em que o cd não aconteceu (alt), pasta sem schema: o Prisma falha sozinho, sem tocar em banco.
        if (state.alt && !schema && !isPrismaDir(dir)) continue;
        const db = prismaDatabase(dir, { env, processEnv, envFiles: meta.envFiles ?? [], schema });
        if (db.nodeEnv && /^(prod|production|producao)$/i.test(db.nodeEnv)) deny(`NODE_ENV=${db.nodeEnv}`);
        else judgeDatabase(what, db.targets, 'a trava não achou a URL do banco (DATABASE_URL) para confirmar que é local', ctx, findings);
    }
}

// Subcomandos do Alembic. upgrade, downgrade e stamp mudam o banco; os outros leem ou só geram arquivo.
const ALEMBIC_COMMANDS = new Set(['branches', 'check', 'current', 'downgrade', 'edit', 'ensure_version', 'heads', 'history', 'init', 'list_templates', 'merge', 'revision', 'show', 'stamp', 'upgrade']);
// python3, python3.13t (sem GIL), pypy3 e py (o lançador do Windows).
export const PYTHON = /^(python|pypy|py)[0-9.]*t?$/;

/**
 * `python [opções] -m módulo ...`: as opções curtas se juntam (-Bm alembic, -malembic), e -W e -X levam valor.
 * Devolve o módulo e a posição do primeiro argumento dele; null para script, -c ou sem -m.
 */
export function pythonModule(values, start = 0) {
    for (let k = start + 1; k < values.length; k++) {
        const arg = values[k];
        if (arg === '--check-hash-based-pycs') {
            k++;
            continue;
        }
        if (arg.startsWith('--')) continue;
        if (!arg.startsWith('-') || arg === '-') return null;
        for (let j = 1; j < arg.length; j++) {
            if (arg[j] === 'c') return null;
            if (arg[j] === 'm') {
                const module = arg.slice(j + 1) || values[++k];
                return module ? { module, at: k + 1 } : null;
            }
            if (arg[j] === 'W' || arg[j] === 'X') {
                if (!arg.slice(j + 1)) k++;
                break;
            }
        }
    }
    return null;
}

/**
 * Argumentos do Alembic em `alembic ...` e `python -m alembic ...`; null se o comando não é o Alembic. Depois de
 * ferramenta desembrulhada (uv, poetry, dotenv, contêiner), a trava procura o Alembic em qualquer posição: opção
 * da ferramenta que ela não conhece pode ter ficado na frente.
 */
function alembicArgs(values, loose) {
    const last = loose ? values.length : Math.min(values.length, 1);
    for (let start = 0; start < last; start++) {
        const name = commandName(values[start]);
        if (name === 'alembic') return values.slice(start + 1);
        const found = PYTHON.test(name) ? pythonModule(values, start) : null;
        if (found && ['alembic', 'alembic.config'].includes(found.module)) return values.slice(found.at);
    }
    return null;
}

/**
 * Alembic, como o Prisma, não tem ambiente: o que diz se o banco é local é a URL que o alembic.ini ou o env.py dão.
 */
export function checkAlembic(values, env, state, ctx, findings, meta) {
    const args = alembicArgs(values, Boolean(meta.runner));
    if (!args) return;
    // Opções globais como o argparse lê: começo da longa (--conf), valor colado (-cdeploy.ini, -c=deploy.ini) e
    // curtas juntas (-qc deploy.ini).
    const long = (arg, name) => arg.startsWith('--') && arg.length > 2 && `--${name}`.startsWith(arg.split('=')[0]);
    const options = { c: null, n: null, x: [] };
    let k = 0;
    for (; k < args.length && !ALEMBIC_COMMANDS.has(args[k]); k++) {
        const arg = args[k];
        if (arg.startsWith('--')) {
            const letter = long(arg, 'config') ? 'c' : long(arg, 'name') ? 'n' : null;
            if (letter) options[letter] = arg.includes('=') ? arg.slice(arg.indexOf('=') + 1) : args[++k];
            continue;
        }
        if (!arg.startsWith('-')) continue;
        for (let j = 1; j < arg.length; j++) {
            if (!'cnx'.includes(arg[j])) continue;
            const value = arg.slice(j + 1).replace(/^=/, '') || args[++k];
            if (arg[j] === 'x') options.x.push(value ?? '');
            else options[arg[j]] = value ?? null;
            break;
        }
    }
    const sub = args[k];
    const rest = args.slice(k + 1);
    if (!['upgrade', 'downgrade', 'stamp'].includes(sub)) return;
    if (args.some((a) => a === '-h' || long(a, 'help'))) return;
    // --sql só gera o SQL, sem conectar no banco.
    if (rest.some((a) => long(a, 'sql'))) return;
    let what = '`alembic upgrade` aplica migração';
    if (sub === 'downgrade') {
        what = rest.includes('base') ? '`alembic downgrade base` desfaz todas as migrações e apaga as tabelas' : '`alembic downgrade` desfaz migração e pode apagar tabela e coluna';
    }
    if (sub === 'stamp') what = '`alembic stamp` grava a versão do banco sem rodar a migração, e o schema deixa de bater com ela';

    const deny = (where) => denyOutsideLocal(findings, what, where);
    if (state.remote) return deny('em servidor remoto');
    if (state.injected) return deny(injectedWhere(state));
    if (state.cwd === null) return deny('a trava não sabe em que pasta ele roda');
    if ([options.c, options.n, ...options.x].some((v) => v && /[$`]/.test(v))) return deny('a configuração vem de variável, e a trava não sabe qual é');
    // Seção, -x ou ambiente da ferramenta (hatch -e, pixi -e, conda -n) escolhido pelo nome de produção.
    const chosen = [options.n, meta.runnerEnv, ...options.x.map((x) => x.slice(x.indexOf('=') + 1))].find((v) => v && !/^[a-z][\w+.-]*:/i.test(v) && PROD_NAME.test(v));
    if (chosen) return deny(`o comando escolhe a configuração de produção (${chosen})`);
    for (const dir of workDirs(state, (d) => isAlembicDir(d, options.c ?? env.ALEMBIC_CONFIG))) {
        // No mundo em que o cd não aconteceu (alt), pasta sem configuração: o Alembic falha sozinho, sem tocar em banco.
        if (state.alt && !isAlembicDir(dir, options.c ?? env.ALEMBIC_CONFIG ?? ctx.processEnv?.ALEMBIC_CONFIG)) continue;
        const db = alembicDatabase(dir, {
            env,
            processEnv: ctx.processEnv ?? {},
            envFiles: meta.envFiles ?? [],
            config: options.c,
            section: options.n ?? 'alembic',
            xArgs: options.x,
            projectDir: ctx.projectDir,
            home: ctx.home,
        });
        if (db.problem) deny(db.problem);
        else if (db.environment) deny(`${db.environment.name}=${db.environment.value}`);
        else {
            const notFound = db.looked.length
                ? `a trava não achou a URL do banco (${db.looked.join(', ')}) para confirmar que é local`
                : 'a trava não achou a URL do banco: o env.py não lê a sqlalchemy.url nem cita variável de ambiente';
            judgeDatabase(what, db.targets, notFound, ctx, findings);
        }
    }
}

// Comandos e apelidos da CLI da Vercel 54.14 (a lista que o pacote registra). Palavra que não é comando é a pasta do
// deploy: `vercel apps/web`.
export const VERCEL_GLOBAL_FLAGS = ['--cwd', '-A', '--local-config', '-Q', '--global-config', '-S', '--scope', '-T', '--team', '-t', '--token'];
const VERCEL_ALIASES = {
    aliases: 'alias', ln: 'alias', cert: 'certs', cron: 'crons', 'deploy-hook': 'deploy-hooks', develop: 'dev', domain: 'domains',
    ir: 'integration-resource', i: 'install', ls: 'list', log: 'logs', mf: 'microfrontends', projects: 'project', redirect: 'redirects',
    rm: 'remove', rr: 'rolling-release', targets: 'target', team: 'teams', webhook: 'webhooks',
};
const VERCEL_COMMANDS = new Set([
    'activity', 'agent', 'ai-gateway', 'alerts', 'alias', 'api', 'bisect', 'blob', 'build', 'buy', 'cache', 'certs', 'connect', 'contract',
    'crons', 'curl', 'deploy', 'deploy-hooks', 'dev', 'dns', 'domains', 'edge-config', 'env', 'firewall', 'flags', 'git', 'guidance', 'help',
    'httpstat', 'init', 'inspect', 'install', 'integration', 'integration-resource', 'link', 'list', 'login', 'logout', 'logs', 'mcp',
    'metrics', 'microfrontends', 'oauth-apps', 'open', 'project', 'promote', 'pull', 'redeploy', 'redirects', 'remove', 'rollback',
    'rolling-release', 'routes', 'rules', 'sandbox', 'skills', 'switch', 'target', 'teams', 'telemetry', 'tokens', 'traces', 'upgrade',
    'usage', 'webhooks', 'whoami', ...Object.keys(VERCEL_ALIASES),
]);
const VERCEL_VALUE_FLAGS = new Set([
    ...VERCEL_GLOBAL_FLAGS,
    '--archive', '-b', '--build-env', '-e', '--env', '-F', '--format', '-m', '--meta', '--project', '--regions', '--timeout',
    '--environment', '--git-branch', '--id', '--value', '-X', '--method', '--field', '--raw-field', '-H', '--header', '--input',
    '--spec-url', '--generate', '--store-id', '--rw-token', '--oidc-token', '--next', '--limit', '--dpl',
]);
// Subcomandos que trocam o que está no ar em produção, com os subcomandos deles que só leem.
const VERCEL_LIVE = {
    promote: { read: ['status'], what: '`vercel promote` troca o deploy que está no ar em produção' },
    rollback: { read: ['status'], what: '`vercel rollback` volta a produção para um deploy anterior' },
    alias: { read: ['ls', 'list'], what: '`vercel alias` muda o deploy para onde cada domínio aponta' },
    'rolling-release': { read: ['fetch'], what: '`vercel rolling-release` muda o tráfego de produção entre deploys' },
};
// Grupos de subcomando: a ação que não é leitura muda o projeto, o time ou a conta na Vercel e pergunta. `nested`
// são ações que têm ações de novo (`vercel firewall rules add`); `read` são as leituras próprias do grupo.
const VERCEL_READ = new Set([
    'ls', 'list', 'inspect', 'get', 'status', 'fetch', 'overview', 'diff', 'check', 'price', 'search', 'discover', 'categories',
    'guide', 'installations', 'balance', 'export', 'list-versions', 'members', 'access-groups', 'access-summary', 'request', 'sso',
    'switch', 'open', 'inspect-group', 'pull', 'get-store', 'list-stores', 'list-requests', 'help',
]);
const VERCEL_GROUPS = {
    env: {}, teams: {}, git: {}, target: {}, webhooks: {}, routes: {}, redirects: {}, crons: {}, 'deploy-hooks': {}, microfrontends: {},
    connect: {}, domains: {}, dns: {}, certs: {}, blob: {}, cache: {}, 'integration-resource': {}, flags: {}, 'oauth-apps': {}, tokens: {},
    integration: { nested: ['resource'] },
    firewall: { nested: ['ip-blocks', 'rules', 'system-bypass', 'attack-mode', 'system-mitigations'] },
    project: { nested: ['protection', 'checks'] },
    'edge-config': { nested: ['items'] },
    sandbox: { nested: ['snapshots', 'sessions', 'config'] },
    'ai-gateway': { nested: ['api-keys', 'rules'] },
    telemetry: { read: ['enable', 'disable', 'flush'] },
    traces: {},
};
// Ações que imprimem um segredo novo ou existente: vão para o contexto do agente.
const VERCEL_PRINTS_SECRET = { project: ['token'], connect: ['token'], tokens: ['add'], 'ai-gateway': ['api-keys create'] };
// Arquivo que é a própria saída do processo: o que vai para ele chega ao contexto do agente.
const OUTPUT_FILES = new Set(['-', '/dev/stdout', '/dev/stderr', '/dev/tty', '/dev/fd/1', '/dev/fd/2', '/proc/self/fd/1', '/proc/self/fd/2']);

/**
 * CLI da Vercel. Deploy de produção, troca do que está no ar e o que muda o projeto, o time ou a conta perguntam;
 * variável de ambiente ou token que iria para a tela (ou para arquivo que a trava não protege) nega.
 */
export function checkVercel(values, state, ctx, findings) {
    if (!values.length || !['vercel', 'vc'].includes(commandName(values[0]))) return;
    const positional = [];
    let target = null;
    let environment = null;
    let deployment = false;
    let generate = false;
    for (let k = 1; k < values.length; k++) {
        const [flag, inline] = values[k].split(/=(.*)/s);
        if (flag === '-h' || flag === '--help') return;
        // A CLI lê `--prod=false` como `--prod`: flag booleana ignora o valor depois do `=`.
        if (flag === '--prod') target = 'production';
        else if (flag === '--target') target = (inline ?? values[++k] ?? '').toLowerCase();
        else if (flag === '--environment') environment = inline ?? values[++k] ?? '';
        else if (VERCEL_VALUE_FLAGS.has(flag)) {
            generate ||= flag === '--generate';
            deployment ||= flag === '--id';
            k += inline === undefined ? 1 : 0;
        } else if (!flag.startsWith('-') || values[k] === '-') positional.push(values[k]);
    }
    // O subcomando é a primeira palavra que a CLI conhece como subcomando, mesmo depois de valor de opção que a trava não conhece.
    const at = positional.findIndex((p) => VERCEL_COMMANDS.has(p));
    const typed = at === -1 ? 'deploy' : positional[at];
    const sub = VERCEL_ALIASES[typed] ?? typed;
    const [action, next] = at === -1 ? [] : positional.slice(at + 1);
    const ask = (what) => findings.push(finding('ask', 'producao', what));
    const deny = (what) => findings.push(finding('deny', 'segredo', what));
    // --prod no pull vira --environment=production na CLI; --id puxa o ambiente de um deploy, que pode ser o de produção.
    const production = (environment !== null && PROD_NAME.test(environment)) || target === 'production' || deployment;

    const live = VERCEL_LIVE[sub];
    if (live && !live.read.includes(action)) return ask(live.what);
    if (sub === 'deploy' && target === 'production') return ask('`vercel --prod` publica em produção pela CLI, fora do deploy que o git dispara');
    if (sub === 'redeploy' && (target === null || target === 'production')) {
        return ask('`vercel redeploy` refaz um deploy existente, que volta para produção se o original era de produção');
    }
    if (sub === 'remove') return ask('`vercel remove` apaga deploy, ou todos os deploys do projeto quando recebe o nome dele, inclusive o que está no ar');
    if (sub === 'api') {
        if (!['list', 'ls'].includes(action) && !generate) ask('`vercel api` chama a API da Vercel com a conta do humano, inclusive endpoint que apaga ou que devolve segredo');
        return;
    }
    if (sub === 'buy' || sub === 'install') return action ? ask(`\`vercel ${typed}\` compra produto ou instala integração do Marketplace para o time`) : undefined;
    if (sub === 'pull') return production ? ask('`vercel pull` de produção grava as variáveis de produção em .vercel/, no disco do projeto') : undefined;
    const secretAction = VERCEL_PRINTS_SECRET[sub]?.find((a) => a === action || a === `${action} ${next}`);
    if (secretAction) return deny(`\`vercel ${typed} ${secretAction}\` imprime um token ou uma chave, que vai para o contexto do agente`);
    if (sub === 'env' && action === 'pull') {
        // O arquivo de destino é a palavra depois de `pull`; sem ela, o .env.local.
        if (next !== undefined && OUTPUT_FILES.has(next)) return deny('`vercel env pull` para a saída padrão joga as variáveis, com os segredos, no contexto do agente');
        if (next !== undefined && state.cwd !== null && !isSecretPath(next, ctx, state.cwd)) {
            return deny(`\`vercel env pull\` grava as variáveis em ${next}, que a trava não reconhece como arquivo de segredo e o agente leria depois. Deixe o padrão (.env.local)`);
        }
        return production ? ask('`vercel env pull` de produção grava as variáveis de produção no disco do projeto') : undefined;
    }
    const group = VERCEL_GROUPS[sub];
    if (!group || action === undefined) return;
    const nested = group.nested?.includes(action);
    const verb = nested ? next : action;
    if (verb === undefined || VERCEL_READ.has(verb) || group.read?.includes(verb)) return;
    ask(`\`vercel ${typed} ${nested ? `${action} ${verb}` : verb}\` muda a configuração do projeto, do time ou da conta na Vercel`);
}

/**
 * CLIs de cofre de segredo e de plataforma que imprimem o valor das variáveis na tela: vão para o contexto do agente.
 */
export function checkSecretCli(values, findings) {
    if (!values.length) return;
    const tool = commandName(values[0]);
    const has = (flag) => values.some((v) => v === flag || v.startsWith(`${flag}=`));
    if (has('-h') || has('--help')) return;
    const after = (word) => (values.includes(word) ? values.slice(values.indexOf(word) + 1) : null);
    let what = null;
    if (tool === 'doppler' && after('secrets') && !has('--only-names')) {
        const sub = after('secrets').find((v) => ['get', 'download', 'set', 'delete', 'upload', 'substitute', 'notes'].includes(v));
        if (!sub || sub === 'get' || sub === 'substitute' || (sub === 'download' && has('--no-file'))) what = '`doppler secrets` imprime os valores';
    } else if (tool === 'op') {
        const [a, b] = values.slice(1).filter((v) => !v.startsWith('-'));
        if (a === 'read' || a === 'inject') what = `\`op ${a}\` imprime o valor do cofre`;
        else if (a === 'item' && b === 'get' && (has('--reveal') || values.some((v, k) => v === '--format=json' || (v === '--format' && values[k + 1] === 'json')))) {
            what = '`op item get --reveal` imprime o valor do cofre';
        }
    } else if (tool === 'railway' && ['variables', 'vars', 'variable'].includes(values[1]) && !has('--set')) what = '`railway variables` imprime os valores';
    else if (tool === 'heroku' && ['config', 'config:get'].includes(values.slice(1).find((v) => !v.startsWith('-')))) what = `\`heroku ${values[1]}\` imprime os valores`;
    else if (tool === 'infisical') {
        const sub = values.slice(1).find((v) => !v.startsWith('-'));
        const action = after('secrets')?.find((v) => !v.startsWith('-'));
        if (sub === 'export' || (sub === 'secrets' && !['set', 'delete'].includes(action))) what = `\`infisical ${sub}\` imprime os valores`;
    }
    if (what) findings.push(finding('deny', 'segredo', `${what} dos segredos, que vão para o contexto do agente. Quem consulta o valor é o humano`));
}

/**
 * `find -name '*.pem' -exec cat {} \;`: o padrão entre aspas não é arquivo para a trava de segredo, então o que ele
 * acha é conferido aqui, contra os nomes de segredo. Comando que só olha metadado (ls, stat, wc) passa.
 */
export function checkFindSecret(tests, exec, findings) {
    const cmd = exec.length ? commandName(exec[0]) : '';
    if (!cmd || METADATA_COMMANDS.has(cmd)) return;
    for (let k = 0; k < tests.length - 1; k++) {
        if (!['-name', '-iname', '-path', '-ipath', '-wholename', '-iwholename'].includes(tests[k])) continue;
        const pattern = tests[k + 1].split('/').pop();
        const regex = globToRegExp(tests[k].startsWith('-i') ? pattern.toLowerCase() : pattern);
        const hit = SECRET_SAMPLES.find((name) => regex.test(name));
        if (hit) return findings.push(finding('deny', 'segredo', `\`find ${tests[k]} ${tests[k + 1]}\` passa arquivo de segredo (${hit}) para \`${cmd}\`; o agente não lê nem copia`));
    }
}

function hostsIn(word) {
    const hosts = [];
    const url = word.match(/^[a-z][a-z0-9+.-]*:\/\/(?:[^@/]*@)?(\[[^\]]+\]|[^:/?#]+)/i);
    if (url) hosts.push(url[1]);
    const userHost = word.match(/^[^@\s/]+@([^:/\s]+)/);
    if (userHost) hosts.push(userHost[1]);
    const hostPath = word.match(/^([A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+):/);
    if (hostPath) hosts.push(hostPath[1]);
    const option = word.match(/^--?(?:host|hostname|h)=?(.+)$/);
    if (option && !option[1].startsWith('-')) hosts.push(option[1]);
    if (/^([a-z0-9-]+\.)+[a-z]{2,}$/i.test(word) || /^\d{1,3}(\.\d{1,3}){3}$/.test(word)) hosts.push(word);
    return hosts.map((h) => h.toLowerCase());
}

/**
 * Host de produção citado no comando. `network` trata as palavras como de comando de rede (valores de opção que o
 * uv e afins tiram da frente, como --index-url).
 */
export function checkProductionHints(values, assigns, seg, ctx, findings, network = false) {
    const cmd = values.length ? commandName(values[0]) : '';
    const allWords = [...values, ...Object.entries(assigns).map(([k, v]) => `${k}=${v}`), ...seg.redirs.map((r) => r.target).filter(Boolean)];
    const listed = ctx.config.hostsProducao;
    for (const word of allWords) {
        const lower = word.toLowerCase();
        const hit = listed.find((h) => lower.includes(h));
        if (hit) findings.push(finding('ask', 'producao', `cita ${hit}, listado como host de produção em .agent-guard.json`));
    }
    if (network || NETWORK_COMMANDS.has(cmd) || cmd === 'docker' || cmd === 'git') {
        for (const word of values.slice(1)) {
            const suspicious = hostsIn(word).find((h) => PROD_LABEL.test(h));
            if (suspicious) findings.push(finding('ask', 'producao', `${suspicious} parece host de produção`));
        }
    }
    const envValue = assigns.APP_ENV ?? values.find((v) => /^--env=/.test(v))?.split('=')[1] ?? (values.includes('--env') ? values[values.indexOf('--env') + 1] : undefined);
    if (envValue && /^(prod|production|producao)$/i.test(envValue)) {
        findings.push({ ...finding('ask', 'producao', `roda com APP_ENV=${envValue}`), envHint: true });
    }
}

export function checkSecretAccess(cmd, words, redirs, state, ctx, findings) {
    const cwd = state.cwd ?? ctx.projectDir;
    const mentioned = [];
    const consider = (value, word, role) => {
        if (!value) return;
        // cat .{env,x}: as chaves se expandem antes de o comando ler. Acima de 64 alternativas, a trava não confere e
        // trata como segredo.
        if (word?.brace && braceGroup(value)) {
            const options = expandBraces(value);
            if (options === null) mentioned.push({ path: value, role });
            else for (const option of options) consider(option, { ...word, brace: false, glob: /[*?[]/.test(option) }, role);
            return;
        }
        if (word?.glob) {
            const expanded = expandGlob(value, cwd, ctx.home);
            if (expanded === null) return;
            for (const e of expanded) if (isSecretPath(e, ctx, cwd)) mentioned.push({ path: e, role });
            return;
        }
        if (isSecretPath(value, ctx, cwd)) mentioned.push({ path: value, role });
    };

    // A chave dada ao ssh com -i é usada por ele para autenticar, não lida para o modelo. Só o valor do -i;
    // qualquer outro caminho de segredo no mesmo comando (o que o scp copia, por exemplo) continua valendo.
    const identity = new Set(IDENTITY_FILE_COMMANDS.has(cmd) ? words.flatMap((w, k) => (k > 0 && w.value === '-i' ? [k + 1] : [])) : []);
    words.slice(1).forEach((w, index) => {
        const v = w.value;
        if (identity.has(index + 1)) return;
        if (v.startsWith('-')) {
            const fileOption = v.match(/^--[\w-]*file[\w-]*=(.+)$/);
            if (fileOption) consider(fileOption[1], null, 'arg');
            return;
        }
        consider(v, w, index === words.length - 2 ? 'last' : 'arg');
    });
    for (const r of redirs) {
        if (['<<', '<<-', '<<<'].includes(r.op) || !r.target || /^\d+$|^-$/.test(r.target)) continue;
        consider(r.target, r.targetWord, r.op.startsWith('<') ? 'read' : 'write');
    }
    if (!mentioned.length) return;

    const onlyArgs = mentioned.every((m) => m.role === 'arg' || m.role === 'last');
    // `vercel env pull .env.local` grava o arquivo com o que vem da Vercel, sem passar pelo agente; o de produção a
    // trava producao pergunta. Só arquivo de variável: chave (~/.ssh) continua negada.
    const values = words.map((w) => w.value);
    const pull = values.indexOf('env') !== -1 && values[values.indexOf('env') + 1] === 'pull';
    if ((cmd === 'vercel' || cmd === 'vc') && pull && onlyArgs && mentioned.every((m) => /^\.env|\.env(\.|$)/.test(basename(m.path)))) return;
    if (onlyArgs && METADATA_COMMANDS.has(cmd)) return;
    if (onlyArgs && cmd === 'git') {
        const sub = words.slice(1).map((w) => w.value).find((v) => !v.startsWith('-'));
        if (GIT_METADATA_SUBCOMMANDS.has(sub) || (sub === 'rm' && words.some((w) => w.value === '--cached'))) return;
    }
    if ((cmd === 'cp' || cmd === 'install') && mentioned.every((m) => m.role === 'last')) return;

    const paths = [...new Set(mentioned.map((m) => m.path))].join(', ');
    const writing = mentioned.every((m) => m.role === 'write');
    findings.push(
        finding('deny', 'segredo', writing
            ? `grava em ${paths}, que guarda segredo; quem edita é o humano`
            : `${cmd || 'o comando'} acessa ${paths}, que guarda segredo; o agente não lê nem copia. Para saber se uma variável existe, procure o NOME dela no código ou no .env.example`),
    );
}

const GREP_VALUE = new Set(['-e', '-f', '--regexp', '--file', '-m', '--max-count', '-A', '-B', '-C', '--context', '--after-context', '--before-context', '--include', '--exclude', '--exclude-dir', '--exclude-from', '--label', '-d', '--directories', '-D', '--devices', '--binary-files']);
const RG_VALUE = new Set([
    '-e', '--regexp', '-f', '--file', '-g', '--glob', '--iglob', '-t', '--type', '-T', '--type-not', '--type-add', '-m', '--max-count',
    '-A', '-B', '-C', '--context', '--after-context', '--before-context', '-d', '--max-depth', '-M', '--max-columns', '-j', '--threads',
    '-E', '--encoding', '-r', '--replace', '--pre', '--pre-glob', '--sort', '--sortr', '--color', '--colors', '--path-separator',
    '--max-filesize', '--ignore-file', '--dfa-size-limit', '--regex-size-limit', '-G', '--file-search-regex', '--ignore',
]);

/**
 * Lê as opções da busca: `onValue(opção, valor)` para as que recebem valor, e devolve as pastas e arquivos (sem o
 * padrão, a não ser que ele venha de -e ou -f, inclusive colado: -eDB_PASSWORD).
 */
function searchArgs(args, valueFlags, onValue) {
    const positional = [];
    let hasPattern = false;
    for (let k = 0; k < args.length; k++) {
        const arg = args[k];
        if (/^-[ef]./.test(arg)) {
            hasPattern = true;
            continue;
        }
        const [flag, inline] = arg.startsWith('--') ? arg.split(/=(.*)/s) : [arg];
        if (!valueFlags.has(flag)) {
            if (!arg.startsWith('-')) positional.push(arg);
            continue;
        }
        const value = inline ?? args[++k];
        if (['-e', '-f', '--regexp', '--file'].includes(flag)) hasPattern = true;
        if (value !== undefined) onValue(flag, value);
    }
    return hasPattern ? positional : positional.slice(1);
}

/**
 * Filtro do grep. No GNU, --include e --exclude valem pelo nome e o último que casa decide; sem nenhum que case, o
 * arquivo entra, a não ser que o primeiro seja --include. No do macOS (FreeBSD), o padrão casa com o nome ou com o
 * caminho inteiro (o * pega a barra), e sem nenhum que case o arquivo só entra se não há --include. A trava vale pelo
 * que lê o arquivo, em qualquer um dos dois.
 */
function grepReads(filters, name, path) {
    const gnu = filters.findLast((f) => f.name.test(name));
    const bsd = filters.findLast((f) => f.name.test(name) || f.path.test(path));
    return (gnu ? gnu.include : !filters[0]?.include) || (bsd ? bsd.include : !filters.some((f) => f.include));
}

/**
 * Filtro do rg: -g com ! tira, sem ! restringe, o último que casa decide; sem nenhum que case, o arquivo entra se não
 * há -g que restringe nem -t (os tipos do rg não incluem arquivo de segredo). Glob com / o rg aplica ao caminho: aqui
 * ele só restringe pelo último pedaço e nunca tira, para errar do lado de negar.
 */
function rgReads(globs, typed, name) {
    const last = globs.findLast((g) => (g.path ? g.include && g.last.test(name) : g.regex.test(name)));
    if (last) return last.include;
    return !globs.some((g) => g.include) && !typed;
}

export function checkRecursiveGrep(cmd, args, state, ctx, findings) {
    const cwd = state.cwd ?? ctx.projectDir;
    const grep = cmd === 'grep' || cmd === 'egrep' || cmd === 'fgrep';
    let paths;
    let reads;
    let skipsDir;
    let advice;
    if (grep) {
        const recursive = args.some((a) => a === '-r' || a === '-R' || a === '--recursive' || a === '--dereference-recursive' || (/^-[^-]/.test(a) && /[rR]/.test(a)));
        if (!recursive) return;
        const filters = [];
        const skipDirs = [];
        paths = searchArgs(args, GREP_VALUE, (flag, value) => {
            if (flag === '--include' || flag === '--exclude') filters.push({ include: flag === '--include', name: globToRegExp(value), path: globToRegExp(value, '.*') });
            if (flag === '--exclude-dir') skipDirs.push(globToRegExp(value));
        });
        reads = (name, path) => grepReads(filters, name, path);
        skipsDir = (name) => skipDirs.some((regex) => regex.test(name));
        advice = "Use `rg` (respeita o .gitignore) ou limite aos arquivos de código com --include (ex.: --include='*.php')";
    } else if (cmd === 'rg' || cmd === 'ag') {
        // -u lê o que o .gitignore esconde; -uu (ou --hidden) também os arquivos ocultos, como o .env.
        let level = 0;
        let hidden = false;
        let noIgnore = false;
        for (const a of args) {
            if (/^-[a-zA-Z]+$/.test(a) && !/[gtTefmABCdMjErG]/.test(a.slice(1))) level += (a.match(/u/g) ?? []).length;
            if (a === '--unrestricted') level++;
            if (a === '--hidden') hidden = true;
            if (a.startsWith('--no-ignore')) noIgnore = true;
        }
        noIgnore ||= level >= 1;
        hidden ||= level >= 2;
        if (!noIgnore) return;
        const globs = [];
        let typed = false;
        paths = searchArgs(args, RG_VALUE, (flag, value) => {
            if (cmd === 'rg' && ['-g', '--glob', '--iglob'].includes(flag)) {
                const clean = value.replace(/^!/, '');
                // --iglob não diferencia maiúscula.
                const regex = (glob) => new RegExp(globToRegExp(glob).source, flag === '--iglob' ? 'i' : '');
                globs.push({ include: !value.startsWith('!'), regex: regex(clean), path: clean.includes('/'), last: regex(clean.split('/').filter(Boolean).at(-1) ?? '*') });
            }
            if (cmd === 'rg' && ['-t', '--type'].includes(flag)) typed = true;
        });
        reads = (name) => (hidden || !name.startsWith('.')) && rgReads(globs, typed, name);
        skipsDir = (name) => (!hidden && name.startsWith('.')) || globs.some((g) => !g.include && !g.path && g.regex.test(name));
        advice = "Tire o -u/--no-ignore ou limite os arquivos com -g (ex.: -g '*.php') ou -t php";
    } else return;

    if (!paths.length) paths = ['.'];
    for (const p of paths) {
        const abs = expandPath(p, cwd, ctx.home);
        let found;
        try {
            // Arquivo dado no comando é lido mesmo fora do filtro.
            found = statSync(abs).isDirectory()
                ? treeHasSecret(abs, ctx, undefined, { reads: (name, full) => reads(name, `${p.replace(/\/+$/, '')}/${relative(abs, full)}`), skipsDir })
                : isSecretPath(abs, ctx);
        } catch {
            found = false;
        }
        if (found === 'grande') {
            return findings.push(
                finding('deny', 'segredo', `busca recursiva em ${p} passa de 20 mil arquivos, e a trava não conferiu se ela lê segredo. Restrinja a pasta ou pule a pasta grande (--exclude-dir no grep, -g '!pasta' no rg)`),
            );
        }
        if (found) return findings.push(finding('deny', 'segredo', `busca recursiva em ${p} lê também o .env e as chaves que estão lá. ${advice}`));
    }
}

export function checkGuardWrites(cmd, words, redirs, state, ctx, findings) {
    const cwd = state.cwd ?? ctx.projectDir;
    const writesRedirect = redirs.find((r) => r.target && !r.op.startsWith('<') && isGuardFile(r.target, ctx, cwd));
    const mentions = words.slice(1).find((w) => !w.value.startsWith('-') && isGuardFile(w.value, ctx, cwd));
    if (writesRedirect || (mentions && !READ_ONLY_COMMANDS.has(cmd) && !METADATA_COMMANDS.has(cmd) && !NAVIGATION_COMMANDS.has(cmd))) {
        const path = writesRedirect?.target ?? mentions.value;
        findings.push(finding('ask', 'trava-protegida', `${cmd} mexe em ${path}, que configura as travas do agente`));
    }
}
