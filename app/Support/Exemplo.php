<?php

namespace App\Support;

/**
 * Marca do dado de exemplo (playbooks/aplicacao-nova.md, etapa 8): sem coluna nova no banco,
 * o exemplo se reconhece pelo nome e pelo e-mail. Quem semeia (ExemploSeeder) e quem apaga
 * (app:limpar-exemplo) leem daqui.
 */
final class Exemplo
{
    /** Começo do nome de cliente, contato, usuário e projeto de exemplo. */
    public const PREFIXO = '[Exemplo] ';

    /** Domínio reservado (.test, RFC 2606): nenhum e-mail de exemplo chega a alguém. */
    public const DOMINIO_EMAIL = 'exemplo.wcj.test';

    public static function nome(string $nome): string
    {
        return self::PREFIXO.$nome;
    }

    public static function email(string $local): string
    {
        return $local.'@'.self::DOMINIO_EMAIL;
    }

    /** Padrão LIKE do nome marcado. */
    public static function likeNome(): string
    {
        return str_replace(['[', ']'], ['\\[', '\\]'], self::PREFIXO).'%';
    }

    /** Padrão LIKE do e-mail marcado. */
    public static function likeEmail(): string
    {
        return '%@'.self::DOMINIO_EMAIL;
    }
}
