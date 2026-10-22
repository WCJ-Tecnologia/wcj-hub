<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Platform\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Em produção, todo link sai do APP_URL e só o host dele é aceito: um pedido com Host falso
 * não manda à vítima um link para outro domínio (revisão dirigida, achado 1; B9, SEC-21).
 */
class HostDoHubTest extends TestCase
{
    use RefreshDatabase;

    private const APP_URL = 'https://hub.exemplo.wcj.test';

    protected function tearDown(): void
    {
        // A lista de hosts confiáveis é estática no Request do Symfony: não pode vazar para o próximo teste.
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    private function emProducao(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => self::APP_URL]);
        // Fora do ambiente de testes o Laravel volta a exigir o token CSRF; ele não é o assunto aqui.
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
        User::factory()->projectManager()->create(['email' => 'ana@exemplo.wcj.test']);
    }

    public function test_a_foreign_host_is_refused_in_production_and_nothing_is_queued(): void
    {
        $this->emProducao();

        $this->post('http://atacante.exemplo/admin/forgot-password', ['email' => 'ana@exemplo.wcj.test'])->assertBadRequest();

        Mail::assertNothingOutgoing();
    }

    public function test_the_reset_link_starts_with_the_app_url_even_over_http(): void
    {
        $this->emProducao();

        $this->post('http://hub.exemplo.wcj.test/admin/forgot-password', ['email' => 'ana@exemplo.wcj.test'])->assertRedirect();

        Mail::assertQueued(ResetPasswordMail::class, fn (ResetPasswordMail $mail) => str_starts_with($mail->resetUrl, self::APP_URL.'/admin/reset-password/'));
    }

    public function test_urls_generated_in_a_request_start_with_the_app_url(): void
    {
        $this->emProducao();
        Route::get('/_test/url', fn () => route('login'));

        $this->get('http://hub.exemplo.wcj.test/_test/url')->assertOk()->assertSeeText(self::APP_URL.'/admin/login');
    }

    public function test_outside_production_the_host_is_not_checked(): void
    {
        $this->get('http://outro-host.exemplo/up')->assertOk();
    }

    /** Host parecido com o do APP_URL também é recusado (Verifier do B9, rodada 1: M05 e M06). */
    public function test_a_subdomain_or_a_lookalike_host_is_refused_in_production(): void
    {
        $this->emProducao();

        foreach (['http://x.hub.exemplo.wcj.test', 'http://hub.exemplo.wcj.test.atacante.exemplo'] as $root) {
            $this->post($root.'/admin/forgot-password', ['email' => 'ana@exemplo.wcj.test'])->assertBadRequest();
        }

        Mail::assertNothingOutgoing();
    }

    /** A raiz é a do APP_URL, sem a porta da requisição (Verifier do B9, rodada 1: M11). */
    public function test_a_request_on_another_port_still_generates_app_url_links(): void
    {
        $this->emProducao();
        Route::get('/_test/url', fn () => route('login'));

        $this->get('http://hub.exemplo.wcj.test:8080/_test/url')->assertOk()->assertSeeText(self::APP_URL.'/admin/login');

        $this->post('http://hub.exemplo.wcj.test:8080/admin/forgot-password', ['email' => 'ana@exemplo.wcj.test'])->assertRedirect();
        Mail::assertQueued(ResetPasswordMail::class, fn (ResetPasswordMail $mail) => str_starts_with($mail->resetUrl, self::APP_URL.'/admin/reset-password/'));
    }

    /** Fora de produção, a URL gerada segue o host da requisição (premissa "nada muda"; Verifier do B9, rodada 1: M10). */
    public function test_outside_production_generated_urls_follow_the_request_host(): void
    {
        Route::get('/_test/url', fn () => route('login'));

        $this->get('http://outro-host.exemplo/_test/url')->assertOk()->assertSeeText('http://outro-host.exemplo/admin/login');
    }
}
