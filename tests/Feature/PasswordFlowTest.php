<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Mail\TemporaryPasswordMail;
use App\Platform\Controllers\Auth\ForgotPasswordController;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_answers_the_same_for_every_email_and_mails_only_active_accounts(): void
    {
        Mail::fake();

        $supervisor = User::factory()->projectManager()->create(['email' => 'ana@example.com']);
        User::factory()->projectManager()->inactive()->create(['email' => 'inativo@example.com']);

        $this->from('/admin/forgot-password')
            ->post('/admin/forgot-password', ['email' => 'ana@example.com'])
            ->assertRedirect('/admin/forgot-password')
            ->assertSessionHas('success', ForgotPasswordController::GENERIC_MESSAGE);

        Mail::assertSent(ResetPasswordMail::class, fn (ResetPasswordMail $mail) => $mail->hasTo($supervisor->email));

        Mail::fake();

        foreach (['inativo@example.com', 'ninguem@example.com'] as $email) {
            $this->from('/admin/forgot-password')
                ->post('/admin/forgot-password', ['email' => $email])
                ->assertRedirect('/admin/forgot-password')
                ->assertSessionHas('success', ForgotPasswordController::GENERIC_MESSAGE)
                ->assertSessionHasNoErrors();
        }

        Mail::assertNothingSent();
    }

    public function test_reset_link_sets_a_new_password_and_clears_the_first_access_flag(): void
    {
        $user = User::factory()->developer()->mustChangePassword()->create([
            'email' => 'carla@example.com',
            'password' => 'temporary',
        ]);

        $token = Password::createToken($user);

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => 'carla@example.com',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('senha-nova-123', $user->password));
    }

    public function test_reset_token_expires_after_sixty_minutes(): void
    {
        $user = User::factory()->projectManager()->create(['email' => 'carla@example.com', 'password' => 'antiga-senha-1']);
        $token = Password::createToken($user);

        $this->travel(61)->minutes();

        $this->from('/admin/reset-password/'.$token)
            ->post('/admin/reset-password', [
                'token' => $token,
                'email' => 'carla@example.com',
                'password' => 'senha-nova-123',
                'password_confirmation' => 'senha-nova-123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('antiga-senha-1', $user->fresh()->password));
    }

    public function test_reset_token_is_rejected_when_it_does_not_match(): void
    {
        $user = User::factory()->projectManager()->create(['email' => 'carla@example.com']);
        Password::createToken($user);

        $this->from('/admin/reset-password/x')
            ->post('/admin/reset-password', [
                'token' => 'not-the-token',
                'email' => 'carla@example.com',
                'password' => 'senha-nova-123',
                'password_confirmation' => 'senha-nova-123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_password_policy_requires_ten_chars_letters_and_numbers(): void
    {
        $user = User::factory()->projectManager()->create(['email' => 'carla@example.com']);
        $token = Password::createToken($user);

        foreach (['curta1', 'somenteletras', '1234567890'] as $weak) {
            $this->from('/admin/reset-password/'.$token)
                ->post('/admin/reset-password', [
                    'token' => $token,
                    'email' => 'carla@example.com',
                    'password' => $weak,
                    'password_confirmation' => $weak,
                ])
                ->assertSessionHasErrors('password');
        }
    }

    public function test_first_login_with_temporary_password_goes_to_change_password(): void
    {
        $user = User::factory()->projectManager()->mustChangePassword()->create([
            'email' => 'paulo@example.com',
            'password' => 'Temp12abc',
        ]);

        $this->post('/admin/login', [
            'email' => 'paulo@example.com',
            'password' => 'Temp12abc',
        ])->assertRedirect(route('password.change'));

        $this->assertAuthenticatedAs($user);

        $this->get('/admin/dashboard')->assertRedirect(route('password.change'));
        $this->get('/admin/users')->assertRedirect(route('password.change'));
        $this->get('/admin/first-access')->assertOk();
    }

    public function test_user_must_pick_a_new_password_before_reaching_the_dashboard(): void
    {
        $user = User::factory()->projectManager()->mustChangePassword()->create([
            'email' => 'paulo@example.com',
            'password' => 'Temp12abc',
        ]);

        $this->actingAs($user)
            ->put('/admin/first-access', [
                'password' => 'minha-senha-boa-1',
                'password_confirmation' => 'minha-senha-boa-1',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('minha-senha-boa-1', $user->password));

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_new_password_cannot_be_the_temporary_one(): void
    {
        $user = User::factory()->projectManager()->mustChangePassword()->create([
            'password' => 'Temp12abcdef',
        ]);

        $this->actingAs($user)
            ->from('/admin/first-access')
            ->put('/admin/first-access', [
                'password' => 'Temp12abcdef',
                'password_confirmation' => 'Temp12abcdef',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_changing_the_password_terminates_the_other_sessions_of_the_account(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->projectManager()->mustChangePassword()->create(['password' => 'Temp12abcdef']);
        $other = User::factory()->projectManager()->create();

        DB::table('sessions')->insert([
            ['id' => 'stolen-cookie', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'another-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'someone-else', 'user_id' => $other->id, 'payload' => '', 'last_activity' => time()],
        ]);

        $this->actingAs($user)
            ->put('/admin/first-access', [
                'password' => 'minha-senha-boa-1',
                'password_confirmation' => 'minha-senha-boa-1',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('sessions', ['id' => 'stolen-cookie']);
        $this->assertDatabaseMissing('sessions', ['id' => 'another-device']);
        $this->assertDatabaseHas('sessions', ['id' => 'someone-else']);
    }

    public function test_reset_link_terminates_every_session_of_the_account(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->projectManager()->create(['email' => 'carla@example.com']);
        DB::table('sessions')->insert(['id' => 'stolen-cookie', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->post('/admin/reset-password', [
            'token' => Password::createToken($user),
            'email' => 'carla@example.com',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('sessions', ['id' => 'stolen-cookie']);
    }

    public function test_creating_a_user_emails_a_temporary_password(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'role_id' => 2,
                'name' => 'Maria Souza',
                'email' => 'maria@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $created = User::query()->where('email', 'maria@example.com')->firstOrFail();

        $this->assertTrue($created->must_change_password);
        $this->assertNotNull($created->password);

        Mail::assertSent(
            TemporaryPasswordMail::class,
            fn (TemporaryPasswordMail $mail) => $mail->hasTo('maria@example.com') && $mail->isReset === false,
        );
    }

    public function test_admin_can_reset_a_password_and_force_the_first_access_flow(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->developer()->create([
            'email' => 'joao@example.com',
            'password' => 'antiga-senha',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->post('/admin/users/'.$target->id.'/reset-password')
            ->assertRedirect()
            ->assertSessionHas('success');

        $target->refresh();

        $this->assertTrue($target->must_change_password);
        $this->assertFalse(Hash::check('antiga-senha', $target->password));

        Mail::assertSent(
            TemporaryPasswordMail::class,
            fn (TemporaryPasswordMail $mail) => $mail->hasTo('joao@example.com') && $mail->isReset === true,
        );

        $this->post('/admin/logout');

        $this->post('/admin/login', [
            'email' => 'joao@example.com',
            'password' => 'antiga-senha',
        ])->assertSessionHasErrors('email');
    }

    public function test_project_manager_cannot_reset_passwords(): void
    {
        $supervisor = User::factory()->projectManager()->create();
        $target = User::factory()->developer()->create();

        $this->actingAs($supervisor)
            ->post('/admin/users/'.$target->id.'/reset-password')
            ->assertForbidden();
    }
}
