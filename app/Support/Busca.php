<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * O texto da busca de uma lista (`?search=`), sem as pontas em branco e com teto de 200
 * caracteres, o mesmo do quadro e dos indicadores (B9, SEC-10). Acima do teto a busca é
 * ignorada, como filtro inválido: a lista volta sem filtro de texto, sem 422.
 */
final class Busca
{
    public const MAX = 200;

    public static function termo(Request $request): string
    {
        $termo = trim((string) $request->query('search', ''));

        return mb_strlen($termo) > self::MAX ? '' : $termo;
    }
}
