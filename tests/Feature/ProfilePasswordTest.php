<?php

namespace Tests\Feature;

use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Trocar a senha pelo perfil pede a senha atual e tem limite (B9, SEC-04 e SEC-05).
 */
class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // O contador do limitador expira pelo relógio de parede (ver LoginThrottleTest).
        $this->freezeTime();
    }

    public function test_a_wrong_current_password_is_refused_and_nothing_changes(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->projectManager()->create(['password' => 'senha-atual-1']);
        DB::table('sessions')->insert(['id' => 'outro-aparelho', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($user)
            ->from('/admin/profile')
            ->put('/admin/profile', [
                'name' => $user->name,
                'current_password' => 'senha-errada-1',
                'password' => 'senha-nova-123',
                'password_confirmation' => 'senha-nova-123',
            ])
            ->assertRedirect('/admin/profile')
            ->assertSessionHasErrors(['current_password' => 'A senha atual não confere.']);

        $this->assertTrue(Hash::check('senha-atual-1', $user->fresh()->password));
        $this->assertDatabaseHas('sessions', ['id' => 'outro-aparelho']);
    }

    public function test_a_missing_current_password_is_refused_when_a_new_password_is_sent(): void
    {
        $user = User::factory()->projectManager()->create(['password' => 'senha-atual-1']);

        $this->actingAs($user)
            ->put('/admin/profile', [
                'name' => $user->name,
                'password' => 'senha-nova-123',
                'password_confirmation' => 'senha-nova-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('senha-atual-1', $user->fresh()->password));
    }

    public function test_the_right_current_password_changes_the_password_and_ends_the_other_sessions(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->projectManager()->create(['password' => 'senha-atual-1']);
        DB::table('sessions')->insert(['id' => 'outro-aparelho', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($user)
            ->put('/admin/profile', [
                'name' => $user->name,
                'current_password' => 'senha-atual-1',
                'password' => 'senha-nova-123',
                'password_confirmation' => 'senha-nova-123',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('senha-nova-123', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'outro-aparelho']);
    }

    public function test_saving_only_the_name_does_not_ask_for_the_current_password(): void
    {
        $user = User::factory()->projectManager()->create(['password' => 'senha-atual-1']);

        $this->actingAs($user)
            ->put('/admin/profile', ['name' => 'Nome Novo', 'current_password' => 'qualquer-coisa'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('Nome Novo', $user->fresh()->name);
        $this->assertTrue(Hash::check('senha-atual-1', $user->fresh()->password));
    }

    public function test_the_sixth_password_change_in_a_minute_is_throttled_and_the_session_stays(): void
    {
        $user = User::factory()->projectManager()->create(['password' => 'senha-atual-1']);
        $attempt = [
            'name' => $user->name,
            'current_password' => 'senha-errada-1',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->put('/admin/profile', $attempt)->assertSessionHasErrors('current_password');
        }

        $this->actingAs($user)
            ->withHeaders(['X-Inertia' => 'true'])
            ->put('/admin/profile', [...$attempt, 'current_password' => 'senha-atual-1'])
            ->assertStatus(429)
            ->assertJsonPath('component', 'admin/profile/index')
            ->assertJsonPath('props.errors.current_password', AuthenticatedSessionController::INVALID_CREDENTIALS);

        $this->assertTrue(Hash::check('senha-atual-1', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_saving_only_the_name_is_not_throttled(): void
    {
        $user = User::factory()->projectManager()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)
                ->put('/admin/profile', ['name' => 'Nome '.$i])
                ->assertSessionHas('success');
        }

        $this->assertSame('Nome 5', $user->fresh()->name);
    }

    public function test_the_limit_is_per_user(): void
    {
        $user = User::factory()->projectManager()->create(['password' => 'senha-atual-1']);
        $other = User::factory()->projectManager()->create(['password' => 'senha-atual-1']);
        $attempt = fn (User $who) => [
            'name' => $who->name,
            'current_password' => 'senha-errada-1',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ];

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->put('/admin/profile', $attempt($user));
        }

        $this->actingAs($other)->put('/admin/profile', $attempt($other))->assertSessionHasErrors('current_password');
    }
}
