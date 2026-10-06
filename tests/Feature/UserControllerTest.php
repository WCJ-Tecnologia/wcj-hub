<?php

namespace Tests\Feature;

use App\Platform\Enums\UserTypeEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Enterprise;
use App\Platform\Models\User;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_admin_can_list_create_update_and_delete_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/index')->has('users.data', 1)->has('userTypes', 3));

        $this->actingAs($admin)
            ->post('/admin/users', [
                'user_type_id' => UserTypeEnum::SUPERVISOR->value,
                'name' => 'Maria Souza',
                'email' => 'maria@example.com',
                'phone' => '(11) 98765-4321',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $maria = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame('11987654321', $maria->phone);
        $this->assertTrue($maria->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => User::class, 'auditable_id' => $maria->id, 'action' => 'created']);

        $this->actingAs($admin)
            ->put('/admin/users/'.$maria->id, [
                'user_type_id' => UserTypeEnum::SUPERVISOR->value,
                'name' => 'Maria S. Lima',
                'email' => 'maria@example.com',
                'active' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $maria->refresh();
        $this->assertSame('Maria S. Lima', $maria->name);
        $this->assertFalse($maria->isActive());
        $this->assertNotNull($maria->deactivated_at);
        $this->assertSame($admin->id, $maria->deactivated_by);

        // Maria never signed in: deleting is allowed and it is for real.
        $this->actingAs($admin)
            ->delete('/admin/users/'.$maria->id)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertModelMissing($maria);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $maria->id, 'action' => 'deleted']);
    }

    public function test_deactivation_keeps_who_and_when_and_reactivation_clears_both(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
        $target = User::factory()->supervisor()->create();
        $payload = ['user_type_id' => UserTypeEnum::SUPERVISOR->value, 'name' => $target->name, 'email' => $target->email];

        $this->actingAs($admin)->put('/admin/users/'.$target->id, [...$payload, 'active' => false])->assertSessionHas('success');
        $stamp = $target->fresh()->deactivated_at;

        $this->actingAs($admin)
            ->get('/admin/users?search='.$target->email)
            ->assertInertia(fn ($page) => $page
                ->where('users.data.0.active', false)
                ->where('users.data.0.deactivated_by', 'Ana Admin')
                ->where('users.data.0.deactivated_at', LocalTime::format($stamp)));

        // Saving again, or omitting the field, keeps the original stamp.
        $this->travel(1)->hour();
        $this->actingAs($admin)->put('/admin/users/'.$target->id, [...$payload, 'active' => false]);
        $this->actingAs($admin)->put('/admin/users/'.$target->id, $payload);
        $this->assertEquals($stamp, $target->fresh()->deactivated_at);

        $this->actingAs($admin)->put('/admin/users/'.$target->id, [...$payload, 'active' => true]);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deactivated_at' => null, 'deactivated_by' => null]);

        // The way back is in the audit trail.
        $last = AuditLog::query()->where('auditable_id', $target->id)->where('action', 'updated')->latest('id')->firstOrFail();
        $this->assertFalse($last->before_data['active']);
        $this->assertTrue($last->after_data['active']);
    }

    public function test_is_active_fails_closed_when_the_column_was_not_loaded(): void
    {
        $user = User::factory()->inactive()->create();

        $this->expectException(\LogicException::class);
        User::query()->select(['id', 'email'])->findOrFail($user->id)->isActive();
    }

    public function test_deleting_a_user_never_signed_in_also_drops_the_pending_reset_token(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->supervisor()->create();
        Password::broker()->createToken($target);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $target->email]);

        $this->actingAs($admin)->delete('/admin/users/'.$target->id)->assertSessionHas('success');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $target->email]);
    }

    public function test_a_user_with_history_can_only_be_deactivated(): void
    {
        $admin = User::factory()->admin()->create();
        $signedIn = User::factory()->supervisor()->create(['last_login_at' => now()]);
        $author = User::factory()->supervisor()->create();
        AuditLog::query()->create(['user_id' => $author->id, 'action' => 'updated', 'auditable_type' => User::class, 'auditable_id' => $admin->id]);
        // Deactivated someone outside the screens (command, job): no audit row, still history.
        $deactivator = User::factory()->admin()->create();
        User::factory()->supervisor()->create()->setActive(false, $deactivator)->save();

        foreach ([$signedIn, $author, $deactivator] as $user) {
            $this->actingAs($admin)
                ->delete('/admin/users/'.$user->id)
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertModelExists($user);
        }
    }

    public function test_email_is_required_and_unique(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', ['user_type_id' => 2, 'name' => 'Sem e-mail'])
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', ['user_type_id' => 2, 'name' => 'Duplicado', 'email' => 'admin@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_financial_requires_at_least_one_enterprise_and_only_financial_keeps_the_scope(): void
    {
        $admin = User::factory()->admin()->create();
        $enterprise = Enterprise::factory()->create();

        $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', ['user_type_id' => UserTypeEnum::FINANCIAL->value, 'name' => 'Fin', 'email' => 'fin@example.com'])
            ->assertSessionHasErrors('enterprise_ids');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'user_type_id' => UserTypeEnum::FINANCIAL->value,
                'name' => 'Fin',
                'email' => 'fin@example.com',
                'enterprise_ids' => [$enterprise->id],
            ])
            ->assertSessionHasNoErrors();

        $fin = User::query()->where('email', 'fin@example.com')->firstOrFail();
        $this->assertSame([$enterprise->id], $fin->enterprises()->pluck('enterprises.id')->all());

        // Promoted to supervisor: the enterprise scope is dropped.
        $this->actingAs($admin)
            ->put('/admin/users/'.$fin->id, [
                'user_type_id' => UserTypeEnum::SUPERVISOR->value,
                'name' => 'Fin',
                'email' => 'fin@example.com',
                'enterprise_ids' => [$enterprise->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([], $fin->fresh()->enterprises()->pluck('enterprises.id')->all());
    }

    public function test_supervisor_can_list_but_cannot_write_users(): void
    {
        $supervisor = User::factory()->supervisor()->create();
        $target = User::factory()->financial()->create();

        $this->actingAs($supervisor)->get('/admin/users')->assertOk();
        $this->actingAs($supervisor)->post('/admin/users', ['user_type_id' => 2, 'name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
        $this->actingAs($supervisor)->put('/admin/users/'.$target->id, ['user_type_id' => 2, 'name' => 'X', 'email' => $target->email])->assertForbidden();
        $this->actingAs($supervisor)->delete('/admin/users/'.$target->id)->assertForbidden();
    }

    public function test_financial_cannot_even_list_users(): void
    {
        $this->actingAs(User::factory()->financial()->create())->get('/admin/users')->assertForbidden();
    }

    public function test_admin_cannot_delete_itself(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)->delete('/admin/users/'.$admin->id)->assertForbidden();
    }

    public function test_the_last_active_admin_cannot_be_demoted_deactivated_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        // Two admins: demoting one is fine.
        $this->actingAs($admin)
            ->put('/admin/users/'.$other->id, ['user_type_id' => UserTypeEnum::SUPERVISOR->value, 'name' => $other->name, 'email' => $other->email])
            ->assertSessionHas('success');

        // Now $admin is the last one.
        $this->actingAs($other->fresh())
            ->put('/admin/users/'.$admin->id, ['user_type_id' => UserTypeEnum::ADMIN->value, 'name' => $admin->name, 'email' => $admin->email, 'active' => false])
            ->assertForbidden();

        $lastAdmin = User::factory()->admin()->create();
        User::query()->whereKey($admin->id)->delete();

        $this->actingAs($lastAdmin)
            ->put('/admin/users/'.$lastAdmin->id, ['user_type_id' => UserTypeEnum::SUPERVISOR->value, 'name' => $lastAdmin->name, 'email' => $lastAdmin->email])
            ->assertSessionHas('error');

        $this->assertTrue($lastAdmin->fresh()->isAdmin());
    }

    public function test_role_never_comes_from_a_self_service_path(): void
    {
        // The profile endpoint ignores user_type_id and active entirely.
        $supervisor = User::factory()->supervisor()->create();

        $this->actingAs($supervisor)
            ->put('/admin/profile', ['name' => 'Novo Nome', 'user_type_id' => UserTypeEnum::ADMIN->value, 'active' => true])
            ->assertSessionHas('success');

        $this->assertTrue($supervisor->fresh()->isSupervisor());
    }
}
