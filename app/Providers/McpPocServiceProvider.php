<?php

namespace App\Providers;

use App\Platform\Mcp\Oauth\HubAccessToken;
use App\Platform\Mcp\Oauth\HubClientRepository;
use App\Platform\Mcp\Oauth\HubRegistrar;
use App\Platform\Mcp\Oauth\HubScopeRepository;
use App\Platform\Mcp\Oauth\RegistraConexao;
use App\Platform\Mcp\Oauth\RegistroDinamicoController;
use App\Platform\Mcp\Oauth\TelaDeAutorizacao;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Bridge\ClientRepository;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;

/**
 * Prova de conceito do servidor MCP (D-GER-17, branch poc/mcp). Tudo o que a prova troca no
 * laravel/mcp e no Passport está aqui, para o B8 enxergar cada ajuste num lugar só
 * (docs/poc-mcp/README.md, "Achados para o B8").
 */
class McpPocServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Metadados com os escopos da casa (o pacote só anuncia mcp:use).
        // Sem device code (o Passport 13 liga por padrão): a prova só usa authorization_code.
        Passport::$deviceCodeGrantEnabled = false;

        $this->app->singleton(Registrar::class, fn () => new HubRegistrar);
        // Registro dinâmico que loga o que o assistente declara.
        $this->app->bind(OAuthRegisterController::class, RegistroDinamicoController::class);
        // O servidor decide o escopo (IA4, IA5, IA3).
        $this->app->bind(ScopeRepository::class, HubScopeRepository::class);
        // Loopback "localhost" com porta aleatória.
        $this->app->bind(ClientRepository::class, HubClientRepository::class);
    }

    public function boot(): void
    {
        Passport::tokensCan(config('mcp_poc.escopos'));
        Passport::useAccessTokenEntity(HubAccessToken::class);
        Passport::tokensExpireIn(now()->addMinutes(config('mcp_poc.access_token_minutes')));
        Passport::refreshTokensExpireIn(now()->addDays(config('mcp_poc.refresh_token_days')));
        Passport::authorizationView(fn (array $parametros) => app(TelaDeAutorizacao::class)($parametros));

        Event::listen(AccessTokenCreated::class, RegistraConexao::class);

        // Atrás do túnel o issuer e os metadados precisam da URL pública (APP_URL), não do
        // 127.0.0.1 que o php artisan serve enxerga.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }
    }
}
