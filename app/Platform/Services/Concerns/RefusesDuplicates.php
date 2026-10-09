<?php

namespace App\Platform\Services\Concerns;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Um jeito só de capturar o unique do banco (CR-2-11). Duas gravações do mesmo valor ao mesmo
 * tempo passam as duas pela checagem de repetido; o unique barra a segunda, que volta como o
 * erro do campo (CR-1-02), não como 500. Quem decide se o unique violado é o esperado é a
 * $refusal: devolve a ValidationException do campo, ou null para relançar a exceção original
 * (um unique que ninguém previu não vira mensagem errada).
 */
trait RefusesDuplicates
{
    /**
     * @template T
     *
     * @param  Closure(): T  $write
     * @param  Closure(UniqueConstraintViolationException): ?ValidationException  $refusal
     * @return T
     */
    protected function writeOrRefuseDuplicate(Closure $write, Closure $refusal): mixed
    {
        try {
            return $write();
        } catch (UniqueConstraintViolationException $e) {
            throw $refusal($e) ?? $e;
        }
    }
}
