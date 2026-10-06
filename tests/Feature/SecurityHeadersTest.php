<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_web_response_carries_the_security_headers(): void
    {
        $response = $this->get('/admin/login')->assertOk();

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringNotContainsString('googleapis', $csp);

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeaderMissing('Strict-Transport-Security');

        // The inline theme script is allowed only through the nonce.
        preg_match("/'nonce-([^']+)'/", $csp, $match);
        $response->assertSee('nonce="'.$match[1].'"', false);
    }

    public function test_hsts_is_sent_only_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('/admin/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_the_dev_server_origin_is_only_allowed_in_local(): void
    {
        $csp = $this->get('/admin/login')->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString('localhost:5173', $csp);

        $this->app['env'] = 'local';

        $csp = $this->get('/admin/login')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('http://localhost:5173', $csp);
        $this->assertStringContainsString('ws://localhost:5173', $csp);
    }

    public function test_session_cookie_is_http_only_and_lax(): void
    {
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }

    public function test_cors_never_opens_to_every_origin(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));
        $this->assertSame(['api/health'], config('cors.paths'));
    }

    public function test_trusted_proxies_come_from_config_and_unlock_the_real_ip(): void
    {
        Route::middleware('web')->get('/_test/ip', fn () => request()->ip());

        $forwarded = ['X-Forwarded-For' => '203.0.113.9'];
        $fromProxy = ['REMOTE_ADDR' => '10.0.0.1'];

        // No proxy trusted: the header is ignored.
        config(['app.trusted_proxies' => null]);
        $this->withServerVariables($fromProxy)->withHeaders($forwarded)->get('/_test/ip')->assertSee('10.0.0.1');

        // The proxy is trusted: the client IP comes from the header.
        config(['app.trusted_proxies' => '10.0.0.1, 10.0.0.2']);
        $this->withServerVariables($fromProxy)->withHeaders($forwarded)->get('/_test/ip')->assertSee('203.0.113.9');

        // A request that did not come through a trusted proxy keeps its own IP.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])->withHeaders($forwarded)->get('/_test/ip')->assertSee('198.51.100.7');
    }
}
