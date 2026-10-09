<?php

namespace Tests\Support;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Prova de trava e corrida com intercalação forçada (skill wcj-testes,
 * references/banco-real.md). Só faz sentido no banco de verdade: o SQLite não tem trava
 * de linha. Use com DatabaseTruncation, nunca com RefreshDatabase: a outra conexão só
 * enxerga dado commitado. E limpe também no tearDown ($this->limparOQueFicou()): o
 * DatabaseTruncation só limpa antes, e o próximo teste herdaria o que ficou.
 */
final class Concorrencia
{
    private const CONEXAO = 'wcj_outra_requisicao';

    // "Linha travada": MySQL 8 (3572 NOWAIT, 1205 espera), deadlock (1213), Postgres (55P03, 40P01).
    private const CODIGOS_DE_TRAVA = [3572, 1205, 1213];

    /**
     * Uma segunda conexão tenta pegar a linha com FOR UPDATE NOWAIT. Devolve true se a
     * linha estava travada por outra transação neste instante.
     */
    public static function linhaEstaTravada(string $tabela, int|string $id, string $chave = 'id'): bool
    {
        $outra = self::outraConexao();
        $outra->beginTransaction();
        try {
            $tabelaSql = $outra->getQueryGrammar()->wrapTable($tabela);
            $chaveSql = $outra->getQueryGrammar()->wrap($chave);
            $outra->select("select {$chaveSql} from {$tabelaSql} where {$chaveSql} = ? for update nowait", [$id]);

            return false;
        } catch (QueryException $e) {
            if (self::ehTrava($e)) {
                return true;
            }
            throw $e;
        } finally {
            $outra->rollBack();
            DB::purge(self::CONEXAO);
        }
    }

    /**
     * Corrida determinística: quando a conexão principal executar a consulta que casa com
     * $gatilho (a checagem), $outraRequisicao roda inteira ali, noutra conexão, antes de a
     * principal gravar. Se a principal trava o que checou, a outra espera e cai por timeout
     * (2 s): "bloqueada". Se não trava, a outra passa e o invariante do teste quebra.
     *
     * Passe o $teste quando a outra requisição for HTTP ($this->put(...)): sem isso o Laravel
     * transforma o erro de trava num 500 e não dá para saber por que ela caiu. Depois de
     * rodar a outra, o usuário logado e a requisição corrente voltam a ser os da principal.
     *
     * No teste, confira sempre: $corrida->rodou (o gatilho casou) e $corrida->outroErro === null
     * (a outra caiu por trava ou passou; qualquer outra queda, como 403 ou 419, invalida a prova).
     *
     * "bloqueada" aceita espera e deadlock juntos; $corrida->codigo diz qual foi (no MySQL, 1205
     * espera, 1213 deadlock, 3572 NOWAIT). Mesmo assim, deadlock de verdade não se forma aqui: a
     * principal fica parada no PHP enquanto a outra roda. Ordem de trava se prova pela sequência
     * de consultas "for update" da principal, não por este teste.
     *
     * @param  Closure(QueryExecuted): bool  $gatilho
     * @return object{rodou: bool, bloqueada: bool, codigo: int|string|null, retorno: mixed, outroErro: ?string}
     */
    public static function intercalarDepoisDe(Closure $gatilho, Closure $outraRequisicao, ?TestCase $teste = null): object
    {
        $resultado = (object) ['rodou' => false, 'bloqueada' => false, 'codigo' => null, 'retorno' => null, 'outroErro' => null];
        $principal = DB::getDefaultConnection();

        DB::connection($principal)->listen(function (QueryExecuted $consulta) use ($gatilho, $outraRequisicao, $resultado, $principal, $teste): void {
            if ($resultado->rodou || $consulta->connectionName !== $principal || ! $gatilho($consulta)) {
                return;
            }
            $resultado->rodou = true;
            $usuario = Auth::user();
            $requisicao = app('request');
            self::outraConexao();
            DB::setDefaultConnection(self::CONEXAO);
            $teste?->withoutExceptionHandling();
            try {
                $resultado->retorno = $outraRequisicao();
                self::conferirResposta($resultado);
            } catch (QueryException $e) {
                $resultado->bloqueada = self::ehTrava($e);
                $resultado->codigo = self::codigo($e);
                if (! $resultado->bloqueada) {
                    $resultado->outroErro = $e->getMessage();
                }
            } catch (Throwable $e) {
                $resultado->outroErro = $e::class.': '.$e->getMessage();
            } finally {
                $teste?->withExceptionHandling();
                DB::setDefaultConnection($principal);
                DB::purge(self::CONEXAO);
                app()->instance('request', $requisicao);
                $usuario !== null ? Auth::setUser($usuario) : Auth::forgetUser();
            }
        });

        return $resultado;
    }

    /** A outra requisição devolveu HTTP de erro sem exceção (sem o $teste): registra. */
    private static function conferirResposta(object $resultado): void
    {
        $status = is_object($resultado->retorno) && method_exists($resultado->retorno, 'getStatusCode')
            ? $resultado->retorno->getStatusCode() : null;
        if ($status !== null && $status >= 400) {
            $resultado->outroErro = "a outra requisição respondeu HTTP {$status} (passe \$this como 3º argumento para ver a causa)";
        }
    }

    private static function outraConexao(): Connection
    {
        $padrao = config('database.default');
        config(['database.connections.'.self::CONEXAO => config("database.connections.{$padrao}")]);
        $conexao = DB::connection(self::CONEXAO);
        match ($conexao->getDriverName()) {
            'mysql', 'mariadb' => $conexao->statement('set session innodb_lock_wait_timeout = 2'),
            'pgsql' => $conexao->statement("set lock_timeout = '2s'"),
            default => null,
        };

        return $conexao;
    }

    private static function ehTrava(QueryException $e): bool
    {
        return in_array((int) ($e->errorInfo[1] ?? 0), self::CODIGOS_DE_TRAVA, true)
            || in_array($e->errorInfo[0] ?? '', ['55P03', '40P01'], true);
    }

    /** O código do motor (MySQL: 1205, 1213…) ou, sem ele, o SQLSTATE (Postgres). */
    private static function codigo(QueryException $e): int|string|null
    {
        return isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : ($e->errorInfo[0] ?? null);
    }
}
