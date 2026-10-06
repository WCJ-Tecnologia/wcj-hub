<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * The limiter is proven dynamically: a sequence of requests that ends in 429.
 * Reading the config would not catch a limiter that is registered but not
 * attached to the route.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sixth_login_attempt_for_the_same_account_is_throttled_with_the_generic_message(): void
    {
        User::factory()->projectManager()->create(['email' => 'ana@example.com', 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'ana@example.com', 'password' => 'errada'])
                ->assertRedirect()
                ->assertSessionHasErrors('email');
        }

        // Plain HTTP: 429 with the same message as a wrong password.
        $this->post('/admin/login', ['email' => 'ana@example.com', 'password' => 'errada'])
            ->assertStatus(429)
            ->assertSee(AuthenticatedSessionController::INVALID_CREDENTIALS);

        // Inertia: the page is re-rendered with status 429 and the error in props.
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $this->inertiaVersion()])
            ->post('/admin/login', ['email' => 'ana@example.com', 'password' => 'password'])
            ->assertStatus(429)
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'auth/login')
            ->assertJsonPath('props.errors.email', AuthenticatedSessionController::INVALID_CREDENTIALS)
            // Shared props must be present: ThrottleRequests runs before HandleInertiaRequests.
            ->assertJsonPath('props.app.brand.name', config('app.brand.name'))
            ->assertJsonPath('props.auth.user', null);

        $this->assertGuest();
    }

    public function test_the_per_account_key_does_not_block_a_different_account_from_the_same_ip(): void
    {
        User::factory()->projectManager()->create(['email' => 'bia@example.com', 'password' => 'password']);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/admin/login', ['email' => 'ana@example.com', 'password' => 'errada']);
        }

        $this->post('/admin/login', ['email' => 'bia@example.com', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_the_per_ip_ceiling_blocks_after_twenty_attempts_across_accounts(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/admin/login', ['email' => "user{$i}@example.com", 'password' => 'errada'])->assertRedirect();
        }

        $this->post('/admin/login', ['email' => 'user-21@example.com', 'password' => 'errada'])->assertStatus(429);
    }

    public function test_forgot_password_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/forgot-password', ['email' => 'ana@example.com'])->assertRedirect();
        }

        $this->post('/admin/forgot-password', ['email' => 'ana@example.com'])->assertStatus(429);
    }

    public function test_reset_password_is_throttled(): void
    {
        $user = User::factory()->projectManager()->create(['email' => 'ana@example.com']);
        $token = Password::createToken($user);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/reset-password', [
                'token' => 'wrong',
                'email' => 'ana@example.com',
                'password' => 'senha-nova-123',
                'password_confirmation' => 'senha-nova-123',
            ])->assertRedirect();
        }

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertStatus(429);
    }

    public function test_first_access_is_throttled(): void
    {
        $user = User::factory()->projectManager()->mustChangePassword()->create(['password' => 'Temp12abcdef']);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)
                ->put('/admin/first-access', ['password' => 'x', 'password_confirmation' => 'x'])
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->put('/admin/first-access', ['password' => 'x', 'password_confirmation' => 'x'])
            ->assertStatus(429);
    }

    private function inertiaVersion(): string
    {
        return (string) app(HandleInertiaRequests::class)->version(request());
    }
}
