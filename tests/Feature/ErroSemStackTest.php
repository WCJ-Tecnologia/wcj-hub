<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Com APP_DEBUG desligado, o 500 não conta nada do servidor: sem classe, caminho nem stack
 * (seguranca.md §5; B9, SEC-13).
 */
class ErroSemStackTest extends TestCase
{
    public function test_a_500_with_debug_off_tells_nothing_about_the_server(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test/explode', fn () => throw new RuntimeException('falha em '.__FILE__));

        foreach ([$this->get('/_test/explode'), $this->getJson('/_test/explode')] as $response) {
            $response->assertStatus(500);
            $body = (string) $response->getContent();

            $this->assertStringNotContainsString('RuntimeException', $body);
            $this->assertStringNotContainsString('ErroSemStackTest', $body);
            $this->assertStringNotContainsString('.php', $body);
            $this->assertStringNotContainsString('#0 ', $body);
            $this->assertStringNotContainsString(base_path(), $body);
        }
    }
}
