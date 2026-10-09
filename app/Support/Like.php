<?php

namespace App\Support;

/**
 * Termo de busca para LIKE (CR-1-18): o que a pessoa digita é texto, nunca curinga. Escapa
 * "\", "%" e "_" (o "\" é o escape padrão do LIKE no MySQL) e cerca com "%" para "contém".
 */
final class Like
{
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '\\%_').'%';
    }
}
