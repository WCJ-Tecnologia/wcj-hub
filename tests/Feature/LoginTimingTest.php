<?php

namespace Tests\Feature;

use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

/**
 * Mesma mensagem e mesmo trabalho para e-mail com e sem conta (seguranca.md §3; B9, SEC-06).
 */
class LoginTimingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_email_checks_the_password_against_a_throwaway_hash(): void
    {
        $bcrypt = Hash::make('descartavel');
        Hash::shouldReceive('make')->andReturn($bcrypt);
        Hash::shouldReceive('check')
            ->once()
            ->with('senha-qualquer-1', Mockery::on(fn ($hashed) => is_string($hashed) && str_starts_with($hashed, '$2y$')))
            ->andReturnFalse();

        $this->post('/admin/login', ['email' => 'ninguem@exemplo.wcj.test', 'password' => 'senha-qualquer-1'])
            ->assertSessionHasErrors(['email' => AuthenticatedSessionController::INVALID_CREDENTIALS]);

        $this->assertGuest();
    }

    public function test_a_wrong_password_and_an_unknown_email_get_the_same_message(): void
    {
        User::factory()->projectManager()->create(['email' => 'existe@exemplo.wcj.test', 'password' => 'senha-certa-1']);

        $this->post('/admin/login', ['email' => 'existe@exemplo.wcj.test', 'password' => 'senha-errada-1'])
            ->assertSessionHasErrors(['email' => AuthenticatedSessionController::INVALID_CREDENTIALS]);
        $this->post('/admin/login', ['email' => 'ninguem@exemplo.wcj.test', 'password' => 'senha-errada-1'])
            ->assertSessionHasErrors(['email' => AuthenticatedSessionController::INVALID_CREDENTIALS]);
        $this->assertGuest();
    }
}
