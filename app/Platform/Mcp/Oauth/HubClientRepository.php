<?php

namespace App\Platform\Mcp\Oauth;

use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\ClientRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * Redirect de loopback com porta aleatória (Claude Code, Codex; RFC 8252, 7.3). O league já
 * ignora a porta para 127.0.0.1 e [::1], mas não para "localhost". Aqui o redirect pedido entra
 * na lista do cliente (só em memória) quando casa com um cadastrado ignorando a porta.
 */
class HubClientRepository extends ClientRepository
{
    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $cliente = parent::getClientEntity($clientIdentifier);
        $pedido = request()->input('redirect_uri');

        if (! $cliente instanceof Client || ! is_string($pedido) || ! self::loopback($pedido)) {
            return $cliente;
        }

        $cadastrados = (array) $cliente->getRedirectUri();

        foreach ($cadastrados as $cadastrado) {
            if (self::loopback($cadastrado) && self::semPorta($cadastrado) === self::semPorta($pedido)) {
                return new Client(
                    $cliente->getIdentifier(),
                    $cliente->getName(),
                    [...$cadastrados, $pedido],
                    $cliente->isConfidential(),
                    $cliente->provider,
                    $cliente->grantTypes,
                );
            }
        }

        return $cliente;
    }

    public static function loopback(string $uri): bool
    {
        return parse_url($uri, PHP_URL_SCHEME) === 'http'
            && in_array(parse_url($uri, PHP_URL_HOST), ['localhost', '127.0.0.1', '[::1]'], true);
    }

    private static function semPorta(string $uri): string
    {
        $partes = parse_url($uri);

        return ($partes['scheme'] ?? '').'://'.($partes['host'] ?? '').($partes['path'] ?? '').(isset($partes['query']) ? '?'.$partes['query'] : '');
    }
}
