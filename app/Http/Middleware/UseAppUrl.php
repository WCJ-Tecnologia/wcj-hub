<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Em produção, toda URL que o Hub gera (link do e-mail de redefinição, logo do e-mail, avisos
 * do card, redirects) sai do APP_URL, nunca do Host da requisição: um pedido com Host forjado
 * mandaria à vítima um link para outro domínio (revisão dirigida do B9, achado 1; SEC-21).
 * O trustHosts do bootstrap/app.php recusa o Host estranho antes; isto cobre o esquema (https
 * atrás de proxy) e qualquer caminho que escape dele. Fora de produção, nada muda.
 */
class UseAppUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()) {
            $root = rtrim((string) config('app.url'), '/');

            URL::forceRootUrl($root);
            URL::forceScheme((string) (parse_url($root, PHP_URL_SCHEME) ?: 'https'));
        }

        return $next($request);
    }
}
