/**
 * Contexto da decisão e reconhecimento de caminhos: o que é segredo, o que é arquivo de trava.
 */
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import { homedir } from 'node:os';
import { basename, dirname, isAbsolute, join, relative, resolve, sep } from 'node:path';

const GUARD_FILES = ['.claude/settings.json', '.claude/settings.local.json', '.cursor/hooks.json', '.codex/config.toml', '.codex/rules', '.ai/adapters', '.agent-guard.json'];

// A trava só lê arquivo comum e pequeno: FIFO ou arquivo enorme pendura o hook até o timeout, e hook que estoura
// o tempo não decide nada (o comando segue o fluxo normal da ferramenta).
const MAX_READ = 256 * 1024;

/**
 * Conteúdo do arquivo; '' se ele não existe; null se existe mas a trava não lê (pasta, FIFO, grande demais).
 */
export function readText(path) {
    let info;
    try {
        info = statSync(path);
    } catch {
        return '';
    }
    if (!info.isFile() || info.size > MAX_READ) return null;
    try {
        return readFileSync(path, 'utf8');
    } catch {
        return null;
    }
}

export function loadConfig(projectDir) {
    const file = join(projectDir, '.agent-guard.json');
    if (!existsSync(file)) return { hostsProducao: [] };
    const text = readText(file);
    if (text === null) throw new Error('.agent-guard.json não é um arquivo que a trava consiga ler');
    const parsed = JSON.parse(text);
    return { hostsProducao: (parsed.hostsProducao ?? []).map((h) => String(h).toLowerCase()).filter(Boolean) };
}

/**
 * Lê do .env só as chaves que a trava precisa. Os valores nunca saem daqui.
 */
export function readDotEnv(dir, envName) {
    const candidates = envName ? [join(dir, `.env.${envName}`), join(dir, '.env')] : [join(dir, '.env')];
    for (const file of candidates) {
        if (!existsSync(file)) continue;
        const text = readText(file);
        if (text === null) return { unreadable: basename(file) };
        const vars = {};
        for (const line of text.split('\n')) {
            const match = line.match(/^[ \t]*(APP_ENV|DB_HOST)[ \t]*=[ \t]*"?([^"#\s]*)"?/);
            if (match) vars[match[1]] = match[2];
        }
        return vars;
    }
    return null;
}

/**
 * Lê do .env só as chaves pedidas (Set ou lista); null se o arquivo existe mas a trava não lê.
 */
function readEnvKeys(file, keys) {
    const text = readText(file);
    if (text === null) return null;
    if (!(keys instanceof Set)) keys = new Set(keys);
    const vars = {};
    for (const line of text.split('\n')) {
        const match = line.match(/^[ \t]*(?:export[ \t]+)?([A-Za-z_][A-Za-z0-9_]*)[ \t]*=(.*)$/);
        if (!match || !keys.has(match[1])) continue;
        let value = match[2].trim();
        if (value[0] === '"' || value[0] === "'") {
            const end = value.indexOf(value[0], 1);
            value = end === -1 ? value.slice(1) : value.slice(1, end);
        } else {
            const comment = value.search(/[ \t]#/);
            if (comment !== -1) value = value.slice(0, comment).trim();
        }
        vars[match[1]] = value;
    }
    return vars;
}

export const LOOPBACK_HOSTS = new Set(['localhost', '127.0.0.1', '::1', '0.0.0.0', 'host.docker.internal']);
const PRISMA_CONFIGS = ['prisma.config.ts', 'prisma.config.mts', 'prisma.config.cts', 'prisma.config.js', 'prisma.config.mjs', 'prisma.config.cjs', '.config/prisma.ts'];
const UNREADABLE = Object.freeze({ host: null, local: false });

/**
 * Um host do banco: socket (local, ou o do Cloud SQL Auth Proxy, que leva à nuvem), nome ou IP. Nome sem ponto é
 * serviço do Docker, a não ser no Oracle e no ODBC, onde nome solto é apelido (TNS, DSN) que pode apontar para
 * qualquer lugar.
 */
function oneHost(raw, { scheme, port, path, params }) {
    if (raw.startsWith('/')) {
        const cloud = raw.match(/\/cloudsql\/([^/]+)/);
        return cloud ? { host: cloud[1].replace(/:/g, '.').toLowerCase(), local: false } : { host: 'socket local', local: true };
    }
    // [::1]:5432 e host:5432 levam porta; IPv6 sem colchete (host=::1 na query), não.
    let host = raw.startsWith('[') ? raw.slice(1, raw.indexOf(']')) : raw.split(':').length > 2 ? raw : raw.replace(/:\d*$/, '');
    try {
        host = decodeURIComponent(host).toLowerCase();
    } catch {
        return UNREADABLE;
    }
    // Host que só existe depois de expandido (${DB_HOST}, %(host)s) não é serviço do Docker: a trava não sabe para onde aponta.
    if (!host || /[\s$%{}()@,]/.test(host)) return UNREADABLE;
    const loopback = LOOPBACK_HOSTS.has(host);
    const named = (path && path !== '/') || params.has('service_name') || params.has('sid');
    if (/^(oracle|mssql)|odbc/i.test(scheme) && !loopback && !port && !named && !/[.:]/.test(host)) return UNREADABLE;
    return { host, local: loopback || !/[.:]/.test(host) };
}

/**
 * Para onde a URL de banco aponta, sem a URL: só o host sai daqui, para a trava decidir e citar.
 * local = mesma máquina (loopback, socket, SQLite em arquivo) ou serviço do Docker (nome sem ponto).
 * Lê como o SQLAlchemy: a senha vai até o @, mesmo com / no meio. Um segundo @ deixa o host ilegível, para
 * pedaço de senha nunca virar "host" na mensagem. O host da autoridade e o `host` e o `hostaddr` da query contam
 * todos (cada driver lê um: o libpq prefere o hostaddr, o MySQL ignora a query); host repetido ou com vírgula é lista,
 * e basta um fora da máquina.
 */
export function databaseHost(url) {
    // sqlite:///arquivo e sqlite+pysqlcipher://:senha@/arquivo são locais; sqlite+libsql://host (Turso) é banco na rede.
    if (/^file:/i.test(url) || /^sqlite(\+\w+)?:(\/\/([^@/]*@)?(\/|$)|[^/])/i.test(url)) return { host: 'arquivo SQLite local', local: true };
    const m = url.match(/^([a-z][\w+.-]*):\/\/(?:[^:/@]*(?::[^@]*)?@)?(\[[^\]]*\]|[^/:?#@]*)(?::(\d*))?(?=[/?#]|$)([^?#]*)(?:\?([^#]*))?/i);
    if (!m || m[4].includes('@') || (m[5] ?? '').includes('@')) return UNREADABLE;
    const [, scheme, netloc, port, path, query] = m;
    const params = new URLSearchParams(query ?? '');
    // pg_service.conf e odbc_connect (MSSQL): o host fica escondido no serviço ou na string de conexão.
    if (params.has('service') || params.has('odbc_connect')) return UNREADABLE;
    // pyodbc sem banco é conexão por DSN do odbc.ini (o SQLAlchemy lê o host como nome do DSN): aponta para qualquer lugar.
    if (/odbc/i.test(scheme) && !(path && path !== '/')) return UNREADABLE;
    const raws = [netloc, ...params.getAll('hostaddr'), ...params.getAll('host')].flatMap((h) => h.split(',')).map((h) => h.trim()).filter(Boolean);
    // Sem host nenhum (postgresql:///app), o driver escolhe: a trava não sabe qual.
    const hosts = (raws.length ? raws : ['']).map((raw) => oneHost(raw, { scheme, port, path, params }));
    if (hosts.some((h) => !h.host)) return UNREADABLE;
    const outside = hosts.filter((h) => !h.local);
    return outside.length ? { host: outside.map((h) => h.host).join(','), local: false } : hosts[0];
}

/**
 * Qual banco o Prisma CLI vai usar ao rodar em `dir`. O nome da variável sai do prisma.config.* e do
 * datasource do schema (padrão DATABASE_URL); o valor, do shell do comando, do ambiente herdado pelo hook e
 * depois dos .env, na ordem em que o Prisma carregaria. Valor que não parece URL (o config pode citar outra
 * variável) é ignorado.
 * @returns {{ targets: Array<{ name, host, local, why? }>, nodeEnv: string|null }}
 */
export function prismaDatabase(dir, { env = {}, processEnv = {}, envFiles = [], schema = null } = {}) {
    const config = PRISMA_CONFIGS.map((f) => readText(join(dir, f)) ?? '').join('\n');
    const schemaPath = schema ?? config.match(/schema\s*:\s*["'`]([^"'`]+)["'`]/)?.[1];
    const schemaText = [schemaPath, 'prisma/schema.prisma', 'schema.prisma'].filter(Boolean).map((p) => readText(resolve(dir, p)) ?? '').find(Boolean) ?? '';
    const datasource = schemaText.match(/datasource\s+\w+\s*\{[^}]*\}/)?.[0] ?? '';

    const names = new Set();
    for (const text of [config, datasource]) {
        for (const m of text.matchAll(/process\.env(?:\.([A-Za-z_]\w*)|\[\s*["'`]([A-Za-z_]\w*)["'`]\s*\])|\benv\(\s*["'`]([A-Za-z_]\w*)["'`]\s*\)/g)) {
            names.add(m[1] ?? m[2] ?? m[3]);
        }
    }
    names.delete('NODE_ENV');
    if (!names.size) names.add('DATABASE_URL');

    const referenced = [...config.matchAll(/["'`](\.env[^"'`\s]*)["'`]/g)].map((m) => m[1]);
    const files = [...new Set([...envFiles, ...referenced, '.env', 'prisma/.env'].map((f) => resolve(dir, f)))];
    const keys = [...names, 'NODE_ENV'];
    // A variável do processo vale mais que a do .env; o NODE_ENV herdado pelo hook é o da ferramenta, não o do projeto.
    const values = Object.fromEntries(keys.map((k) => [k, env[k] ?? (k === 'NODE_ENV' ? undefined : processEnv[k])]));
    const unreadable = [];
    for (const file of files) {
        const found = readEnvKeys(file, new Set(keys));
        if (found === null) unreadable.push(basename(file));
        else for (const k of keys) if (values[k] === undefined && found[k] !== undefined) values[k] = found[k];
    }

    const targets = [...names]
        .filter((name) => /^[a-z][\w+.-]*:/i.test(values[name] ?? ''))
        .map((name) => ({ name, ...databaseHost(values[name]) }));
    // Valor que não é URL e traz $ (variável do shell, export que pode não ter rodado): a trava não sabe para onde aponta.
    for (const name of names) {
        const value = values[name] ?? '';
        if (!/^[a-z][\w+.-]*:/i.test(value) && /[$`]/.test(value)) {
            targets.push({ name, host: null, local: false, why: `o valor de ${name}, que vem de variável` });
        }
    }
    for (const file of unreadable) targets.push({ name: file, host: null, local: false, why: `o arquivo ${file}` });
    return { targets, nodeEnv: values.NODE_ENV ?? null };
}

/**
 * Lê o .ini como o ConfigParser do Python lê o alembic.ini: seções, `chave = valor` (ou `chave: valor`),
 * comentário com # e ;, continuação recuada e %(here)s ou %(chave)s da própria seção e da [DEFAULT]. Sem regex
 * nas linhas e sem protótipo nos objetos: seção [__proto__] não mexe no resto da trava.
 */
function readIni(path) {
    const text = readText(path);
    const sections = Object.create(null);
    let current = null;
    let lastKey = null;
    let lastIndent = 0;
    for (const line of (text ?? '').split('\n')) {
        const trimmed = line.trim();
        if (!trimmed || trimmed[0] === '#' || trimmed[0] === ';') continue;
        if (trimmed[0] === '[' && trimmed.lastIndexOf(']') > 0) {
            current = sections[trimmed.slice(1, trimmed.lastIndexOf(']')).trim()] ??= Object.create(null);
            lastKey = null;
            continue;
        }
        if (!current) continue;
        // Linha mais recuada que a da opção anterior continua o valor dela; no mesmo recuo, ou logo depois do cabeçalho,
        // o ConfigParser a lê como opção nova.
        const indent = line.search(/\S/);
        if (indent > lastIndent && lastKey) {
            current[lastKey] += `\n${trimmed}`;
            continue;
        }
        const cut = trimmed.search(/[=:]/);
        if (cut > 0) {
            lastKey = trimmed.slice(0, cut).trim().toLowerCase();
            lastIndent = indent;
            current[lastKey] = trimmed.slice(cut + 1).trim();
        }
    }
    const raw = (section, key) => sections[section]?.[key] ?? sections.DEFAULT?.[key];
    return {
        text,
        has: (section) => Object.hasOwn(sections, section),
        get: (section, key) =>
            raw(section, key)
                ?.replace(/%\((\w+)\)s/g, (m, name) => (name === 'here' ? dirname(path) : (raw(section, name.toLowerCase()) ?? m)))
                .replace(/%%/g, '%'),
    };
}

/**
 * O script_location do [tool.alembic] no pyproject.toml, que o Alembic 1.16+ lê quando não há alembic.ini.
 */
function pyprojectScriptLocation(dir) {
    const lines = (readText(join(dir, 'pyproject.toml')) ?? '').split('\n');
    const start = lines.findIndex((l) => l.trim() === '[tool.alembic]');
    if (start === -1) return null;
    for (const line of lines.slice(start + 1)) {
        if (line.trim().startsWith('[')) break;
        const m = line.match(/^[ \t]*script_location[ \t]*=[ \t]*["']([^"']+)["']/);
        if (m) return m[1].replace(/%\(here\)s/g, dir);
    }
    return null;
}

/**
 * Onde pode estar o env.py: pasta (relativa à do comando) ou recurso de pacote (`meuapp:migracoes`).
 */
function scriptDirs(location, dir) {
    const resource = location.match(/^([A-Za-z_][\w.]*):([^/\\].*)$/);
    if (!resource || /^[A-Za-z]:[\\/]/.test(location)) return [resolve(dir, location)];
    const path = join(resource[1].replace(/\./g, '/'), resource[2]);
    return [resolve(dir, path), resolve(dir, 'src', path)];
}

/**
 * A pasta e as de cima dela até a raiz do projeto: onde o python-dotenv e o pydantic acham o .env.
 */
function upToProject(start, projectDir) {
    const dirs = [start];
    for (let current = start; isInside(current, projectDir); ) {
        current = dirname(current);
        dirs.push(current);
    }
    return dirs;
}

/**
 * Texto do módulo Python (`app.core.config`) procurado nas pastas dadas, no layout plano e no `src/`.
 */
function readPythonModule(module, bases) {
    const path = module.replace(/\./g, '/');
    for (const base of bases) {
        for (const candidate of [`${path}.py`, `${path}/__init__.py`, `src/${path}.py`, `src/${path}/__init__.py`]) {
            const text = readText(join(base, candidate));
            if (text) return text;
        }
    }
    return '';
}

/**
 * Índice do módulo de settings numa passada só: campos (membro recuado `nome: tipo`), métodos e env_prefix.
 */
function indexSettings(text) {
    const lines = text.split('\n');
    const fields = new Map();
    const defs = new Map();
    lines.forEach((line, i) => {
        const def = line.match(/^([ \t]*)def[ \t]+([A-Za-z_]\w*)[ \t]*\(/);
        if (def) {
            if (!defs.has(def[2])) defs.set(def[2], { line: i, indent: def[1].length });
            return;
        }
        const field = line.match(/^[ \t]+([A-Za-z_]\w*)[ \t]*:/);
        if (field && !fields.has(field[1])) fields.set(field[1], line);
    });
    const prefixes = [...new Set([...text.matchAll(/env_prefix["']?[ \t]*[:=][ \t]*["']([^"'\n]*)["']/g)].map((m) => m[1]))];
    return { text, lines, fields, defs, prefixes, unsupported: !text || /secrets_dir|alias_generator/.test(text) };
}

/**
 * Campo simples da classe de settings: as variáveis que o pydantic-settings pode ler (com e sem cada env_prefix do
 * módulo, porque a trava não sabe de que classe o prefixo é) e o valor padrão. Campo com alias (alias,
 * validation_alias, env=) lê outra variável: null, a trava não resolve.
 */
function plainField(index, attr) {
    const line = index.fields.get(attr);
    if (!line || /\b(validation_)?alias[ \t]*=|\benv[ \t]*=/.test(line)) return null;
    const bases = [attr, ...index.prefixes.map((p) => `${p}${attr}`)];
    const names = [...new Set(bases.flatMap((n) => [n, n.toUpperCase(), n.toLowerCase()]))];
    const fallback = line.match(/=[ \t]*(?:Field\([ \t]*(?:default[ \t]*=[ \t]*)?)?["']([^"'\n]+)["']/)?.[1] ?? null;
    return { names, fallback };
}

// Nome de URL do banco (DATABASE_URL, POSTGRES_URL_NON_POOLING, DATABASE_URL_SYNC, SQLALCHEMY_DATABASE_URI, PG_DSN)
// e de host (POSTGRES_HOST, DB_SERVER, PGHOST).
const URLISH = /(url|uri|dsn)$|(^|_)(url|uri|dsn)_/i;
const HOSTISH = /(host|server)$/i;
// Só o nome que diz banco é obrigatório (citado sem valor, nega); REDIS_URL, SENTRY_DSN e afins contam só com URL de banco.
const DBISH = /(^|_)(database|db|sql|sqlalchemy|pg|postgres|postgresql|mysql|mariadb|mssql|oracle|sqlite|cockroach)/i;
const dbName = (name) => (URLISH.test(name) || HOSTISH.test(name)) && DBISH.test(name);
// Dialeto de banco do SQLAlchemy: URL com outro esquema (redis://, amqp://, https://) não é o banco.
const DB_URL = /^(postgres(ql)?|mysql|mariadb|sqlite|mssql|oracle|cockroachdb|redshift|snowflake|db2|ibm_db_sa|firebird|sybase|teradata|trino|presto|clickhouse|duckdb|databricks|awsathena|spanner|hana|vertica|informix|access)(\+[\w.-]+)?:\/\//i;

/**
 * Como o pydantic-settings acha settings.X. Campo simples: as variáveis e o padrão da classe. Campo calculado
 * (def, computed_field, property): os atributos *_URL/*_DSN e *_HOST/*_SERVER que ele usa (o layout do
 * full-stack-fastapi-template). O resto (alias, secrets_dir, alias_generator, cálculo sem host nem URL, herança de
 * outro módulo) devolve null, e o comando é negado.
 */
function settingsField(index, attr) {
    if (index.unsupported) return null;
    const def = index.defs.get(attr);
    if (!def) {
        const field = plainField(index, attr);
        return field && { kind: HOSTISH.test(attr) ? 'host' : 'url', ...field };
    }
    const body = [];
    for (const line of index.lines.slice(def.line + 1)) {
        if (line.trim() && line.search(/\S/) <= def.indent) break;
        body.push(line);
    }
    const refs = [...new Set([...body.join('\n').matchAll(/self\.([A-Za-z_]\w*)/g)].map((m) => m[1]))].filter((r) => r !== attr);
    const parts = refs.filter((r) => HOSTISH.test(r) || URLISH.test(r)).map((r) => ({ attr: r, kind: HOSTISH.test(r) ? 'host' : 'url', field: plainField(index, r) }));
    if (!parts.some((p) => p.kind === 'host') || parts.some((p) => !p.field)) return null;
    return { kind: 'computed', parts: parts.map((p) => ({ attr: p.attr, kind: p.kind, ...p.field })) };
}

/**
 * Primeiro argumento de uma chamada, a partir do caractere depois do `(`: até a vírgula ou o `)` do mesmo nível.
 */
function firstArgument(text, at) {
    let depth = 0;
    for (let i = at; i < text.length; i++) {
        const c = text[i];
        if (c === '(') depth++;
        else if (c === ')' && depth-- === 0) return text.slice(at, i).trim();
        else if (c === ',' && depth === 0) return text.slice(at, i).trim();
    }
    return text.slice(at).trim();
}

const ENVIRONMENT_KEYS = ['ENVIRONMENT', 'APP_ENV', 'ENV'];
const PROD_ENVIRONMENT = /^(prod|production|producao|prd)$/i;
// Nomes demais citados no env.py deixam a análise cara além do timeout do hook: acima disso, a trava nega.
const MAX_NAMES = 40;

/**
 * Qual banco o Alembic vai usar ao rodar em `dir`.
 *
 * Conta a sqlalchemy.url do alembic.ini (ou do -c, do ALEMBIC_CONFIG, da seção do -n) sempre que o env.py a lê,
 * mesmo como reserva, e tudo o que ele cita para o banco (variável *_URL/*_DSN ou *_HOST/*_SERVER por os.environ,
 * getenv, decouple e environs; settings.X), com o valor do shell, do ambiente herdado pelo hook, dos .env (o da
 * pasta e os de cima até a raiz do projeto, os do --env-file e os citados no código), do padrão do getenv e da
 * classe de settings. Fonte citada que a trava não resolve vira host ilegível; env.py que troca a URL do ini por
 * algo que ela não segue também. Sem saber qual delas o Python vai usar, todas contam: basta uma de produção ou
 * uma ilegível. Os valores não saem daqui, só o host.
 * @returns {{ targets: Array<{ name, host, local, why? }>, looked: string[], environment: { name, value }|null, problem: string|null }}
 */
export function alembicDatabase(dir, { env = {}, processEnv = {}, envFiles = [], config = null, section = 'alembic', xArgs = [], projectDir = dir, home = homedir() } = {}) {
    const iniPath = expandPath(config ?? env.ALEMBIC_CONFIG ?? processEnv.ALEMBIC_CONFIG ?? 'alembic.ini', dir, home);
    const ini = readIni(iniPath);
    if (ini.text === null) return { problem: `a trava não consegue ler ${basename(iniPath)}` };
    const location = ini.get(section, 'script_location') ?? pyprojectScriptLocation(dir);
    const scriptDir = location ? scriptDirs(location, dir).find((d) => readText(join(d, 'env.py'))) : null;
    // Sem o env.py a trava não sabe de onde vem a URL; se o Alembic também não acha, negar não custa nada.
    if (!scriptDir) return { problem: 'a trava não achou o env.py do Alembic (script_location) para saber de onde vem a URL do banco' };
    const envPy = readText(join(scriptDir, 'env.py'));

    const readsIni = /get_main_option\s*\(\s*["']sqlalchemy\.url["']|get_section\s*\(/.test(envPy);
    // create_engine com a própria URL do ini (direto, por context.config ou por variável que a recebe) não troca nada.
    const iniUrlCall = String.raw`(?:context\.)?config\.get_main_option\(\s*["']sqlalchemy\.url["']\s*\)`;
    const iniVars = new Set([...envPy.matchAll(new RegExp(`^[ \\t]*(\\w+)[ \\t]*=[ \\t]*${iniUrlCall}[ \\t]*$`, 'gm'))].map((m) => m[1]));
    const engineArgs = [...envPy.matchAll(/create_(?:async_)?engine\s*\(/g)].map((m) => firstArgument(envPy, m.index + m[0].length));
    const replacesIni =
        /set_(main|section)_option\s*\(|\[\s*["']sqlalchemy\.url["']\s*\]\s*=|["']sqlalchemy\.url["']\s*:|URL\.create\s*\(/.test(envPy) ||
        engineArgs.some((arg) => !new RegExp(`^${iniUrlCall}$`).test(arg) && !iniVars.has(arg));
    const cited = /(?:environ\s*(?:\.get\s*\(|\[)|getenv\s*\(|\bconfig\s*\(|\benv(?:\.\w+)?\s*\()\s*["']([A-Za-z_]\w*)["']/g;
    const envNames = [...new Set([...envPy.matchAll(cited)].map((m) => m[1]))];
    // settings.X, get_settings().X e objeto com outro nome (app_settings.X): este, sem classe que a trava ache, nega.
    const refs = [...envPy.matchAll(/\b\w*settings(?:\(\))?\.([A-Za-z_]\w*)/g)];
    const attrs = [...new Set(refs.map((m) => m[1]))];
    // Como o env.py escreve (app_settings.X, get_settings().X), para a mensagem.
    const written = new Map(refs.map((m) => [m[1], m[0]]).reverse());
    if (envNames.length + attrs.length > MAX_NAMES) return { problem: 'o env.py cita variáveis demais para a trava analisar' };
    // Padrão do getenv: texto ou a própria URL do ini (os.environ.get("DATABASE_URL", config.get_main_option("sqlalchemy.url"))).
    const iniUrl = ini.get(section, 'sqlalchemy.url') ?? '';
    const defaults = new Map(
        [...envPy.matchAll(/(?:getenv|environ\.get)\(\s*["']([A-Za-z_]\w*)["']\s*,\s*(?:["']([^"'\n]*)["']|config\.get_main_option\(\s*["']sqlalchemy\.url["']\s*\))/g)].map((m) => [m[1], m[2] ?? iniUrl]),
    );
    const module = envPy.match(/^[ \t]*from[ \t]+([\w.]+)[ \t]+import[ \t][^\n]*\b\w*settings\b/m)?.[1];
    const index = indexSettings(module ? readPythonModule(module, [dir, dirname(iniPath), projectDir]) : '');
    const fields = attrs.map((attr) => ({ attr, field: settingsField(index, attr) }));
    // Engine que vem da aplicação (from app.db import engine, db.engine, get_engine()): a URL dele a trava não segue.
    // O que vem do SQLAlchemy, do SQLModel e do Alembic (create_engine, engine_from_config) não conta.
    const imported = [...envPy.matchAll(/^[ \t]*from[ \t]+([\w.]+)[ \t]+import[ \t]+(\([^)]*\)|[^\n]*)/gm)]
        .filter((m) => !/^(sqlalchemy|sqlmodel|alembic)\b/.test(m[1]))
        .flatMap((m) => m[2].replace(/#[^\n]*/g, '').replace(/[()]/g, '').split(/[,\n]/).map((n) => n.trim().split(/\s+/)[0]));
    const appEngine = imported.some((n) => /(^|_)engine$/i.test(n)) || /\.get_engine\s*\(|\bdb\.engine\b/.test(envPy);

    const names = new Set(envNames);
    for (const { field } of fields) {
        for (const part of field?.kind === 'computed' ? field.parts : field ? [field] : []) part.names.forEach((n) => names.add(n));
    }
    // .env citado no código (load_dotenv(".env.x"), env_file="../.env"), resolvido a partir da pasta do comando.
    const referenced = [...`${envPy}\n${index.text}`.matchAll(/["']([^"'\n]{1,200})["']/g)].map((m) => m[1]).filter((l) => /(^|[\\/]|\w)\.env(\.[\w.-]+)?$/.test(l));
    const files = [
        ...new Set([
            ...[...envFiles, env.UV_ENV_FILE, ...referenced].filter(Boolean).map((f) => expandPath(f, dir, home)),
            ...[dir, dirname(iniPath), scriptDir].flatMap((start) => upToProject(start, projectDir)).map((d) => join(d, '.env')),
        ]),
    ];
    const keys = new Set([...names, ...ENVIRONMENT_KEYS]);
    const values = new Map([...keys].map((k) => [k, new Set()]));
    for (const k of keys) {
        if (env[k] !== undefined) values.get(k).add(env[k]);
        if (names.has(k) && processEnv[k] !== undefined) values.get(k).add(processEnv[k]);
    }
    const targets = [];
    for (const file of files) {
        const vars = readEnvKeys(file, keys);
        if (vars === null) targets.push({ name: basename(file), host: null, local: false, why: `o arquivo ${basename(file)}` });
        else for (const k of keys) if (vars[k] !== undefined) values.get(k).add(vars[k]);
    }

    const looked = [];
    const unresolved = (name, what) => targets.push({ name, host: null, local: false, why: what });
    // Valor de URL: URL vira alvo pelo host; outro valor não vazio (variável não expandida, texto solto) é ilegível.
    const addUrl = (name, value) => {
        if (/^driver:/i.test(value)) return; // o exemplo que o `alembic init` deixa no ini
        if (/^[a-z][\w+.-]*:/i.test(value)) targets.push({ name, ...databaseHost(value) });
        else if (value) unresolved(name, `o host de ${name}`);
    };
    const addHost = (name, value) => (/[$`]/.test(value) ? unresolved(name, `o host de ${name}`) : targets.push({ name, ...databaseHost(`db://${value}`) }));
    // Fonte do banco: usa o que achar; sem valor nenhum, a trava não sabe para onde ela aponta.
    let sources = 0;
    const isUrl = (value) => /^[a-z][\w+.-]*:\/\//i.test(value);
    const source = (label, kind, found, required = true) => {
        const usable = [...found].filter(Boolean);
        looked.push(label);
        sources++;
        if (!usable.length && required) unresolved(label, `o valor de ${label}, que o env.py usa para o banco`);
        for (const value of usable) (kind === 'host' && !isUrl(value) ? addHost : addUrl)(label, value);
    };
    // Variável ou campo com outro nome (DB_CONNECTION, REDIS_URL) conta quando o valor é URL de banco; sem valor, ou com
    // outro valor (LOG_LEVEL=info, redis://), não diz nada do banco.
    const maybe = (label, found) => {
        for (const value of new Set(found)) {
            if (!DB_URL.test(value)) continue;
            looked.push(label);
            sources++;
            addUrl(label, value);
        }
    };
    const valuesOf = (list) => list.flatMap((n) => [...values.get(n)]);

    if (readsIni) {
        const name = `sqlalchemy.url do ${basename(iniPath)}`;
        looked.push(name);
        addUrl(name, ini.get(section, 'sqlalchemy.url') ?? '');
    }
    for (const name of envNames) {
        const found = [...values.get(name), ...(defaults.has(name) ? [defaults.get(name)] : [])];
        if (dbName(name)) source(name, HOSTISH.test(name) ? 'host' : 'url', found);
        else maybe(name, found);
    }
    for (const { attr, field } of fields) {
        const named = dbName(attr);
        if (!field) {
            // Campo de banco sem origem conhecida (calculado, com alias, fora da classe) ou classe que a trava não lê.
            if (named || index.defs.has(attr) || index.unsupported) unresolved(written.get(attr), `de onde vem ${written.get(attr)} (campo calculado, com alias ou fora da classe de settings)`);
        } else if (field.kind !== 'computed') {
            const found = [...valuesOf(field.names), ...(field.fallback ? [field.fallback] : [])];
            if (named) source(attr, field.kind, found);
            else maybe(attr, found);
        } else {
            // No campo calculado, a URL direta é opcional (costuma vir antes do cálculo por partes); o host, não.
            for (const part of field.parts) source(part.attr, part.kind, [...valuesOf(part.names), ...(part.fallback ? [part.fallback] : [])], part.kind === 'host');
        }
    }
    // env.py que troca a URL do ini por algo que a trava não segue (objeto de config com outro nome, engine da aplicação).
    if (replacesIni && !sources) unresolved('env.py', 'de onde o env.py tira a URL que põe no lugar da do alembic.ini');
    if (appEngine) unresolved('env.py', 'a URL do engine que o env.py importa da aplicação');
    // -x chega ao env.py: URL vale como alvo (citada só pelo host), e nome de seção do ini aponta para a URL dela.
    for (const x of xArgs) {
        const value = x.slice(x.indexOf('=') + 1);
        if (ini.has(value)) addUrl(`sqlalchemy.url da seção [${value}]`, ini.get(value, 'sqlalchemy.url') ?? '');
        else if (/^[a-z][\w+.-]*:/i.test(value)) addUrl('-x', value);
    }
    const environment = ENVIRONMENT_KEYS.flatMap((name) => [...values.get(name)].map((value) => ({ name, value }))).find((e) => PROD_ENVIRONMENT.test(e.value));
    return { targets, looked: [...new Set(looked)], environment: environment ?? null, problem: null };
}

/**
 * Pasta de onde o Prisma CLI acharia o schema ou o prisma.config.
 */
export function isPrismaDir(dir) {
    return [...PRISMA_CONFIGS, 'prisma/schema.prisma', 'schema.prisma'].some((f) => existsSync(join(dir, f)));
}

/**
 * Pasta de onde o Alembic acharia a configuração: o ini (ou o do -c) ou o [tool.alembic] do pyproject.toml.
 */
export function isAlembicDir(dir, config = null) {
    return existsSync(resolve(dir, config ?? 'alembic.ini')) || pyprojectScriptLocation(dir) !== null;
}

/**
 * Troca ${NOME}, ${NOME:-padrão}, ${NOME-padrão}, ${NOME:?erro}, ${NOME:+outro} e $NOME como o shell e o docker
 * compose trocam, com o valor de `lookup`. Se falta alguma variável sem padrão, devolve o texto como veio: com o
 * `$` no meio, ele fica ilegível para a trava, que nega.
 */
export function interpolate(value, lookup) {
    // Texto enorme a trava não troca: com o `$` no meio, fica ilegível e nega. O {0,256} evita custo quadrático.
    if (value.length > 4096) return value;
    let missing = false;
    const out = value.replace(/\$\$|\$\{([A-Za-z_]\w*)(?:(:?[-?+])([^}]{0,256}))?\}|\$([A-Za-z_]\w*)/g, (match, braced, op, arg, bare) => {
        if (match === '$$') return '$';
        const found = lookup(braced ?? bare);
        const unset = op?.startsWith(':') ? !found : found === undefined;
        if (op === ':-' || op === '-') return unset ? arg : found;
        if (op === ':+' || op === '+') return unset ? '' : arg;
        if (found === undefined) {
            missing = true;
            return match;
        }
        return found;
    });
    return missing ? value : out;
}

/**
 * Tira o comentário de uma linha de YAML: # no começo ou depois de espaço, fora de aspas.
 */
function stripYamlComment(line) {
    let quote = null;
    for (let k = 0; k < line.length; k++) {
        const c = line[k];
        if (quote) {
            if (c === quote) quote = null;
        } else if (c === '"' || c === "'") {
            if (k === 0 || /[\s:[{,-]/.test(line[k - 1])) quote = c;
        } else if (c === '#' && (k === 0 || /\s/.test(line[k - 1]))) return line.slice(0, k);
    }
    return line;
}

function yamlScalar(text) {
    const value = text.trim();
    if (value[0] === '"') return value.slice(1, value.lastIndexOf('"') > 0 ? value.lastIndexOf('"') : undefined).replace(/\\(.)/g, '$1');
    if (value[0] === "'") return value.slice(1, value.lastIndexOf("'") > 0 ? value.lastIndexOf("'") : undefined).replace(/''/g, "'");
    return value;
}

/**
 * Chave de mapa no começo da linha: entre aspas, ou até o primeiro `:` seguido de espaço ou fim. Sem regex, para
 * linha enorme não custar tempo quadrático.
 * @returns {{ key: string, rest: string }|null}
 */
function yamlKey(text) {
    let end = -1;
    if (text[0] === '"' || text[0] === "'") {
        const close = text.indexOf(text[0], 1);
        if (close === -1) return null;
        end = close + 1;
        while (text[end] === ' ' || text[end] === '\t') end++;
        if (text[end] !== ':') return null;
    } else {
        if (!text || /[\s#]/.test(text[0])) return null;
        for (let k = 0; k < text.length && end === -1; k++) {
            if (text[k] === ':' && (k + 1 === text.length || text[k + 1] === ' ' || text[k + 1] === '\t')) end = k;
        }
        if (end === -1) return null;
    }
    const after = text.slice(end + 1);
    if (after && after[0] !== ' ' && after[0] !== '\t') return null;
    return { key: yamlScalar(text.slice(0, end)), rest: after.trim() };
}

/**
 * Só o YAML em bloco que o docker compose usa: mapa, lista, escalar com ou sem aspas e lista entre colchetes numa
 * linha. Âncora, alias, merge (<<), tag e mapa entre chaves lançam erro, e quem chama volta ao que fazia antes.
 */
function parseYaml(text) {
    const lines = [];
    for (const raw of text.split('\n')) {
        const body = stripYamlComment(raw).trimEnd();
        const indent = body.search(/\S/);
        if (indent === -1 || body.trim() === '---') continue;
        if (body.slice(0, indent).includes('\t')) throw new Error('tab na indentação');
        lines.push({ indent, text: body.slice(indent) });
    }
    let i = 0;
    const isItem = (t) => t === '-' || t.startsWith('- ');
    const value = (rest, indent) => {
        if (/^[&*!]/.test(rest) || rest.startsWith('{')) throw new Error('YAML que a trava não lê');
        if (rest.startsWith('[')) {
            if (!rest.endsWith(']') || /[[{]/.test(rest.slice(1))) throw new Error('lista que a trava não lê');
            return rest.slice(1, -1).split(',').map((s) => yamlScalar(s)).filter((s) => s !== '');
        }
        if (/^[|>]/.test(rest)) {
            const parts = [];
            while (i < lines.length && lines[i].indent > indent) parts.push(lines[i++].text);
            return parts.join('\n');
        }
        return yamlScalar(rest);
    };
    const mapping = (indent) => {
        const map = Object.create(null);
        while (i < lines.length && lines[i].indent === indent && !isItem(lines[i].text)) {
            const entry = yamlKey(lines[i].text);
            if (!entry) throw new Error('linha que a trava não lê');
            if (entry.key === '<<') throw new Error('merge de YAML');
            i++;
            if (entry.rest) map[entry.key] = value(entry.rest, indent);
            else if (i < lines.length && (lines[i].indent > indent || (lines[i].indent === indent && isItem(lines[i].text)))) map[entry.key] = block(lines[i].indent);
            else map[entry.key] = null;
        }
        return map;
    };
    const block = (indent) => {
        if (!isItem(lines[i].text)) return mapping(indent);
        const list = [];
        while (i < lines.length && lines[i].indent === indent && isItem(lines[i].text)) {
            const rest = lines[i].text.slice(1).trimStart();
            if (!rest) {
                i++;
                list.push(i < lines.length && lines[i].indent > indent ? block(lines[i].indent) : null);
            } else if (!/^["']/.test(rest) && yamlKey(rest)) {
                // Mapa dentro da lista: a primeira chave está na linha do traço.
                const column = indent + lines[i].text.length - rest.length;
                lines[i] = { indent: column, text: rest };
                list.push(mapping(column));
            } else {
                i++;
                list.push(value(rest, indent));
            }
        }
        return list;
    };
    const doc = lines.length ? block(lines[0].indent) : null;
    if (i < lines.length) throw new Error('indentação que a trava não lê');
    return doc;
}

// Na ordem em que o docker compose v2 procura: o docker-compose.yml vem antes do docker-compose.yaml.
const COMPOSE_FILES = ['compose.yaml', 'compose.yml', 'docker-compose.yml', 'docker-compose.yaml'];
const COMPOSE_OVERRIDES = ['compose.override.yaml', 'compose.override.yml', 'docker-compose.override.yml', 'docker-compose.override.yaml'];

/**
 * O serviço do docker compose como a trava precisa dele: de que pastas do disco saem os arquivos de dentro do
 * contêiner, e que ambiente ele recebe. Os arquivos são os do -f, os do COMPOSE_FILE (do shell ou do .env da pasta
 * do projeto) ou o compose.yaml (e o override) da pasta do projeto ou de cima dela, até a raiz do projeto.
 * As pastas candidatas vêm do volume que cobre o working_dir (ou o -w, ou o WORKDIR do Dockerfile), do contexto do
 * build e dos volumes do serviço; quem usa confere todas em que a ferramenta acha a própria configuração.
 * `env` é o ambiente de quem roda o `docker compose` (atribuição na frente incluída): serve para o ${VAR} do
 * compose.yaml, não entra no contêiner. `envFiles` volta com o que vale mais primeiro (no compose, o último).
 * null quando não há arquivo, o serviço não está nele ou o YAML tem algo que a trava não lê (extends, âncora).
 * @returns {{ dirs: string[], env: object, envFiles: string[] }|null}
 */
export function composeService(start, compose, { env = {}, processEnv = {}, projectDir = start, home = homedir() } = {}) {
    const shellEnv = (name) => env[name] ?? processEnv[name];
    const projectBase = compose.projectDirectory ? expandPath(compose.projectDirectory, start, home) : start;
    let files = compose.files.map((f) => expandPath(f, start, home));
    const composeFile = shellEnv('COMPOSE_FILE') ?? readEnvKeys(join(projectBase, '.env'), ['COMPOSE_FILE'])?.COMPOSE_FILE;
    if (!files.length && composeFile) files = composeFile.split(/[:;]/).filter(Boolean).map((f) => expandPath(f, projectBase, home));
    if (!files.length) {
        const dir = upToProject(projectBase, projectDir).find((d) => COMPOSE_FILES.some((f) => existsSync(join(d, f))));
        if (!dir) return null;
        files = [join(dir, COMPOSE_FILES.find((f) => existsSync(join(dir, f)))), ...COMPOSE_OVERRIDES.map((f) => join(dir, f)).filter((f) => existsSync(f)).slice(0, 1)];
    }
    const base = compose.projectDirectory ? projectBase : dirname(files[0]);

    // O compose junta os arquivos: build e working_dir do último, volume trocado pelo destino, env_file somado,
    // environment chave por chave.
    const merged = { build: null, workingDir: null, volumes: new Map(), envFiles: [], environment: Object.create(null) };
    let found = false;
    for (const file of files) {
        const text = readText(file);
        if (!text) return null;
        let doc;
        try {
            doc = parseYaml(text);
        } catch {
            return null;
        }
        const service = doc?.services?.[compose.service];
        if (service === undefined || service === null) continue;
        if (typeof service !== 'object' || Array.isArray(service) || 'extends' in service) return null;
        found = true;
        if ('build' in service) merged.build = service.build;
        if (service.working_dir) merged.workingDir = String(service.working_dir);
        for (const volume of Array.isArray(service.volumes) ? service.volumes : []) {
            const target = typeof volume === 'string' ? volume.split(':')[1] : volume?.target;
            merged.volumes.set(target ?? Symbol('volume'), volume);
        }
        const envFiles = service.env_file === undefined || service.env_file === null ? [] : [service.env_file].flat();
        merged.envFiles.push(...envFiles.map((f) => (typeof f === 'object' && f !== null ? f.path : f)).filter((f) => typeof f === 'string'));
        const environment = service.environment;
        if (Array.isArray(environment)) {
            for (const entry of environment) {
                const eq = String(entry).indexOf('=');
                merged.environment[eq === -1 ? String(entry) : String(entry).slice(0, eq)] = eq === -1 ? null : String(entry).slice(eq + 1);
            }
        } else if (environment && typeof environment === 'object') {
            for (const [key, v] of Object.entries(environment)) merged.environment[key] = v === null ? null : String(v);
        }
    }
    if (!found) return null;

    // A troca de ${VAR} usa o shell e o .env da pasta do projeto do compose (ou o --env-file dele).
    const names = [...Object.entries(merged.environment)].flatMap(([key, v]) => (v === null ? [key] : [...v.matchAll(/\$\{?([A-Za-z_]\w*)/g)].map((m) => m[1])));
    const dotEnv = {};
    for (const file of compose.envFiles.length ? compose.envFiles.map((f) => expandPath(f, start, home)) : [join(base, '.env')]) {
        Object.assign(dotEnv, readEnvKeys(file, names) ?? {});
    }
    const lookup = (name) => shellEnv(name) ?? dotEnv[name];
    const environment = {};
    for (const [key, v] of Object.entries(merged.environment)) {
        const resolved = v === null ? lookup(key) : interpolate(v, lookup);
        if (resolved !== undefined) environment[key] = resolved;
    }

    const bind = (v) => {
        const [source, target] = typeof v === 'string' ? v.split(':') : [v?.source, v?.target];
        return typeof source === 'string' && typeof target === 'string' && /^[.~/]/.test(source) && !source.includes('$') ? { source: expandPath(source, base, home), target } : null;
    };
    const binds = [...merged.volumes.values()].map(bind).filter(Boolean);
    // Volume de arquivo por cima de um arquivo de variável de dentro (./deploy/prod.env:/app/.env) é esse arquivo lá dentro.
    const isFile = (path) => {
        try {
            return statSync(path).isFile();
        } catch {
            return false;
        }
    };
    const mountedEnvFiles = binds.filter((b) => isFile(b.source) && /^\.env|\.env(\.|$)/.test(basename(b.target))).map((b) => b.source);
    const context = typeof merged.build === 'string' ? merged.build : (merged.build?.context ?? (merged.build ? '.' : null));
    const contextDir = typeof context === 'string' && !/:\/\/|^git@|\$/.test(context) ? expandPath(context, base, home) : null;
    let workdir = compose.workdir ?? merged.workingDir;
    if (!workdir && contextDir) {
        // Sem working_dir, a pasta de dentro é o último WORKDIR absoluto do Dockerfile.
        const dockerfile = typeof merged.build?.dockerfile === 'string' ? merged.build.dockerfile : 'Dockerfile';
        const text = readText(resolve(contextDir, dockerfile)) ?? '';
        workdir = [...text.matchAll(/^[ \t]*WORKDIR[ \t]+(\S+)/gim)].map((m) => m[1]).filter((w) => w.startsWith('/') && !w.includes('$')).at(-1) ?? null;
    }
    const dirs = [];
    if (workdir) {
        const cover = binds.filter((b) => workdir === b.target || workdir.startsWith(`${b.target.replace(/\/$/, '')}/`)).sort((a, b) => b.target.length - a.target.length)[0];
        if (cover) dirs.push(join(cover.source, workdir.slice(cover.target.length)));
    }
    if (contextDir) dirs.push(contextDir);
    dirs.push(...binds.filter((b) => !isFile(b.source)).map((b) => b.source));
    return {
        dirs: [...new Set(dirs)],
        env: environment,
        envFiles: [...mountedEnvFiles, ...merged.envFiles.filter((f) => !f.includes('$')).map((f) => expandPath(f, base, home)).reverse()],
    };
}

/**
 * As chaves pedidas, lidas dos arquivos na ordem dada (o primeiro que tem a chave vale); `unreadable` é o primeiro
 * arquivo que existe mas a trava não lê.
 */
export function readEnvFiles(files, keys) {
    const vars = {};
    for (const file of files) {
        const found = readEnvKeys(file, keys);
        if (found === null) return { vars, unreadable: basename(file) };
        for (const k of keys) if (vars[k] === undefined && found[k] !== undefined) vars[k] = found[k];
    }
    return { vars, unreadable: null };
}

/**
 * O package.json que o npm usaria a partir de `dir`: o mais próximo subindo as pastas.
 */
export function findPackageScripts(dir) {
    for (let current = resolve(dir); ; current = dirname(current)) {
        const file = join(current, 'package.json');
        if (existsSync(file)) {
            try {
                const text = readText(file);
                return text ? { dir: current, scripts: JSON.parse(text).scripts ?? {} } : null;
            } catch {
                return null;
            }
        }
        if (dirname(current) === current) return null;
    }
}

export function makeContext({ cwd, projectDir, config, home, env }) {
    const root = resolve(projectDir ?? cwd ?? process.cwd());
    return {
        cwd: resolve(cwd ?? root),
        projectDir: root,
        home: home ?? homedir(),
        config: config ?? loadConfig(root),
        // Ambiente herdado pelo hook: a URL de banco exportada no terminal que abriu o agente também conta.
        processEnv: env ?? process.env,
    };
}

// ---------------------------------------------------------------------------
// Caminhos
// ---------------------------------------------------------------------------

export function expandPath(path, cwd, home) {
    if (path === '~') return home;
    if (path.startsWith('~/')) return join(home, path.slice(2));
    if (isAbsolute(path)) return resolve(path);
    return resolve(cwd, path);
}

export function isInside(child, parent) {
    const rel = relative(parent, child);
    return rel !== '' && !rel.startsWith('..') && !isAbsolute(rel);
}

/**
 * Arquivo cujo conteúdo é segredo: .env, chave, credencial de nuvem e de ferramenta.
 */
export function isSecretPath(path, ctx, cwd = ctx.cwd) {
    const abs = expandPath(path, cwd, ctx.home);
    const base = basename(abs).toLowerCase();

    // Padrão entre aspas ('*.env' no find -name, no -g do rg, no .gitignore) não é arquivo: o shell não expandiu.
    if (/[*?[\]{}]/.test(base)) return false;
    if (/^\.env(\..+)?$/.test(base) || base === '.envrc') return !/\.(example|sample|dist|template|defaults)$/.test(base);
    // Sufixo .env (prod.env, deploy.env: o env_file do docker compose, e prod.env.local, prod.env.bak) e .env-prod, .env_prod.
    if (/^.+\.env([.~].*)?$/.test(base) || /^\.env[-_].+$/.test(base)) return !/(^|[._-])(example|exemplo|sample|modelo|dist|template|defaults)([._-]|$)/.test(base);
    if (/^id_(rsa|dsa|ecdsa|ed25519)(_sk)?$/.test(base)) return true;
    if (/\.(pem|key|p12|pfx|jks|keystore|kdbx|ppk)$/.test(base)) return true;
    if (['.netrc', '_netrc', '.pgpass', '.my.cnf', '.git-credentials', 'credentials.json', '.htpasswd'].includes(base)) return true;
    if (/^service-account.*\.json$/.test(base)) return true;
    if (abs === join(ctx.projectDir, 'auth.json')) return true;

    const rel = relative(ctx.home, abs).split(sep).join('/');
    if (rel.startsWith('..') || isAbsolute(rel)) return false;
    if (['.ssh', '.aws', '.gnupg'].includes(rel)) return true;
    if (rel.startsWith('.ssh/')) return !/(\.pub|known_hosts[^/]*|\/config|authorized_keys)$/.test(rel);
    if (rel.startsWith('.gnupg/') || rel.startsWith('Library/Keychains/')) return true;
    return [
        '.aws/credentials',
        '.config/gh/hosts.yml',
        '.docker/config.json',
        '.kube/config',
        '.npmrc',
        '.pypirc',
        '.composer/auth.json',
        '.config/composer/auth.json',
        '.codex/auth.json',
        '.claude/.credentials.json',
        '.config/gcloud/credentials.db',
        '.config/gcloud/application_default_credentials.json',
    ].includes(rel);
}

export function isGuardFile(path, ctx, cwd = ctx.cwd) {
    const abs = expandPath(path, cwd, ctx.home);
    return GUARD_FILES.some((f) => {
        const guarded = join(ctx.projectDir, f);
        return abs === guarded || isInside(abs, guarded);
    });
}

/**
 * Glob como o shell e o rg leem: *, ?, [abc], [!abc] e {a,b} (o bash expande a chave antes do grep, o rg lê direto).
 * `star` é o que o * casa: sem barra (padrão) ou qualquer coisa (o fnmatch do grep do macOS no caminho inteiro).
 * Faixa inválida ([z-a]) vale como texto, como no fnmatch: não casa nome nenhum.
 */
export function globToRegExp(glob, star = '[^/]*') {
    const convert = (text) => {
        let out = '';
        for (let k = 0; k < text.length; k++) {
            const c = text[k];
            if (c === '*') out += star;
            else if (c === '?') out += star === '[^/]*' ? '[^/]' : '.';
            else if (c === '[') {
                const end = text.indexOf(']', k + 2);
                const set = end === -1 ? null : `[${text.slice(k + 1, end).replace(/^!/, '^').replace(/\\/g, '\\\\')}]`;
                let valid = set !== null;
                try {
                    if (valid) new RegExp(set);
                } catch {
                    valid = false;
                }
                if (!valid) out += '\\[';
                else {
                    out += set;
                    k = end;
                }
            } else if (c === '{') {
                // Acha o } do mesmo nível e separa as vírgulas desse nível.
                let depth = 0;
                let end = -1;
                const parts = [];
                let from = k + 1;
                for (let j = k + 1; j < text.length && end === -1; j++) {
                    if (text[j] === '{') depth++;
                    else if (text[j] === '}' && depth > 0) depth--;
                    else if (text[j] === '}') end = j;
                    else if (text[j] === ',' && depth === 0) {
                        parts.push(text.slice(from, j));
                        from = j + 1;
                    }
                }
                if (end === -1 || !parts.length) out += '\\{';
                else {
                    parts.push(text.slice(from, end));
                    out += `(?:${parts.map(convert).join('|')})`;
                    k = end;
                }
            } else out += c.replace(/[.+^${}()|\\]/g, '\\$&');
        }
        return out;
    };
    return new RegExp(`^${convert(glob)}$`);
}

/**
 * Expande um glob simples contra o disco, como o shell faria (sem ponto inicial, a menos que o padrão comece com ponto).
 */
export function expandGlob(word, cwd, home) {
    const abs = expandPath(word, cwd, home);
    const dir = dirname(abs);
    const pattern = basename(abs);
    if (/[*?[]/.test(dir)) return null;
    let entries;
    try {
        entries = readdirSync(dir);
    } catch {
        return [];
    }
    const regex = globToRegExp(pattern);
    return entries.filter((e) => (pattern.startsWith('.') || !e.startsWith('.')) && regex.test(e)).map((e) => join(dir, e));
}

// Nomes de arquivo de segredo para testar um filtro (glob da ferramenta Grep, -name do find): se ele casa algum, a
// busca lê segredo.
export const SECRET_SAMPLES = ['.env', '.env.local', '.env.production', 'x.env', '.env-prod', 'id_rsa', 'server.pem', 'server.key'];

// Dependência, histórico e cache de ferramenta: não guardam segredo do projeto, e o .venv sozinho passa do teto.
const SKIPPED_DIRS = new Set(['node_modules', 'vendor', '.git', '.venv', 'venv', '__pycache__', '.tox', '.mypy_cache', '.pytest_cache', '.next', '.nuxt', '.turbo']);

/**
 * Procura arquivo de segredo dentro de uma pasta, como um grep recursivo leria.
 * `filter.reads(nome, caminho)` diz se a busca lê o arquivo, e `filter.skipsDir(nome)` se ela pula a pasta
 * (--include, --exclude e --exclude-dir do grep, -g do rg). Devolve 'grande' quando passa do teto de entradas:
 * a trava não conferiu tudo, e quem chama nega com esse motivo.
 */
export function treeHasSecret(dir, ctx, budget = { left: 20000 }, filter = {}) {
    let entries;
    try {
        entries = readdirSync(dir, { withFileTypes: true });
    } catch {
        return false;
    }
    for (const entry of entries) {
        if (--budget.left <= 0) return 'grande';
        const full = join(dir, entry.name);
        if (entry.isDirectory()) {
            if (SKIPPED_DIRS.has(entry.name) || filter.skipsDir?.(entry.name)) continue;
            const found = treeHasSecret(full, ctx, budget, filter);
            if (found) return found;
        } else if (isSecretPath(full, ctx, dir) && (filter.reads?.(entry.name, full) ?? true)) return true;
    }
    return false;
}
