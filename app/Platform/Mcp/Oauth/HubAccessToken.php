<?php

namespace App\Platform\Mcp\Oauth;

use DateTimeImmutable;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * Token do Passport com o recurso na audiência (RFC 8707). O Passport põe só o client_id no
 * "aud" e lê o client_id do primeiro item (BearerTokenValidator: aud[0]); por isso o aud vira
 * [client_id, recurso] e o ConexaoDaIa confere o segundo. Mesmo corpo do Bridge\AccessToken.
 */
class HubAccessToken implements AccessTokenEntityInterface
{
    use AccessTokenTrait, EntityTrait, TokenEntityTrait;

    /** O recurso pedido no /oauth/token (OauthDaProva guarda); sem pedido, o canônico. */
    public const RECURSO = 'mcp_poc.recurso';

    /** @param  ScopeEntityInterface[]  $scopes */
    public function __construct(?string $userIdentifier, array $scopes, ClientEntityInterface $client)
    {
        if (! is_null($userIdentifier)) {
            $this->setUserIdentifier($userIdentifier);
        }

        foreach ($scopes as $scope) {
            $this->addScope($scope);
        }

        $this->setClient($client);
    }

    private function convertToJWT(): Token
    {
        $this->initJwtConfiguration();

        $recurso = app()->bound(self::RECURSO) ? app(self::RECURSO) : HubRegistrar::recursoCanonico();

        return $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier(), $recurso)
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable)
            ->canOnlyBeUsedAfter(new DateTimeImmutable)
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getUserIdentifier() ?? $this->getClient()->getIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->withClaim('client_id', $this->getClient()->getIdentifier())
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }
}
