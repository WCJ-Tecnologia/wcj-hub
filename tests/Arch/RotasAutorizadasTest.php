<?php

namespace Tests\Arch;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Tests\Arch\Support\AcoesDeExemploA10;
use Tests\Arch\Support\VerificaArquitetura;

/**
 * A5: toda rota do projeto é pública declarada, ou exige login E autoriza (Policy, Gate,
 * middleware can:). .ai/AGENTS.padrao.md, regra 8: "rota pública é exceção declarada".
 * Skill wcj-testes, references/arquitetura.md.
 *
 * Sobe o Laravel para ler as rotas, sem banco: estende a TestCase do framework
 * (Laravel 11+), não a do projeto, que costuma migrar e semear no setUp.
 */
class RotasAutorizadasTest extends TestCase
{
    use VerificaArquitetura;

    /**
     * Rota pública de verdade, com o motivo. As protegidas por guest (login, senha) e por
     * link assinado (signed) já contam como declaradas pelo próprio middleware.
     * Chave: "VERBO /uri", como o teste imprime.
     */
    private const ROTAS_PUBLICAS = [
        'GET /' => 'redireciona para o login ou para o painel; não mostra dado',
        'GET /api/health' => 'health check do balanceador; não mostra dado',
    ];

    /**
     * Middleware próprio que exige login (sessão de operador, token de API), pelo nome da
     * classe. O Authenticate do Laravel (e subclasses) já conta.
     */
    private const MIDDLEWARE_DE_LOGIN = [
        // \App\Http\Middleware\EnsureOperatorCardSession::class,
    ];

    /**
     * Ação sobre a própria conta ou sessão: quem está logado só age sobre si mesmo, então
     * não há recurso de terceiro a autorizar. Chave "Classe@metodo" => motivo.
     */
    private const ACOES_DA_PROPRIA_CONTA = [
        'App\Platform\Controllers\Auth\AuthenticatedSessionController@destroy' => 'logout da própria sessão',
        'App\Platform\Controllers\Auth\ChangePasswordController@edit' => 'primeiro acesso: troca a própria senha',
        'App\Platform\Controllers\Auth\ChangePasswordController@update' => 'primeiro acesso: troca a própria senha',
        'App\Platform\Controllers\Admin\DashboardController@index' => 'painel de quem está logado: contagens gerais; a atividade recente só chega ao admin',
        'App\Platform\Controllers\Admin\ProfileController@edit' => 'o próprio perfil',
        'App\Platform\Controllers\Admin\ProfileController@update' => 'o próprio perfil',
        'App\Platform\Controllers\NotificationController@read' => 'marca como lido um aviso da própria pessoa (procurado só entre os dela: de outra, 404)',
        'App\Platform\Controllers\NotificationController@readAll' => 'marca como lidos os avisos da própria pessoa',
    ];

    // Autoriza dentro da ação: Policy, Gate, helper do controller com nome de checagem
    // ($this->ensureOwnership), abort 403/404 condicional (404 esconde a existência do
    // recurso), exceção de acesso negado e can()/cannot() dentro de condição. Um abort com
    // outro código (422, 500) não é autorização. Comentário não conta.
    private const AUTORIZA = '/->authorize\(|Gate::(authorize|allows|denies|check|inspect|any|none)\(|\$this->(ensure|authoriz)\w*\('
        .'|\babort_(if|unless)\s*\([^;]*,\s*40[34]\b|\bif\s*\([^;{]*\)\s*\{?\s*abort\(\s*40[34]\b'
        .'|throw\s+new\s+\\?(\w+\\\\)*(AuthorizationException|AccessDeniedHttpException)\b'
        .'|(\bif\s*\(|\bthrow_(if|unless)\s*\(|&&|\|\||!)[^;{]*->can(not)?\(/';

    private const METODOS_DE_RESOURCE = ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'];

    public function test_a5_toda_rota_e_publica_declarada_ou_autoriza(): void
    {
        $this->app->make(Kernel::class); // registra os apelidos de middleware no roteador
        $roteador = $this->app->make('router');

        $violacoes = [];
        foreach ($roteador->getRoutes() as $rota) {
            $funcao = self::funcaoDaRota($rota);
            if ($funcao === null) {
                continue; // rota de pacote (vendor): fora do escopo
            }
            $acao = $rota->getActionName() === 'Closure'
                ? 'Closure em '.self::relativo((string) $funcao->getFileName()).':'.$funcao->getStartLine()
                : $rota->getActionName();
            $chave = self::verbo($rota).' /'.ltrim($rota->uri(), '/');
            $middlewares = array_values(array_filter($roteador->gatherRouteMiddleware($rota), 'is_string'));

            if (! self::exigeLogin($middlewares)) {
                if (! self::publicaPorMiddleware($middlewares) && ! array_key_exists($chave, self::ROTAS_PUBLICAS)) {
                    $violacoes[] = ['chave' => $chave, 'texto' => "{$chave} → {$acao} é pública e não está declarada"];
                }

                continue;
            }
            if (array_key_exists($acao, self::ACOES_DA_PROPRIA_CONTA) || self::temCan($middlewares) || self::autoriza($funcao)) {
                continue;
            }
            $violacoes[] = ['chave' => $acao, 'texto' => "{$chave} → {$acao} não autoriza"];
        }

        $this->semViolacoes('A5', 'Toda rota é pública declarada ou autoriza', $violacoes,
            'rota logada: chame $this->authorize() com a Policy do recurso, ou ponha num grupo com can:. '
            .'Rota pública de propósito: declare em ROTAS_PUBLICAS, com o motivo. '
            .'Ação que só mexe na conta de quem está logado: ACOES_DA_PROPRIA_CONTA, com o motivo.');
    }

    /**
     * Validação na própria ação, fora de FormRequest (B9, SEC-09; seguranca.md §7). O
     * `validated()` do FormRequest não casa: o parêntese vem logo depois de "validate".
     */
    private const VALIDA_NA_MAO = '/->validate\(|\bValidator::make\(|\bvalidator\(/';

    public function test_a10_escrita_valida_a_entrada_em_form_request(): void
    {
        $this->app->make(Kernel::class);

        $violacoes = self::violacoesA10($this->app->make('router')->getRoutes(), self::funcaoDaRota(...));

        $this->semViolacoes('A10', 'Validação fora de FormRequest', $violacoes,
            'a escrita valida a entrada num FormRequest (regras explícitas, autoriza antes de validar), '
            .'nunca com $request->validate() ou Validator::make() na ação.');
    }

    /** O detector pega as duas formas e só olha a escrita (Verifier do B9, rodada 1: M36 e M38). */
    public function test_a10_o_detector_pega_a_validacao_na_mao_e_so_na_escrita(): void
    {
        $this->assertTrue(self::validaNaMao(new ReflectionMethod(AcoesDeExemploA10::class, 'validaNoRequest')));
        $this->assertTrue(self::validaNaMao(new ReflectionMethod(AcoesDeExemploA10::class, 'validaComValidator')));
        $this->assertFalse(self::validaNaMao(new ReflectionMethod(AcoesDeExemploA10::class, 'usaOValidado')));

        // A mesma varredura do teste acima, sobre rotas de mentira: a ação de exemplo não é App\,
        // então o resolvedor aqui aceita a classe dela.
        $acao = AcoesDeExemploA10::class.'@validaNoRequest';
        $rotas = new RouteCollection;
        $rotas->add(new Route(['POST'], '_a10/escrita', ['uses' => $acao, 'controller' => $acao]));
        $rotas->add(new Route(['GET', 'HEAD'], '_a10/leitura', ['uses' => $acao, 'controller' => $acao]));
        $resolve = fn (Route $rota) => new ReflectionMethod(...explode('@', $rota->getActionName(), 2));

        $this->assertSame(
            ['POST /_a10/escrita -> '.AcoesDeExemploA10::class.'@validaNoRequest'],
            self::violacoesA10($rotas, $resolve),
        );
    }

    /**
     * @param  iterable<Route>  $rotas
     * @param  callable(Route): ?ReflectionFunctionAbstract  $funcaoDaRota
     * @return list<string>
     */
    private static function violacoesA10(iterable $rotas, callable $funcaoDaRota): array
    {
        $violacoes = [];
        foreach ($rotas as $rota) {
            if (array_diff($rota->methods(), ['GET', 'HEAD']) === []) {
                continue;
            }
            $funcao = $funcaoDaRota($rota);
            if ($funcao !== null && self::validaNaMao($funcao)) {
                $violacoes[] = self::verbo($rota).' /'.ltrim($rota->uri(), '/').' -> '.$rota->getActionName();
            }
        }

        return $violacoes;
    }

    private static function validaNaMao(ReflectionFunctionAbstract $funcao): bool
    {
        return preg_match(self::VALIDA_NA_MAO, self::corpoSemComentario($funcao)) === 1;
    }

    /** A função que atende a rota, se ela é do projeto (App\ ou closure em routes/). */
    private static function funcaoDaRota(Route $rota): ?ReflectionFunctionAbstract
    {
        $uses = $rota->getAction('uses');
        if ($uses instanceof Closure) {
            $funcao = new ReflectionFunction($uses);
            $rotas = str_replace('\\', '/', base_path('routes'));

            return str_starts_with(str_replace('\\', '/', (string) $funcao->getFileName()), $rotas) ? $funcao : null;
        }
        $acao = $rota->getActionName();
        if (! str_starts_with($acao, 'App\\') || ! str_contains($acao, '@')) {
            return null;
        }
        [$classe, $metodo] = explode('@', $acao, 2);

        return method_exists($classe, $metodo) ? new ReflectionMethod($classe, $metodo) : null;
    }

    /** @param  list<string>  $middlewares  nomes de classe, com ":parâmetros" */
    private static function exigeLogin(array $middlewares): bool
    {
        foreach ($middlewares as $middleware) {
            $classe = strtok($middleware, ':');
            if (is_a($classe, Authenticate::class, true) || in_array($classe, self::MIDDLEWARE_DE_LOGIN, true)) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<string>  $middlewares */
    private static function publicaPorMiddleware(array $middlewares): bool
    {
        foreach ($middlewares as $middleware) {
            $classe = strtok($middleware, ':');
            if (is_a($classe, RedirectIfAuthenticated::class, true) || is_a($classe, ValidateSignature::class, true)) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<string>  $middlewares */
    private static function temCan(array $middlewares): bool
    {
        foreach ($middlewares as $middleware) {
            if (is_a(strtok($middleware, ':'), Authorize::class, true)) {
                return true;
            }
        }

        return false;
    }

    private static function autoriza(ReflectionFunctionAbstract $funcao): bool
    {
        if (preg_match(self::AUTORIZA, self::corpoSemComentario($funcao)) === 1) {
            return true;
        }
        if (! $funcao instanceof ReflectionMethod) {
            return false;
        }

        return self::formRequestAutoriza($funcao)
            || (in_array($funcao->getName(), self::METODOS_DE_RESOURCE, true) && self::construtorAutorizaResource($funcao->getDeclaringClass()));
    }

    /** FormRequest recebido pela ação com authorize() que checa algo (não só "return true"). */
    private static function formRequestAutoriza(ReflectionMethod $acao): bool
    {
        foreach ($acao->getParameters() as $parametro) {
            $tipo = $parametro->getType();
            $classe = $tipo instanceof \ReflectionNamedType ? $tipo->getName() : null;
            if ($classe === null || ! is_subclass_of($classe, FormRequest::class) || ! method_exists($classe, 'authorize')) {
                continue;
            }
            $authorize = new ReflectionMethod($classe, 'authorize');
            if ($authorize->getDeclaringClass()->getName() === $classe
                && ! preg_match('/\{\s*return\s+true\s*;\s*\}/', self::corpoSemComentario($authorize))) {
                return true;
            }
        }

        return false;
    }

    private static function construtorAutorizaResource(ReflectionClass $classe): bool
    {
        $construtor = $classe->getConstructor();

        return $construtor !== null && str_contains(self::corpoSemComentario($construtor), 'authorizeResource(');
    }

    private static function corpoSemComentario(ReflectionFunctionAbstract $funcao): string
    {
        $linhas = file((string) $funcao->getFileName()) ?: [];
        $trecho = implode('', array_slice($linhas, $funcao->getStartLine() - 1, $funcao->getEndLine() - $funcao->getStartLine() + 1));
        $codigo = '';
        foreach (token_get_all('<?php '.$trecho) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $codigo .= is_array($token) ? $token[1] : $token;
        }

        return $codigo;
    }

    private static function verbo(Route $rota): string
    {
        return implode('|', array_diff($rota->methods(), ['HEAD']));
    }

    private static function relativo(string $arquivo): string
    {
        return ltrim(str_replace(str_replace('\\', '/', base_path()), '', str_replace('\\', '/', $arquivo)), '/');
    }
}
