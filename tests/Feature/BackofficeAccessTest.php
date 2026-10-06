<?php

namespace Tests\Feature;

use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackofficeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_every_admin_route(): void
    {
        foreach (['/', '/admin/dashboard', '/admin/enterprises', '/admin/users', '/admin/audit-logs', '/admin/profile'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_backoffice_user_can_sign_in(): void
    {
        $user = User::factory()->supervisor()->create([
            'email' => 'supervisor@example.com',
            'password' => 'password',
        ]);

        $this->post('/admin/login', [
            'email' => 'supervisor@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'login']);
    }

    public function test_wrong_password_unknown_email_and_inactive_account_get_the_same_message(): void
    {
        User::factory()->supervisor()->create(['email' => 'ana@example.com', 'password' => 'password']);
        User::factory()->supervisor()->inactive()->create(['email' => 'inativo@example.com', 'password' => 'password']);

        foreach ([
            ['email' => 'ana@example.com', 'password' => 'errada'],
            ['email' => 'ninguem@example.com', 'password' => 'password'],
            ['email' => 'inativo@example.com', 'password' => 'password'],
        ] as $attempt) {
            $this->from('/admin/login')
                ->post('/admin/login', $attempt)
                ->assertRedirect('/admin/login')
                ->assertSessionHasErrors(['email' => AuthenticatedSessionController::INVALID_CREDENTIALS]);

            $this->assertGuest();
        }
    }

    public function test_inactive_session_is_rejected_by_the_backoffice_middleware(): void
    {
        $user = User::factory()->supervisor()->create();

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();

        $user->setActive(false, null)->save();

        $this->actingAs($user->fresh())->get('/admin/dashboard')->assertForbidden();
        $this->assertGuest();
    }

    public function test_root_redirects_to_the_dashboard_when_signed_in(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_shows_real_counts(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->supervisor()->inactive()->create();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/dashboard')
                ->where('summary.users_total', 2)
                ->where('summary.users_active', 1)
                ->where('summary.enterprises_total', 0));
    }

    public function test_health_check_is_public_and_carries_no_data(): void
    {
        $this->getJson('/api/health')->assertOk()->assertExactJson(['ok' => true]);
        $this->get('/up')->assertOk();
    }
}
