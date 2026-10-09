<?php

namespace App\Platform\Services\Concerns;

use Closure;
use Illuminate\Database\DeadlockException;
use Illuminate\Support\Facades\DB;
use PDOException;

/**
 * Escrita que trava linha de usuário repete a transação em deadlock (CR-3-03): a trava dos admins
 * ativos vai em ordem de índice e a FK da auditoria trava o ator depois do alvo. Só o deadlock
 * (1213) repete. O DB::transaction(..., attempts) do Laravel repetiria também o "Lock wait
 * timeout" (1205): até 3 × 50 s segurando a trava dos admins, com a tela recebendo 504 e a
 * escrita commitando depois (CR-4-04). O 1205 sobe na hora, sem repetir.
 *
 * O callback precisa ser repetível: instância em memória salva dentro dele é restaurada no início
 * (ou relida), e nada fora do banco (e-mail) acontece dentro dele.
 */
trait RetriesDeadlock
{
    private const ATTEMPTS = 3;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function transactionRetryingDeadlock(Closure $callback): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (PDOException $e) {
                // O deadlock de uma transação aninhada (o deleteIfUnused) sobe como DeadlockException,
                // com o erro do MySQL dentro. Aberta por quem chamou, a transação não se repete aqui:
                // o MySQL já a desfez inteira, e quem a abriu é que repete.
                $code = ($e instanceof DeadlockException ? $e->getPrevious() : $e)?->errorInfo[1] ?? null;
                if ($code !== 1213 || $attempt >= self::ATTEMPTS || DB::transactionLevel() > 0) {
                    throw $e;
                }
            }
        }
    }
}
