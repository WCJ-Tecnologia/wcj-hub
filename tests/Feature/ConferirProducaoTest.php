<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * app:conferir-producao recusa a configuração que não pode ir para produção e nunca imprime
 * valor, só o nome da variável (B9, SEC-12).
 */
class ConferirProducaoTest extends TestCase
{
    private const CERTA = [
        'app.env' => 'production',
        'app.debug' => false,
        'app.url' => 'https://hub.exemplo.wcj.test',
        'app.key' => 'base64:Q2hhdmVEZVRlc3RlRGVzY2FydGF2ZWxBcXVpMTIzNDU=',
        'session.secure' => true,
        'mail.default' => 'smtp',
        'queue.default' => 'database',
    ];

    public function test_the_right_configuration_passes(): void
    {
        config(self::CERTA);

        $this->artisan('app:conferir-producao')->assertExitCode(0);
    }

    public function test_debug_on_is_refused_by_name(): void
    {
        config([...self::CERTA, 'app.debug' => true]);

        $this->artisan('app:conferir-producao')
            ->expectsOutputToContain('APP_DEBUG ligado')
            ->assertExitCode(1);
    }

    public function test_every_wrong_item_is_listed_by_the_variable_name(): void
    {
        $errada = [
            'APP_ENV' => ['app.env' => 'local'],
            'APP_URL' => ['app.url' => 'http://hub.exemplo.wcj.test'],
            'SESSION_SECURE_COOKIE' => ['session.secure' => false],
            'MAIL_MAILER' => ['mail.default' => 'log'],
            'QUEUE_CONNECTION' => ['queue.default' => 'sync'],
            'APP_KEY' => ['app.key' => ''],
        ];

        foreach ($errada as $variavel => $valor) {
            config([...self::CERTA, ...$valor]);

            $this->artisan('app:conferir-producao')
                ->expectsOutputToContain($variavel)
                ->assertExitCode(1);
        }

        config([...self::CERTA, 'mail.default' => 'array']);
        $this->artisan('app:conferir-producao')->expectsOutputToContain('MAIL_MAILER')->assertExitCode(1);
    }

    public function test_no_value_is_ever_printed(): void
    {
        config([
            'app.env' => 'staging-secreto',
            'app.debug' => true,
            'app.url' => 'http://interno-secreto.exemplo.wcj.test',
            'app.key' => 'base64:Q2hhdmVEZVRlc3RlRGVzY2FydGF2ZWxBcXVpMTIzNDU=',
            'session.secure' => false,
            'mail.default' => 'log',
            'queue.default' => 'sync',
        ]);

        $this->artisan('app:conferir-producao')
            ->doesntExpectOutputToContain('staging-secreto')
            ->doesntExpectOutputToContain('interno-secreto')
            ->doesntExpectOutputToContain('Q2hhdmVE')
            ->assertExitCode(1);
    }
}
