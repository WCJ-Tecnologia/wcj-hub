<?php

namespace Tests\Support;

use PDO;
use RuntimeException;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Container\StartedGenericContainer;
use Testcontainers\Modules\MariaDBContainer;
use Testcontainers\Modules\MySQLContainer;
use Testcontainers\Modules\PostgresContainer;
use Testcontainers\Wait\WaitForExec;

/**
 * Banco de verdade nos testes (skill wcj-testes, references/banco-real.md).
 *
 * Com DB_TESTE=container, o tests/bootstrap.php chama subir(): um container descartável
 * na MESMA imagem da produção (DB_TESTE_IMAGEM) recebe a suíte inteira, e as variáveis
 * DB_* apontam para ele antes de o Laravel subir. O container morre no fim da rodada.
 */
final class BancoDeVerdade
{
    // Versão exata da produção: a fonte é o docker-compose.yml / DEPLOY.md do projeto.
    public const IMAGEM_PADRAO = 'mysql:8.4';

    private static ?StartedGenericContainer $container = null;

    public static function subir(): void
    {
        // Com a config em cache, o Laravel ignora as variáveis abaixo e roda (e trunca) o banco
        // do .env. Melhor parar aqui do que apagar o banco de alguém.
        if (is_file(__DIR__.'/../../bootstrap/cache/config.php')) {
            throw new RuntimeException('DB_TESTE=container com a config em cache: rode php artisan config:clear antes (senão os testes usariam o banco do .env).');
        }
        self::apontarDocker();

        $imagem = getenv('DB_TESTE_IMAGEM') ?: self::IMAGEM_PADRAO;
        [$nome, $versao] = self::imagemOficial($imagem);

        // Espera generosa: no CI frio a imagem ainda está sendo baixada. Trava de 2 s no
        // próprio servidor: no teste de corrida, quem espera a trava cai em vez de pendurar.
        [$container, $conexao, $usuario, $senha, $dados, $consulta] = match ($nome) {
            'mariadb' => [(new MariaDBContainer($versao, 'root'))->withMariaDBDatabase('testing')
                ->withWait(new WaitForExec(['sh', '-c', 'mariadb-admin ping -h 127.0.0.1 || mysqladmin ping -h 127.0.0.1'], null, 60000))
                ->withCommand(['--innodb-lock-wait-timeout=2']), 'mariadb', 'root', 'root', '/var/lib/mysql', 'select version()'],
            'mysql' => [(new MySQLContainer($versao, 'root'))->withMySQLDatabase('testing')
                ->withWait(new WaitForExec(['mysqladmin', 'ping', '-h', '127.0.0.1'], null, 60000))
                ->withCommand(['--innodb-lock-wait-timeout=2']), 'mysql', 'root', 'root', '/var/lib/mysql', 'select version()'],
            'postgres' => [(new PostgresContainer($versao, 'testing', 'testing', 'testing'))
                ->withWait(new WaitForExec(['pg_isready', '-h', '127.0.0.1', '-U', 'testing'], null, 60000))
                ->withCommand(['-c', 'lock_timeout=2000']), 'pgsql', 'testing', 'testing', '/var/lib/postgresql/data', 'show server_version'],
        };

        // Dados em memória: a suíte roda mais rápido e nada fica no disco da VM.
        // O rótulo deixa achar e apagar container esquecido (processo morto no meio).
        /** @var GenericContainer $container */
        self::$container = $container
            ->withTmpfs($dados, 'rw')
            ->withLabels(['wcj-testes' => 'banco-de-verdade'])
            ->start();

        register_shutdown_function(static function (): void {
            self::$container?->stop();
        });
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            foreach ([SIGINT, SIGTERM] as $sinal) {
                pcntl_signal($sinal, static fn () => exit(130)); // Ctrl-C passa pelo shutdown: o container morre
            }
        }

        $porta = (string) self::$container->getFirstMappedPort();
        self::definir([
            'DB_CONNECTION' => $conexao,
            // "localhost" faz o PDO do MySQL tentar o socket Unix, não a porta mapeada.
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => $porta,
            'DB_SOCKET' => '',
            'DB_DATABASE' => 'testing',
            'DB_USERNAME' => $usuario,
            'DB_PASSWORD' => $senha,
            'DB_URL' => '',
            'DB_TESTE_MOTOR' => $conexao,
        ]);

        self::gravarEvidencia($imagem, $conexao, $porta, $usuario, $senha, $consulta);
    }

    public static function ativo(): bool
    {
        return self::$container !== null;
    }

    /**
     * Só imagem oficial: a biblioteca monta "mysql:<versão>" por conta própria, então
     * "bitnami/mysql:8.4" viraria "mysql:8.4" sem aviso.
     *
     * @return array{0: string, 1: string}
     */
    private static function imagemOficial(string $imagem): array
    {
        if (! preg_match('#^(?:docker\.io/)?(?:library/)?(mysql|mariadb|postgres):([\w.\-]+)$#', $imagem, $m)) {
            throw new RuntimeException("DB_TESTE_IMAGEM={$imagem}: use a imagem oficial com a versão da produção (mysql:8.4, mariadb:11.4, postgres:17-alpine).");
        }

        return [$m[1], $m[2]];
    }

    /** Prova de que a suíte rodou no motor real: imagem e versão, lidas do próprio banco. */
    private static function gravarEvidencia(string $imagem, string $conexao, string $porta, string $usuario, string $senha, string $consulta): void
    {
        $arquivo = getenv('WCJ_BANCO_EVIDENCIA');
        if (! is_string($arquivo) || $arquivo === '') {
            return;
        }
        $driver = $conexao === 'pgsql' ? 'pgsql' : 'mysql';
        $pdo = new PDO("{$driver}:host=127.0.0.1;port={$porta};dbname=testing", $usuario, $senha);
        file_put_contents($arquivo, json_encode([
            'imagem' => $imagem,
            'motor' => $conexao,
            'versao' => (string) $pdo->query($consulta)->fetchColumn(),
        ], JSON_UNESCAPED_SLASHES));
    }

    /**
     * A biblioteca PHP usa tcp://127.0.0.1:2375 quando DOCKER_HOST não está definido, e
     * nenhum Docker de Mac escuta lá. Procura o socket dos mais comuns.
     */
    private static function apontarDocker(): void
    {
        if (getenv('DOCKER_HOST')) {
            return;
        }
        $casa = getenv('HOME') ?: '';
        foreach ([
            "{$casa}/.colima/default/docker.sock",
            "{$casa}/.docker/run/docker.sock",
            "{$casa}/.orbstack/run/docker.sock",
            "{$casa}/.rd/docker.sock",
            '/var/run/docker.sock',
        ] as $socket) {
            if (file_exists($socket)) {
                putenv("DOCKER_HOST=unix://{$socket}");

                return;
            }
        }
        throw new RuntimeException('DB_TESTE=container, mas nenhum Docker foi encontrado. Suba o Docker (Colima, Docker Desktop, OrbStack) ou defina DOCKER_HOST.');
    }

    /** @param  array<string, string>  $variaveis */
    private static function definir(array $variaveis): void
    {
        // Vale para getenv, $_ENV e $_SERVER: o Laravel lê os três, e o phpunit.xml já
        // tinha posto DB_CONNECTION=sqlite antes deste bootstrap.
        foreach ($variaveis as $nome => $valor) {
            putenv("{$nome}={$valor}");
            $_ENV[$nome] = $valor;
            $_SERVER[$nome] = $valor;
        }
    }
}
