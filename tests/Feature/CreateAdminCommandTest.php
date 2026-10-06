<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_with_a_random_password_that_must_be_changed(): void
    {
        $this->artisan('app:create-admin', ['email' => 'Admin@Example.com', '--name' => 'Ana Admin'])
            ->expectsOutputToContain('Senha temporária')
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->must_change_password);
        $this->assertSame('Ana Admin', $user->name);
        $this->assertNotNull($user->password);
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_it_refuses_a_duplicate_email_and_an_invalid_one(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->artisan('app:create-admin', ['email' => 'admin@example.com'])->assertFailed();
        $this->artisan('app:create-admin', ['email' => 'not-an-email'])->assertFailed();

        $this->assertSame(1, User::query()->count());
    }

    public function test_a_fixed_password_is_only_for_test_environments(): void
    {
        $this->artisan('app:create-admin', ['email' => 'e2e@example.com', '--password' => 'E2eOnlyPassw0rd'])
            ->assertSuccessful();

        $user = User::query()->where('email', 'e2e@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('E2eOnlyPassw0rd', $user->password));
        $this->assertFalse($user->must_change_password);

        $this->app['env'] = 'production';

        $this->artisan('app:create-admin', ['email' => 'prod@example.com', '--password' => 'E2eOnlyPassw0rd'])->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'prod@example.com']);
    }
}
