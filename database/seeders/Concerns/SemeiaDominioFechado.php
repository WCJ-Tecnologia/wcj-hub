<?php

namespace Database\Seeders\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Domínio fechado (docs/banco.md, seção 0): ids fixos, semeados, sem tela. Idempotente:
 * rodar de novo só confirma os valores.
 */
trait SemeiaDominioFechado
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<int, array<string, mixed>>  $linhas  id => colunas
     */
    protected function semear(string $model, array $linhas): void
    {
        foreach ($linhas as $id => $colunas) {
            $model::query()->updateOrCreate(['id' => $id], $colunas);
        }
    }
}
